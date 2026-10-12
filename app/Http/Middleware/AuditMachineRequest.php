<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\Machine\MachineRequestAuditor;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditMachineRequest
{
    public function __construct(private MachineRequestAuditor $auditor) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->auditor->requestId($request->header('X-Request-Id'));
        $request->attributes->set('machine_request_id', $requestId);

        try {
            $response = $next($request);
            $this->writeLog($request, $requestId, $response->getStatusCode());
            $response->headers->set('X-Request-Id', $requestId);

            return $response;
        } catch (Throwable $exception) {
            $status = match (true) {
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ValidationException => 422,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            $this->writeLog($request, $requestId, $status);

            throw $exception;
        }
    }

    private function writeLog(Request $request, string $requestId, int $status): void
    {
        $identity = $request->user();
        $route = $request->route();
        $this->auditor->record(
            $identity,
            $route?->getName() ?: $request->method().' '.$request->path(),
            $status,
            $requestId,
            $request->query('sku'),
            $request->query('warehouse'),
        );
    }
}
