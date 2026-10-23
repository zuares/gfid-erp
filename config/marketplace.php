<?php

return [
    // Profil scope yang dipakai oleh integrasi Ads Dashboard. Scope aktual
    // tetap harus diaktifkan pada console resmi provider; token tidak pernah
    // dimasukkan secara manual melalui UI aplikasi.
    'read_only_api_scopes' => ['ads.read', 'shop.read'],

    // Must exceed the normal scheduler batch runtime so manual, finance, and
    // settlement syncs for one store cannot overlap while a batch is finishing.
    'settlement_lock_ttl' => (int) env('MARKETPLACE_SETTLEMENT_LOCK_TTL', 3600),

    // Pilot Shopee Finance tetap nonaktif sampai payout dan mutasi bank toko
    // ini diperiksa. Nama toko dipakai karena ID berbeda antar lingkungan.
    'finance_pilot_store_name' => env('MARKETPLACE_FINANCE_PILOT_STORE_NAME', 'Greatfit.id'),
    'finance_pilot_sync_enabled' => (bool) env('MARKETPLACE_FINANCE_PILOT_SYNC_ENABLED', false),

    // Order-only ledger sync is independent from payout/bank reconciliation.
    // It only imports the three legacy order sources for one exact store name.
    'finance_order_sync_store_name' => env('MARKETPLACE_FINANCE_ORDER_SYNC_STORE_NAME', 'Greatfit.id'),
    'finance_order_sync_enabled' => (bool) env('MARKETPLACE_FINANCE_ORDER_SYNC_ENABLED', false),
    'finance_order_sync_interval_minutes' => (int) env('MARKETPLACE_FINANCE_ORDER_SYNC_INTERVAL_MINUTES', 15),

    // Ads dashboard menyimpan spend sebelum pajak. Jadikan configurable agar
    // laporan profit tidak mengunci aturan pajak di source code.
    'ads_vat_percent' => (float) env('MARKETPLACE_ADS_VAT_PERCENT', 11),

    // Mapping COA marketplace. Saldo iklan dipisahkan dari clearing agar
    // pemakaian iklan tidak mengurangi 1302 untuk kedua kalinya.
    'accounting_accounts' => [
        'marketplace_receivable' => env('MARKETPLACE_ACCOUNT_RECEIVABLE', '1302'),
        // Cash can arrive before the corresponding settlement is final and
        // reconciled. Keep that amount in a temporary liability instead of
        // allowing the marketplace clearing asset to become negative.
        'marketplace_payout_pending' => env('MARKETPLACE_ACCOUNT_PAYOUT_PENDING', '2301'),
        'ad_wallet' => env('MARKETPLACE_ACCOUNT_AD_WALLET', '1304'),
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
