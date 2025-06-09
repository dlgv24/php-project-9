<?php

namespace App\Models;

use DiDom\Element;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DiDom\Document;

class UrlCheck
{
    private string $url;
    private int $statusCode;
    private string $h1;
    private string $title;
    private string $description;
    public function __construct(string $url)
    {
        $this->url = $url;
    }
    public function check(): void
    {
        $client = new Client([
            'allow_redirects' => true,
        ]);

        try {
            $response = $client->request('GET', $this->url);
            $this->statusCode = $response->getStatusCode();
        } catch (GuzzleException $e) {
            $this->statusCode = 404;
        }
        if ($this->statusCode < 200 || $this->statusCode > 299) {
            return;
        }
        $html = $response->getBody()->getContents();
        $document = new Document($html);

        $h1 = $document->first('h1');
        $this->h1 = $h1 instanceof Element ? $h1->text() : '';

        $title = $document->first('title');
        $this->title = $title instanceof Element ? $title->text() : '';

        $description = $document->first('meta[name="description"]');
        $this->description = ($description instanceof Element && $description->getAttribute('content') !== null)
            ? $description->getAttribute('content')
            : '';
    }
    public function resourceIsAvailable(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    public function getH1(): string
    {
        return $this->h1;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
