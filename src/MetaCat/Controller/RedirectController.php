<?php
namespace MetaCat\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RedirectController implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        //redirect to "view" when requesting directory
        $controllers->get('{url}/', function(Application $app, Request $request, $url) {

            $pathInfo = $request->getPathInfo();
            $requestUri = $request->getRequestUri();

            $nurl = str_replace($pathInfo, $pathInfo . 'view', $requestUri);

            return $app->redirect($nurl, 301);

        })->assert('url', '^(?!.*view).+$');

        return $controllers;
    }

}
?>