<?php

declare(strict_types=1);

namespace App\Http\Responders;

use Psr\Http\Message\ResponseInterface as Response;

final class JsonResponder
{
    public static function respond(Response $response, array $payload, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    public static function success(Response $response, mixed $data = null, int $status = 200): Response
    {
        return self::respond($response, [
            'success' => true,
            'data' => $data,
        ], $status);
    }

    public static function error(Response $response, string $message, int $status = 400, array $errors = []): Response
    {
        $payload = [
            'success' => false,
            'error' => $message,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return self::respond($response, $payload, $status);
    }
}
