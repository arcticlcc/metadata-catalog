<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class ProjectController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers->get('/view', function(Application $app) {

            $em = $app['orm.em'];
            $sql = "SELECT p.projectid as id,
                html IS NOT NULL as has_html,
                xml IS NOT NULL as has_xml,
                p.json#>>'{metadata,resourceInfo,citation,title}' as title,
                (SELECT value FROM jsonb_array_elements(json#>'{contact}') AS c WHERE
                    c->'contactId' = (SELECT value FROM jsonb_array_elements(json#>'{metadata,resourceInfo,citation,responsibleParty}') AS role WHERE
                    role@>'{\"role\":\"owner\"}' LIMIT 1)->'contactId') ->>'organizationName' as owner
                FROM project p";
            $rsm = new ResultSetMappingBuilder($em);
            $rsm->addRootEntityFromClassMetadata('MetaCat\Entity\Project', 'p');
            //$rsm->addFieldResult('p', 'id', 'projectid');
            $rsm->addScalarResult('id', 'id');
            $rsm->addScalarResult('has_html', 'has_html');
            $rsm->addScalarResult('has_xml', 'has_xml');
            $rsm->addScalarResult('title', 'title');
            $rsm->addScalarResult('owner', 'owner');
            $query = $em->createNativeQuery($sql, $rsm);
            $results = $query->getArrayResult();

            return $app['twig']->render('metadata.html.twig', array(
                'title' => 'Projects',
                'active_page' => 'project',
                'path' => ['project' => ['Projects']],
                'data' => $results
            ));

        })->bind('project');

        $controllers->get('/{id}/view', function(Application $app, $id) {

            $em = $app['orm.em'];
                $query = $em->createQuery("SELECT partial c.{projectid, json}, partial p.{productid, json} from MetaCat\Entity\Project c
                    LEFT JOIN c.products p where c.projectid = ?1");
                $query->setParameter(1, $id);
                $item = $query->getArrayResult();

                if($item) {
                    //set the id alias manually
                    $item[0]['id'] = $item[0]['projectid'];
                    return $app['twig']->render('project_view.html.twig', array(
                        'title' => "Project: {$item[0]['id']}",
                        'active_page' => 'project',
                        'path' => [
                            'project' => ['Projects'],
                            'view' => ['View'],
                        ],
                        'data' => $item[0]
                    ));
                } else {
                    $app->abort(404, "No item found with id: $id.");
                }

        })->bind('projectview');

        return $controllers;
    }

}
?>
