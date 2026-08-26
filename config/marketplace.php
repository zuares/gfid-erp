<?php

return [
    // Must exceed the normal scheduler batch runtime so manual, finance, and
    // settlement syncs for one store cannot overlap while a batch is finishing.
    'settlement_lock_ttl' => (int) env('MARKETPLACE_SETTLEMENT_LOCK_TTL', 3600),

    // Ads dashboard menyimpan spend sebelum pajak. Jadikan configurable agar
    // laporan profit tidak mengunci aturan pajak di source code.
    'ads_vat_percent' => (float) env('MARKETPLACE_ADS_VAT_PERCENT', 11),

    // Mapping COA marketplace. Tahap awal hanya menyiapkan master akun;
    // posting otomatis tetap menggunakan aturan existing sampai tahap 2.
    'accounting_accounts' => [
        'marketplace_receivable' => env('MARKETPLACE_ACCOUNT_RECEIVABLE', '1302'),
        'sales' => env('MARKETPLACE_ACCOUNT_SALES', '4101'),
        'sales_return' => env('MARKETPLACE_ACCOUNT_SALES_RETURN', '4201'),
        'commission_fee' => env('MARKETPLACE_ACCOUNT_COMMISSION', '6202'),
        'service_fee' => env('MARKETPLACE_ACCOUNT_SERVICE_FEE', '6203'),
        'transaction_fee' => env('MARKETPLACE_ACCOUNT_TRANSACTION_FEE', '6204'),
        'affiliate_fee' => env('MARKETPLACE_ACCOUNT_AFFILIATE', '6205'),
        'advertising' => env('MARKETPLACE_ACCOUNT_ADVERTISING', '6206'),
        'shipping_insurance' => env('MARKETPLACE_ACCOUNT_SHIPPING_INSURANCE', '6207'),
        'other_fee' => env('MARKETPLACE_ACCOUNT_OTHER_FEE', '6201'),
    ],
];
