<?php

namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class ProjectController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/view', function (Application $app, Request $request) {
            $sql = "SELECT * FROM (SELECT p.projectid as id,
                html IS NOT NULL as has_html,
                xml IS NOT NULL as has_xml,
                p.json#>>'{metadata,resourceInfo,citation,title}' as title,
                (SELECT value FROM jsonb_array_elements(json#>'{contact}') AS c WHERE
                    c->'contactId' = (SELECT value FROM
                    jsonb_array_elements(json#>'{metadata,resourceInfo,citation,responsibleParty}') AS role
                    WHERE role@>'{\"role\":\"administrator\"}' LIMIT 1)
                      ->'party'->0->'contactId')->>'name' as owner
                FROM project p) p";
            $class = 'MetaCat\Entity\Project';
            $pagination = $app['mc.paginator']($request, $sql, $class);

            return $app['twig']->render('metadata.html.twig', array(
                'title' => 'Projects',
                'active_page' => 'project',
                'path' => ['project' => ['Projects']],
                'data' => $pagination,
                'pagination' => $pagination,
                'owners' => $app['mc.cache.owners']('project'),
            ));

        })->bind('project');

        $controllers->get('/{id}/view', function (Application $app, $id) {

            $em = $app['orm.em'];
            $query = $em->createQuery("SELECT partial c.{projectid, json}, partial p.{productid, json}
                from MetaCat\Entity\Project c
                LEFT JOIN c.products p where c.projectid = ?1");
            $query->setParameter(1, $id);
            $item = $query->getArrayResult();

            if (!$item) {
                $app->abort(404, "No item found with id: $id.");
            }
            //set the id alias manually
            $item[0]['id'] = $item[0]['projectid'];

            return $app['twig']->render('project_view.html.twig', array(
                'title' => "Project: {$item[0]['id']}",
                'active_page' => 'project',
                'path' => [
                    'project' => ['Projects'],
                    'view' => ['View'],
                ],
                'data' => $item[0],
            ));

        })->bind('projectview');

        return $controllers;
    }
}
