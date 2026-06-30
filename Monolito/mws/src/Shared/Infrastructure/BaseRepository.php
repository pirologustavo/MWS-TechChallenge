<?php

namespace App\Shared\Infrastructure;

use GuzzleHttp\Client;

abstract class BaseRepository
{
    protected string $baseUri;

    protected function getToken(): string
    {
        return $_COOKIE['token'] ?? '';
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Accept'        => 'application/json',
        ];
    }

    protected function httpClient(float $timeout = 5.0): Client
    {
        return new Client([
            'base_uri' => $this->baseUri,
            'timeout'  => $timeout,
        ]);
    }
}