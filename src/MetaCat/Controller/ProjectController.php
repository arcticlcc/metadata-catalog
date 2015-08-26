<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

class ProjectController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app) {

            $em = $app['orm.em'];
            $qb = $em->createQueryBuilder();

            $qb->select(array('p.projectid as id')) //
                ->addSelect(array('JSONB_HGG(p.json, \'{metadata,resourceInfo,citation,title}\') as title')) //
                ->from('MetaCat\Entity\Project', 'p') //
                ->orderBy('title', 'ASC');

            $query = $qb->getQuery();
            $results = $query->getArrayResult();

            return $app['twig']->render('metadata.html.twig', array(
                'title' => 'Projects',
                'active_page' => 'project',
                'path' => ['project' => 'Projects'],
                'data' => $results
            ));

        })->bind('project');

        return $controllers;
    }

}
?>