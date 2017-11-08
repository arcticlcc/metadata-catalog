<?php

namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/view', function (Application $app, Request $request) {
            $sql = "SELECT * FROM (SELECT p.productid as id,
                html IS NOT NULL as has_html,
                xml IS NOT NULL as has_xml,
                p.json#>>'{metadata,resourceInfo,citation,title}' as title,
                (SELECT value FROM jsonb_array_elements(json#>'{contact}') AS c WHERE
                    c->'contactId' = (SELECT value FROM
                    jsonb_array_elements(json#>'{metadata,resourceInfo,citation,responsibleParty}') AS role
                    WHERE role@>'{\"role\":\"administrator\"}' LIMIT 1)
                      ->'party'->0->'contactId')->>'name' as owner
                FROM product p) p";
            $class = 'MetaCat\Entity\Product';
            $pagination = $app['mc.paginator']($request, $sql, $class);

            return $app['twig']->render('metadata.html.twig', array(
                'title' => 'Products',
                'active_page' => 'product',
                'path' => ['product' => ['Products']],
                'data' => $pagination,
                'pagination' => $pagination,
                'owners' => $app['mc.cache.owners']('product'),
            ));

        })->bind('product');

        $controllers->get('/{id}/view', function (Application $app, $id) {
            //TODO: add error handling
            $em = $app['orm.em'];
                $query = $em->createQuery("SELECT c.productid as id, c.json, c.projectid, p.json as project
                    FROM MetaCat\Entity\Product c LEFT JOIN c.project p where c.productid = ?1");
                $query->setParameter(1, $id);
                $item = $query->getArrayResult();

            if (!$item) {
                $app->abort(404, "No item found with id: $id.");
            }

            return $app['twig']->render('product_view.html.twig', array(
                'title' => "Product: {$item[0]['id']}",
                'active_page' => 'product',
                'path' => [
                    'product' => ['Products'],
                    'view' => ['View'],
                ],
                'data' => $item[0],
            ));

        })->bind('productview');

        return $controllers;
    }
}
