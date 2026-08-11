<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;

class PricingCalculatorController extends Controller
{
    /**
     * Halaman Pricing & ROAS Calculator (stateless).
     * Perhitungan dilakukan realtime di frontend; service PHP dipakai untuk
     * test & kebutuhan masa depan (save preset, ambil HPP dari master item).
     */
    public function index()
    {
        // Default preset yang bisa di-edit user di halaman.
        $defaults = [
            'fee_pct'    => 21.17,
            'profit_pct' => 10,
            'roas'       => 7,
        ];

        return view('tools.pricing-calculator.index', compact('defaults'));
    }
}
