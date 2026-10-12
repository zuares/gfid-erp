<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetStockTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('gfid-erp')]
#[Version('1.0.0')]
#[Instructions('Read-only access to ERP inventory stock positions.')]
class ErpMcpServer extends Server
{
    protected array $capabilities = [
        self::CAPABILITY_TOOLS => ['listChanged' => false],
    ];

    /** @var array<int, class-string> */
    protected array $tools = [
        GetStockTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
