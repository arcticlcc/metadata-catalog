<?php

namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
//use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

class SyncController implements ControllerProviderInterface
{
      private function calculate_etag($filename, $chunksize, $expected = false) {
        /*
        DESCRIPTION:
        - calculate Amazon AWS ETag used on the S3 service
        INPUT:
        - $filename : path to file to check
        - $chunksize : chunk size in Megabytes
        - $expected : verify calculated etag against this specified etag and return true or false instead
            - if you make chunksize negative (eg. -8 instead of 8) the function will guess the chunksize by checking all possible sizes given the number of parts mentioned in $expected
        OUTPUT:
        - ETag (string)
        - or boolean true|false if $expected is set
        */
        if ($chunksize < 0) {
            $do_guess = true;
            $chunksize = 0 - $chunksize;
        } else {
            $do_guess = false;
        }

        $chunkbytes = $chunksize*1024*1024;
        $filesize = filesize($filename);
        if ($filesize < $chunkbytes && (!$expected || !preg_match("/^\\w{32}-\\w+$/", $expected))) {
            $return = md5_file($filename);
            if ($expected) {
                $expected = strtolower($expected);
                return ($expected === $return ? true : false);
            } else {
                return $return;
            }
        } else {
            $md5s = array();
            $handle = fopen($filename, 'rb');
            if ($handle === false) {
                return false;
            }
            while (!feof($handle)) {
                $buffer = fread($handle, $chunkbytes);
                $md5s[] = md5($buffer);
                unset($buffer);
            }
            fclose($handle);

            $concat = '';
            foreach ($md5s as $indx => $md5) {
                $concat .= hex2bin($md5);
            }
            $return = md5($concat) .'-'. count($md5s);
            if ($expected) {
                $expected = strtolower($expected);
                $matches = ($expected === $return ? true : false);
                if ($matches || $do_guess == false || strlen($expected) == 32) {
                    return $matches;
                } else {
                    // Guess the chunk size
                    preg_match("/-(\\d+)$/", $expected, $match);
                    $parts = $match[1];
                    $min_chunk = ceil($filesize / $parts /1024/1024);
                    $max_chunk =  floor($filesize / ($parts-1) /1024/1024);
                    $found_match = false;
                    for ($i = $min_chunk; $i <= $max_chunk; $i++) {
                        if ($this->calculate_etag($filename, $i) === $expected) {
                            $found_match = true;
                            break;
                        }
                    }
                    return $found_match;
                }
            } else {
                return $return;
            }
        }
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/s3', function (Application $app) {
            // Instantiate the Message and Validator
            try {
                $message = Message::fromRawPostData();
            } catch (\Exception $e) {
                throw new HttpException(404, 'POST data is absent, or not a valid JSON document: '.$e->getMessage());
            }
            $validator = new MessageValidator();
            // Validate the message and log errors if invalid.
            try {
                $validator->validate($message);
            } catch (InvalidSnsMessageException $e) {
                // Pretend we're not here if the message is invalid.
                throw new HttpException(404, 'SNS Message Validation Error: '.$e->getMessage());
            }

            // Check the type of the message and handle the subscription.
            $type = $message['Type'];
            if (in_array($type, ['SubscriptionConfirmation', 'UnsubscribeConfirmation'])) {
                // Confirm the (un)subscription by sending a GET request to the SubscribeURL
                file_get_contents($message['SubscribeURL']);
            } elseif ('Notification' === $type) {
                $json = json_decode($message['Message']);
                $record = end($json->Records);
                $bucket = $record->s3->bucket->name;
                $key = $record->s3->object->key;

                try {
                    if (isset($app['config']['white']['sync'])) {
                        $white = $app['config']['white']['sync'];
                        if ($white['key'] !== $key || $white['bucket'] != $bucket) {
                            throw new \Exception('Key or Bucket is invalid. Check whitelist config.');
                        }
                    }
                } catch (\Exception $e) {
                    throw new HttpException(403, 'Failed sync white-list validation. '.$e->getMessage());
                }

                try {
                    //get the file from s3
                    $s3Client = $app['aws']->createS3();

                    $temp = $app['config.dir'].'../var/data/'.basename($key);

                    $result = $s3Client->getObject([
                        'Bucket' => $bucket,
                        'Key' => $key,
                        'IfMatch' => $record->s3->object->eTag,
                        'SaveAs' => $temp,
                    ]);

                    if (file_exists($temp)) {
                        //Validate md5(skip for multi-part)
                        $s3Etag = str_replace('"', '', $result['ETag']);
                        if (md5_file($temp) !== $s3Etag && !$this->calculate_etag($temp, -8, $s3Etag)) {
                            throw new \Exception('S3 exception. md5 validation failed.');
                        }
                        //unzip the contents
                        // Open our files (in binary mode)
                        $zip = gzopen($temp, 'rb');
                        $out = fopen(str_replace('.gz', '', $temp), 'wb');

                        // Keep repeating until the end of the input file
                        while (!gzeof($zip)) {
                            // Read buffer-size bytes
                            // Both fwrite and gzread and binary-safe
                            fwrite($out, gzread($zip, 4096));
                        }

                        // Files are done, close files
                        fclose($out);
                        gzclose($zip);
                        unlink($temp);

                        $app['import.dbal']();
                    } else {
                        throw new Exception("Error retrieving file from S3. $temp
                          does not exist.");
                    }
                } catch (S3Exception $e) {
                    // Catch an S3 specific exception.
                    throw new \Exception('S3 exception. '.$e->getMessage());
                } catch (AwsException $e) {
                    // This catches the more generic AwsException.
                    throw new \Exception("AWS exception. {$e->getAwsErrorType()}: {$e->getMessage()}");
                }
            } else {
                throw new HttpException(404, "Unknown message type: $type");
            }

            return new Response('OK', 200);
        });

        return $controllers;
    }
}
