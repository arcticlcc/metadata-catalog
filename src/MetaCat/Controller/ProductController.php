<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class ProductController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers->get('/view', function(Application $app) {

            $em = $app['orm.em'];
            $sql = "SELECT p.productid as id,
                p.json#>>'{metadata,resourceInfo,citation,title}' as title,
                (SELECT value FROM jsonb_array_elements(json#>'{contact}') AS c WHERE
                    c->'contactId' = (SELECT value FROM jsonb_array_elements(json#>'{metadata,resourceInfo,citation,responsibleParty}') AS role WHERE
                    role@>'{\"role\":\"owner\"}' LIMIT 1)->'contactId') ->>'organizationName' as owner
                FROM product p";
            $rsm = new ResultSetMappingBuilder($em);
            $rsm->addRootEntityFromClassMetadata('MetaCat\Entity\Project', 'p');
            //$rsm->addFieldResult('p', 'id', 'projectid');
            $rsm->addScalarResult('id', 'id');
            $rsm->addScalarResult('title', 'title');
            $rsm->addScalarResult('owner', 'owner');
            $query = $em->createNativeQuery($sql, $rsm);
            $results = $query->getArrayResult();

            return $app['twig']->render('metadata.html.twig', array(
                'title' => 'Products',
                'active_page' => 'product',
                'path' => ['product' => ['Products']],
                'data' => $results
            ));

        })->bind('product');

        $controllers->get('/{id}/view', function(Application $app, $id) {
            //TODO: add error handling
            $em = $app['orm.em'];
                $query = $em->createQuery("SELECT c.productid as id, c.json, c.projectid, p.json as project from MetaCat\Entity\Product c LEFT JOIN c.project p where c.productid = ?1");
                $query->setParameter(1, $id);
                $item = $query->getArrayResult();

            if ($item) {
                return $app['twig']->render('product_view.html.twig', array(
                    'title' => "Product: {$item[0]['id']}",
                    'active_page' => 'product',
                    'path' => [
                        'product' => ['Products'],
                        'view' => ['View'],
                    ],
                    'data' => $item[0]
                ));
            } else {
                $app->abort(404, "No item found with id: $id.");
            }


        })->bind('productview');

        return $controllers;
    }

}
?>