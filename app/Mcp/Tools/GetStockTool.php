<?php

namespace App\Mcp\Tools;

use App\Models\ServiceIdentity;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\StockPositionNotFoundException;
use App\Services\Machine\LocalServiceTokenAuthenticator;
use App\Services\Machine\MachineInventoryRateLimiter;
use App\Services\Machine\MachineRequestAuditor;
use App\Services\Machine\ServiceIdentityAuthorizer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[Name('get_stock')]
#[Description('Read on-hand, allocated, and available stock by SKU, optionally for one active warehouse.')]
#[IsReadOnly]
class GetStockTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'sku' => $schema->string()
                ->min(1)
                ->max(80)
                ->pattern('^[A-Za-z0-9._-]+$')
                ->description('Item SKU.')
                ->required(),
            'warehouse' => $schema->string()
                ->min(1)
                ->max(50)
                ->pattern('^[A-Za-z0-9._-]+$')
                ->description('Optional active warehouse code.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        $warehousePosition = fn () => $schema->object([
            'warehouse' => $schema->string()->required(),
            'on_hand' => $schema->number()->required(),
            'allocated' => $schema->number()->required(),
            'available' => $schema->number()->required(),
        ])->withoutAdditionalProperties();

        $totals = fn () => $schema->object([
            'on_hand' => $schema->number()->required(),
            'allocated' => $schema->number()->required(),
            'available' => $schema->number()->required(),
        ])->withoutAdditionalProperties();

        return [
            'sku' => $schema->string()->required(),
            'warehouse' => $schema->string(),
            'on_hand' => $schema->number(),
            'allocated' => $schema->number(),
            'available' => $schema->number(),
            'warehouses' => $schema->array()->items($warehousePosition()),
            'total' => $totals(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $identity = null;
        $status = 500;
        $input = $request->all();
        $sku = $input['sku'] ?? null;
        $warehouse = $input['warehouse'] ?? null;
        $requestId = $this->correlationId($request->meta());

        try {
            $identity = app(LocalServiceTokenAuthenticator::class)->authenticate();

            if (! $identity instanceof ServiceIdentity) {
                $status = 401;

                return Response::error('Service authentication required.');
            }

            $authorizer = app(ServiceIdentityAuthorizer::class);

            if (! $authorizer->allows($identity, 'mcp:inventory:read')) {
                $status = 403;

                return Response::error('Service identity is not authorized for inventory reads.');
            }

            $retryAfter = app(MachineInventoryRateLimiter::class)->consume(
                $identity,
                $identity->currentAccessToken(),
            );

            if ($retryAfter !== null) {
                $status = 429;

                return Response::error('Too many inventory requests.');
            }

            if (array_diff(array_keys($input), ['sku', 'warehouse']) !== []) {
                $status = 422;

                return Response::error('Invalid input.');
            }

            try {
                $validated = $request->validate([
                    'sku' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
                    'warehouse' => ['sometimes', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
                ]);
            } catch (ValidationException) {
                $status = 422;

                return Response::error('Invalid input.');
            }

            $position = app(InventoryService::class)->getStockPositionForSku(
                $validated['sku'],
                $validated['warehouse'] ?? null,
            );

            if (isset($validated['warehouse'])) {
                $stock = $position['warehouses'][0];
                $result = [
                    'sku' => $position['sku'],
                    'warehouse' => $stock['warehouse'],
                    'on_hand' => $stock['on_hand'],
                    'allocated' => $stock['allocated'],
                    'available' => $stock['available'],
                ];
            } else {
                $result = [
                    'sku' => $position['sku'],
                    'warehouses' => array_map(static fn (array $stock): array => [
                        'warehouse' => $stock['warehouse'],
                        'on_hand' => $stock['on_hand'],
                        'allocated' => $stock['allocated'],
                        'available' => $stock['available'],
                    ], $position['warehouses']),
                    'total' => $position['total'],
                ];
            }

            $status = 200;

            return Response::structured($result);
        } catch (StockPositionNotFoundException $exception) {
            $status = 404;

            return Response::error($exception->resource === 'sku' ? 'SKU not found.' : 'Warehouse not found.');
        } catch (Throwable) {
            $status = 500;

            return Response::error('Stock lookup failed.');
        } finally {
            app(MachineRequestAuditor::class)->record(
                $identity,
                'get_stock',
                $status,
                $requestId,
                $sku,
                $warehouse,
            );
        }
    }

    private function correlationId(?array $meta): ?string
    {
        foreach (['correlationId', 'requestId'] as $key) {
            $value = $meta[$key] ?? null;

            if (is_string($value) && preg_match('/^[A-Za-z0-9._:-]{8,80}$/', $value)) {
                return $value;
            }
        }

        return null;
    }
}
