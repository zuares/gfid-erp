<?php

namespace App\Services\Inventory;

use RuntimeException;

class StockPositionNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $resource)
    {
        parent::__construct('Stock position not found.');
    }
}
