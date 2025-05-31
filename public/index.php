<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response): Response {
    $response->getBody()->write('Hello, World!');
    return $response;
})->setName('home');

$app->run();
