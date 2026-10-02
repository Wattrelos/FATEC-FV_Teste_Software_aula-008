<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Responders\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controller/Action para área restrita (Dashboard).
 */
final class DashboardAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $accept = $request->getHeaderLine('Accept');
        $isJson = str_contains($accept, 'application/json')
            || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if (!$isJson) {
            return $response
                ->withHeader('Location', '/dashboard.html')
                ->withStatus(302);
        }

        return JsonResponder::success($response, [
            'authenticated' => true,
            'user' => [
                'id'    => $_SESSION['customer_id'] ?? null,
                'name'  => $_SESSION['customer_name'] ?? null,
                'email' => $_SESSION['customer_email'] ?? null,
            ],
            'authenticated_at' => $_SESSION['authenticated_at'] ?? null,
        ]);
    }
}
