<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Di\Container;
use App\View;
use Slim\Flash\Messages;

return function (Container $container) {
    $container->set('pdo', function () {
        if (isset($_ENV['DATABASE_URL'])) {
            $databaseUrl = parse_url($_ENV['DATABASE_URL']);
            $user = $databaseUrl['user'];
            $pass = $databaseUrl['pass'];
            $host = $databaseUrl['host'];
            $port = $databaseUrl['port'];
            $dbName = ltrim($databaseUrl['path'], '/');
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";
        } else {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            );
            $user = $_ENV['DB_USER'];
            $pass = $_ENV['DB_PASSWORD'];
        }

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    });

    $container->set('view', function () {
        return new View();
    });

    $container->set('flash', function () {
        return new Messages();
    });
};
