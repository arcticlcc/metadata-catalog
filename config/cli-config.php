<?php
//config for doctrine CLI commands
require_once __DIR__.'/../vendor/autoload.php';

set_time_limit(0);

use Doctrine\ORM\Tools\Console\ConsoleRunner;

$env = 'dev';
$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/'.$env.'.php';

// replace with mechanism to retrieve EntityManager in your app
$entityManager = $app['orm.em'];

return ConsoleRunner::createHelperSet($entityManager);