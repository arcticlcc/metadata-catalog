<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use MetaCat\Entity\Project;

class ProjectController implements ControllerProviderInterface {
    public function connect(Application $app) {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app) {

            $em = $app['orm.em'];
            $qb = $em->createQueryBuilder();

            $qb->select(array('p.projectid as id')) //
                ->addSelect(array('JSONB_HGG(p.json, \'{metadata,resourceInfo,citation,title}\') as title')) //
                ->from('MetaCat\Entity\Project', 'p') //
                ->orderBy('title', 'ASC');

            $query = $qb->getQuery();
            $results = $query->getResult();

            return $app['twig']->render('project.html.twig', array(
                'title' => 'Projects',
                'active_page' => 'project',
                'path' => ['project' => 'Projects'],
                'data' => $results
            ));


            foreach ($results as $row) {
                echo "uuid: " . $row['projectid'];
                echo "title: " . $row['title'];
            }

            return TRUE;
        })->bind('project');

        return $controllers;
    }

}
?>