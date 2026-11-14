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
        $headers = [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Accept'        => 'application/json',
        ];
        $correlationId = $_SERVER['MWS_CORRELATION_ID'] ?? $_SERVER['HTTP_X_CORRELATION_ID'] ?? '';
        if ($correlationId !== '') $headers['X-Correlation-ID'] = $correlationId;
        return $headers;
    }

    protected function httpClient(float $timeout = 5.0): Client
    {
        return new Client([
            'base_uri' => $this->baseUri,
            'timeout'  => $timeout,
        ]);
    }
}
