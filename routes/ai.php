<?php

use App\Mcp\Servers\ErpMcpServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('erp', ErpMcpServer::class);
