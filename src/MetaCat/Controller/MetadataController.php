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

        $controllers->get('/{entity}/{id}.{format}', function(Application $app, $entity, $id, $format) {

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

        $controllers->get('/{id}.{format}', function(Application $app, $id, $format) {

            $em = $app['orm.em'];

            if(isset($app['config']['white']['entity'][$id])) {
                $em = $app['orm.em'];
                $class = 'MetaCat\Entity\\' . ucfirst($id);

                $sql = "SELECT json_agg(p.json) as out FROM $id p";
                $rsm = new ResultSetMappingBuilder($em);
                $rsm->addRootEntityFromClassMetadata($class, 'p');
                $rsm->addScalarResult('out', 'out');
                $query = $em->createNativeQuery($sql, $rsm);
                $item = $query->getSingleScalarResult();

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

        return $controllers;
    }

}
?>