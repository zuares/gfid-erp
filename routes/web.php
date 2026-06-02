<?php

// Grouping per domain
require __DIR__ . '/web/auth.php';
require __DIR__ . '/web/dashboard.php';
require __DIR__ . '/web/purchasing.php';
require __DIR__ . '/web/inventory.php';
require __DIR__ . '/web/production.php';
require __DIR__ . '/web/production-legacy.php'; // @deprecated — Orders/Issues/Receipts/Activities (lihat file)
require __DIR__ . '/web/payroll.php';
require __DIR__ . '/web/costing.php';
require __DIR__ . '/web/marketplace.php';
require __DIR__ . '/web/master.php';
require __DIR__ . '/web/sales.php';
require __DIR__ . '/web/accounting.php';
require __DIR__ . '/web/shipments.php';
require __DIR__ . '/web/imports.php';

// NOTE: Route produksi-lama (orders/issues/receipts/activities) dipindah ke
// routes/web/production-legacy.php agar konsisten dengan pola routing per-domain.
