<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MetadataController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers->get('/{id}/view', function (Application $app, $id) {

            $em = $app['orm.em'];

            try {
                $query = $em -> createQuery("SELECT 1 from MetaCat\Entity\Project c where c.projectid = ?1");
                $query -> setParameter(1, $id);
                $item = $query -> getSingleScalarResult();
                $subRequest = Request::create($app['url_generator']->generate('projectview', ['id' => $id]), 'GET');

                return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
            } catch (\Doctrine\ORM\NoResultException $e) {

                try {
                    $query = $em -> createQuery("SELECT 1 from MetaCat\Entity\Product c where c.productid = ?1");
                    $query -> setParameter(1, $id);
                    $item = $query -> getSingleScalarResult();
                    $subRequest = Request::create($app['url_generator']->generate('productview', ['id' => $id]), 'GET');

                    return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

                } catch (\Doctrine\ORM\NoResultException $e) {

                    throw new HttpException(404, "No record found for id: $id");

                }
            }
        })
        ->bind('metadatabaseview');

        $controllers->get('/{entity}/{id}.{format}', function (Application $app, $entity, $id, $format) {

            $em = $app['orm.em'];

            $qb = $em->createQueryBuilder();
            $qb->select('c.' . $format)
               ->from('MetaCat\Entity\\' . ucfirst($entity), 'c')
               ->where("c.{$entity}id = ?1")
               ->setParameter(1, $id);
            $query = $qb->getQuery();

            try {
                $result = $query->getSingleScalarResult();

            } catch (\Doctrine\ORM\NoResultException $e) {

                throw new HttpException(404, "No record found for id: $id");
            }

            return [trim($result)];
        })
        ->bind('metadata')
        ->value('format', 'json');

        $controllers->get('/{id}.{format}', function (Application $app, Request $request, $id, $format) {

            $em = $app['orm.em'];

            if (isset($app['config']['white']['entity'][$id])) {
                $em = $app['orm.em'];
                $class = 'MetaCat\Entity\\' . ucfirst($id);
                //check owner
                $owners = array_flip(array_column($app['mc.cache.owners']($class), 'owner'));
                $owner = $request->query->get('owner', '');

                if ($owner && !isset($owners[$owner])) {
                    throw new HttpException(404, "Owner does not exist: $owner");
                }

                $sql = "SELECT json_agg(p.json) as out FROM $id p";
                $where = " WHERE ((SELECT value FROM jsonb_array_elements(json#>'{contact}') AS c WHERE
                          c->'contactId' = (SELECT value FROM
                          jsonb_array_elements(json#>'{metadata,resourceInfo,citation,responsibleParty}') AS role
                          WHERE role@>'{\"role\":\"owner\"}' LIMIT 1)->'contactId') ->>'organizationName') = ? ";
                          //add filter
                if ($owner) {
                    $sql .= $where;
                }

                $rsm = new ResultSetMappingBuilder($em);
                $rsm->addRootEntityFromClassMetadata($class, 'p');
                $rsm->addScalarResult('out', 'out');
                $query = $em->createNativeQuery($sql, $rsm);

                if ($owner) {
                    $query->setParameter(1, $owner);
                }
                $item = $query->getSingleScalarResult();

                if (!$item) {
                    throw new HttpException(404, "No $id records found.");
                }

                return [$item];
            }

            try {
                $query = $em -> createQuery("SELECT c.$format from MetaCat\Entity\Project c where c.projectid = ?1");
                $query -> setParameter(1, $id);
                $item = $query -> getSingleScalarResult();
            } catch (\Doctrine\ORM\NoResultException $e) {

                try {
                    $query = $em -> createQuery("SELECT c.$format from MetaCat\Entity\Product c where c.productid = ?1");
                    $query -> setParameter(1, $id);
                    $item = $query -> getSingleScalarResult();

                } catch (\Doctrine\ORM\NoResultException $e) {

                    throw new HttpException(404, "No record found for id: $id");

                }
            }
            return [trim($item)];
        })
        ->bind('metadatabase')
        ->value('format', 'json');

        $controllers->get('{entity1}/{id}/{entity2}.{format}', function(Application $app, Request $request, $entity1, $id, $entity2) {

            $em = $app['orm.em'];
            $white = $app['config']['white']['entity'];

            if (isset($white[$entity1], $white[$entity2])) {
                $em = $app['orm.em'];
                $class = 'MetaCat\Entity\\' . ucfirst($entity2);
                $class2 = 'MetaCat\Entity\\' . ucfirst($entity1);
                $col = $request->get('short') ? 'p.json#>\'{metadata,resourceInfo,citation}\'' : 'p.json';

                $sql = "SELECT json_agg($col) as out FROM $entity2 p JOIN $entity1 p2 USING(projectid) WHERE p2.{$entity1}id = ?";
                $rsm = new ResultSetMappingBuilder($em);
                $rsm->addRootEntityFromClassMetadata($class, 'p');
                $rsm->addJoinedEntityResult($class2, 'p2', 'p', 'projectid');
                $rsm->addScalarResult('out', 'out');
                $query = $em->createNativeQuery($sql, $rsm);
                $query->setParameter(1, $id);

            } else {
                throw new HttpException(403, 'Entity name not allowed. Valid entities are: ' . join('|', array_keys($white)));
            }

            try {
                $recs = $query->getScalarResult();
                $out = @$recs[0]['out'];

                if ($out) {
                    $request->request->set('format','json');
                    return [$out];

                } else {
                    throw new HttpException(404, "No $entity2(s) found for $entity1 id: $id");
                }

            } catch (\Doctrine\ORM\NoResultException $e) {
                throw new HttpException(404, "No record found for id: $id");

            }
        })->bind('related')
        ->value('format', 'json');

        return $controllers;
    }
}
