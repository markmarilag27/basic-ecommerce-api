<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Handle only API paths
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $message = $this->sanitizeMessage($exception->getMessage());

        $response = new JsonResponse([
            'success' => false,
            'error' => $message,
        ], $statusCode);

        $event->setResponse($response);
    }

    private function sanitizeMessage(string $message): string
    {
        // Replace double quotes with single to avoid escaping in JSON
        return str_replace('"', "'", $message);
    }
}
