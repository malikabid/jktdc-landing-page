<?php

use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create DI Container
$container = new Container();

// Load settings
$settings = require __DIR__ . '/../config/settings.php';
$container->set('settings', $settings);

// Register dependencies
$dependencies = require __DIR__ . '/../config/dependencies.php';
$dependencies($container);

// Initialize database connection
$container->get('db');

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set base path for subdirectory deployment
$app->setBasePath('/admin');

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $settings['displayErrorDetails'],
    true,
    true
);

// CORS Middleware (if needed)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Load routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// Run app
$app->run();
