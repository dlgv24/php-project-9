<?php

namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class View
{
    protected Environment $twig;

    public function __construct(string $viewsPath = __DIR__ . "/../views", array $options = [])
    {
        $loader = new FilesystemLoader($viewsPath);
        $this->twig = new Environment($loader, $options);
    }

    public function render(Response $response, string $template, array $data = []): Response
    {
        try {
            $html = $this->twig->render($template, $data);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            $response->getBody()->write('Error rendering view');
            return $response->withStatus(500);
        }
        $response->getBody()->write($html);
        return $response;
    }
}
