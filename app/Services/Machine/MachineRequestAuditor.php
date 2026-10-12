<?php

namespace App\Services\Machine;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MachineRequestAuditor
{
    public function requestId(?string $provided): string
    {
        if ($provided !== null && preg_match('/^[A-Za-z0-9._:-]{8,80}$/', $provided)) {
            return $provided;
        }

        return (string) Str::uuid();
    }

    public function record(
        mixed $identity,
        string $operation,
        int $status,
        ?string $requestId = null,
        mixed $sku = null,
        mixed $warehouse = null,
    ): void {
        Log::info('machine.request', [
            'service_identity' => $identity?->slug,
            'operation' => $operation,
            'timestamp' => now()->toIso8601String(),
            'success' => $status < 400,
            'status' => $status,
            'request_id' => $this->requestId($requestId),
            'sku' => $this->safeValue($sku),
            'warehouse' => $this->safeValue($warehouse),
        ]);
    }

    private function safeValue(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._-]{1,80}$/', $value)
            ? $value
            : null;
    }
}
