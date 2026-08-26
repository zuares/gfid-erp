<?php

return [
    // Must exceed the normal scheduler batch runtime so manual, finance, and
    // settlement syncs for one store cannot overlap while a batch is finishing.
    'settlement_lock_ttl' => (int) env('MARKETPLACE_SETTLEMENT_LOCK_TTL', 3600),

    // Ads dashboard menyimpan spend sebelum pajak. Jadikan configurable agar
    // laporan profit tidak mengunci aturan pajak di source code.
    'ads_vat_percent' => (float) env('MARKETPLACE_ADS_VAT_PERCENT', 11),
];
