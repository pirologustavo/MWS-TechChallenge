<?php

require_once __DIR__.'/../vendor/autoload.php';

$correlationId = (string) ($_SERVER['HTTP_X_CORRELATION_ID'] ?? '');
if ($correlationId === '' || !preg_match('/^[A-Za-z0-9._-]{1,100}$/', $correlationId)) {
    $correlationId = bin2hex(random_bytes(16));
}
$_SERVER['MWS_CORRELATION_ID'] = $correlationId;
header('X-Correlation-ID: '.$correlationId);
$startedAt = microtime(true);

register_shutdown_function(static function () use ($correlationId, $startedAt): void {
    $status = http_response_code() ?: 200;
    $entry = [
        'timestamp' => gmdate(DATE_ATOM),
        'level' => $status >= 500 ? 'error' : 'info',
        'message' => 'http.request.completed',
        'service' => getenv('DD_SERVICE') ?: 'monolito',
        'correlation_id' => $correlationId,
        'http_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'http_route' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
        'http_status_code' => $status,
        'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
    ];
    $logger = new Monolog\Logger('monolito');
    $handler = new Monolog\Handler\StreamHandler('php://stderr');
    $handler->setFormatter(new Monolog\Formatter\JsonFormatter());
    $logger->pushHandler($handler);
    $logger->log($status >= 500 ? 'error' : 'info', 'http.request.completed', $entry);
});
