<?php

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Repositories\UrlCheckRepository;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;
use DI\Container;
use Valitron\Validator;
use Carbon\Carbon;
use App\Repositories\UrlRepository;
use App\Models\UrlCheck;

$container = new Container();
$dependencies = require __DIR__ . '/../config/dependencies.php';
$dependencies($container);

$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response): Response {
    $params = [
        'url' => '',
        'flash' => $this->get('flash')->getMessages()
    ];
    return $this->get('view')->render($response, 'home.twig', $params);
})->setName('home');

$app->get('/urls', function (Request $request, Response $response): Response {
    $stmt = $this->get('pdo')->prepare('SELECT id, name FROM urls');
    $stmt->execute();
    $urls = $stmt->fetchAll();
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    $urlCheckRepo = new UrlCheckRepository($this->get('pdo'));
    for ($i = 0; $i < count($urls); $i++) {
        $urls[$i]['link'] = $routeParser->urlFor('check', ['url_id' => $urls[$i]['id']]);
        $lastCheck = $urlCheckRepo->lastCheck((int) $urls[$i]['id']);
        if ($lastCheck === false) {
            $urls[$i]['checked_at'] = '';
            $urls[$i]['code_status'] = '';
        } else {
            $urls[$i]['checked_at'] = $lastCheck['created_at'];
            $urls[$i]['status_code'] = $lastCheck['status_code'];
        }
    }
    $params = [
        'urls' => $urls,
        'flash' => $this->get('flash')->getMessages()
    ];
    return $this->get('view')->render($response, 'urls.twig', $params);
})->setName('urls');

$app->get('/urls/{url_id}', function (Request $request, Response $response, array $args): Response {
    $id = (int) $args['url_id'];
    $stmt = $this->get('pdo')->prepare('SELECT id, name, created_at FROM urls WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $url = $stmt->fetch();
    if ($url === false) {
        return $this->get('view')->render($response, '404.twig')->withStatus(404);
    }
    $urlCheckRepo = new UrlCheckRepository($this->get('pdo'));
    $params = [
        'url' => $url,
        'flash' => $this->get('flash')->getMessages(),
        'url_checks' => $urlCheckRepo->all($id)
    ];
    return $this->get('view')->render($response, 'check.twig', $params);
})->setName('check');

$app->post('/urls', function (Request $request, Response $response): Response {
    $url = $request->getParsedBody()['url'] ?? null;
    $v = new Validator($url);
    $v->rules([
        'required' => ['name'],
        'url' => ['name'],
        'lengthMax' => [['name', 255]]
    ]);
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    if ($v->validate()) {
        $url = parse_url($url['name'], PHP_URL_SCHEME) . '://' . parse_url($url['name'], PHP_URL_HOST);
        $stmt = $this->get('pdo')->prepare('SELECT id FROM urls WHERE name = :name');
        $stmt->execute(['name' => $url]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            $stmt = $this->get('pdo')->prepare('INSERT INTO urls (name, created_at) VALUES (:name, :created_at)');
            $stmt->execute([
               'name' => $url,
               'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
            $id = $this->get('pdo')->lastInsertId();
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        } else {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
        }

        return $response
            ->withHeader('Location', $routeParser->urlFor('check', ['url_id' => $id]))
            ->withStatus(302);
    }

    $params = [
        'url' => $url['name'],
        'flash' => ['error' => ['Некорректный URL']]
    ];
    return $this->get('view')->render($response, 'home.twig', $params)->withStatus(422);
})->setName('addUrl');

$app->post('/urls/{url_id}/checks', function (Request $request, Response $response, array $args): Response {
    $urlId = (int) $args['url_id'];
    $urlRepo = new UrlRepository($this->get('pdo'));
    $url = $urlRepo->getNameById($urlId);
    if ($url === false) {
        return $this->get('view')->render($response, '404.twig')->withStatus(404);
    }
    $urlCheck = new UrlCheck($url);
    $urlCheck->check();
    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
    if (!$urlCheck->resourceIsAvailable()) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withHeader('Location', $routeParser->urlFor('check', ['url_id' => $urlId]))->withStatus(302);
    }
    $urlCheckRepo = new UrlCheckRepository($this->get('pdo'));
    $urlCheckRepo->save($urlCheck, $urlId);
    $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    return $response->withHeader('Location', $routeParser->urlFor('check', ['url_id' => $urlId]))->withStatus(302);
});
$app->run();
