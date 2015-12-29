<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
//use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

class SyncController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers->post('/s3', function(Application $app) {
            // Instantiate the Message and Validator
            try {
                $message = Message::fromRawPostData();
            } catch (\Exception $e) {
                throw new HttpException(404, 'POST data is absent, or not a valid JSON document: ' . $e->getMessage());
            }
            $validator = new MessageValidator();
            // Validate the message and log errors if invalid.
            try {
                $validator->validate($message);

            } catch (InvalidSnsMessageException $e) {
                // Pretend we're not here if the message is invalid.
                throw new HttpException(404, 'SNS Message Validation Error: ' . $e->getMessage());
                die();
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
                } catch (\Exception $e){
                    throw new HttpException(403, 'Failed sync white-list validation. ' . $e->getMessage());
                }

                try {
                    //get the file from s3
                    $s3Client = $app['aws']->createS3();

                    $temp = $app['config.dir'] . '../var/data/' . basename($key);

                    $result = $s3Client->getObject([
                        'Bucket' => $bucket,
                        'Key'    => $key,
                        'IfMatch' => $record->s3->object->eTag,
                        'SaveAs' => $temp
                    ]);

                    //Validate md5(skip for multi-part)
                    $s3Etag = str_replace('"', '', $result['ETag']);
                    if(md5_file($temp) !== $s3Etag || strpos($s3Etag, '-') !== FALSE) {
                        throw new \Exception('S3 exception. md5 validation failed.');
                    }
                    //unzip the contents
                    // Open our files (in binary mode)
                    $zip = gzopen($temp, 'rb');
                    $out = fopen(str_replace('.gz', '', $temp), 'wb');

                    // Keep repeating until the end of the input file
                    while(!gzeof($zip)) {
                        // Read buffer-size bytes
                        // Both fwrite and gzread and binary-safe
                        fwrite($out, gzread($zip, 4096));
                    }

                    // Files are done, close files
                    fclose($out);
                    gzclose($zip);
                    unlink($temp);

                    $app['import.dbal']();

                } catch (S3Exception $e) {
                    // Catch an S3 specific exception.
                    throw new \Exception('S3 exception. ' . $e->getMessage());
                } catch (AwsException $e) {
                    // This catches the more generic AwsException.
                    throw new \Exception("AWS exception. {$e->getAwsErrorType()}: {$e->getMessage()}");
                }
            } else {
                throw new HttpException(404, "Unknown message type: $type");
                die();
            }

            return new Response('OK', 200);
        });

        return $controllers;
    }

}
?>