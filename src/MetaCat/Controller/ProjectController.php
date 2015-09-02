<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class ProjectController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app) {

            $em = $app['orm.em'];
            $sql = "SELECT p.projectid as id,
                p.json#>>'{metadata,resourceInfo,citation,title}' as title,
                (SELECT value FROM jsonb_array_elements(json#>'{contact}') AS c WHERE
                    c->'contactId' = (SELECT value FROM jsonb_array_elements(json#>'{metadata,resourceInfo,citation,responsibleParty}') AS role WHERE
                    role@>'{\"role\":\"owner\"}' LIMIT 1)->'contactId') ->>'organizationName' as owner
                FROM project p";
            $rsm = new ResultSetMappingBuilder($em);
            $rsm->addRootEntityFromClassMetadata('MetaCat\Entity\Project', 'p');
            //$rsm->addFieldResult('p', 'id', 'projectid');
            $rsm->addScalarResult('id', 'id');
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

        return $controllers;
    }

}
?>