<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$dependencies = require __DIR__ . '/../config/dependencies.php';
$dependencies($container);

$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response): Response {
    return $this->get('view')->render($response, 'home.twig');
})->setName('home');

$app->get('/urls', function (Request $request, Response $response): Response {
    return $this->get('view')->render($response, 'urls.twig');
})->setName('urls');

$app->run();
