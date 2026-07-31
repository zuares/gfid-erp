<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ShopeeApiLog;
use Illuminate\Http\Request;

class ShopeeApiLogController extends Controller
{
    public function index()
    {
        // Load recent 50 logs on first load
        $logs = ShopeeApiLog::orderBy('id', 'desc')->take(50)->get();
        return view('marketplace.shopee_api_logs', compact('logs'));
    }
}
