<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

class ProductController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app) {

            $em = $app['orm.em'];
            $qb = $em->createQueryBuilder();

            $qb->select(array('p.productid as id','p.projectid')) //
                ->addSelect(array('JSONB_HGG(p.json, \'{metadata,resourceInfo,citation,title}\') as title')) //
                ->from('MetaCat\Entity\Product', 'p') //
                ->orderBy('title', 'ASC');

            $query = $qb->getQuery();
            $results = $query->getArrayResult();

            return $app['twig']->render('metadata.html.twig', array(
                'title' => 'Products',
                'active_page' => 'product',
                'path' => ['product' => 'Products'],
                'data' => $results
            ));

        })->bind('product');

        return $controllers;
    }

}
?>