<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Configure session lifetime (24 hours)
ini_set('session.gc_maxlifetime', '86400');
ini_set('session.cookie_lifetime', '86400');

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/container.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
Crowdmap\Http\ErrorHandling::register($app, Crowdmap\Http\ErrorHandling::debugFromEnvironment());

(require __DIR__ . '/../src/routes.php')($app);

$app->run();
