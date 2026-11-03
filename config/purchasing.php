<?php

return [
    // Approval PO dinonaktifkan untuk workflow berjalan: PO Draft langsung
    // dapat dijadikan acuan GRN. Env override tetap dipertahankan untuk
    // kompatibilitas jika approval diperlukan kembali di masa depan.
    'po_approval_enabled' => (bool) env('PURCHASING_PO_APPROVAL_ENABLED', false),

    // Draft GRN tetap dokumen operasional, tetapi draft yang terlalu lama
    // perlu terlihat agar PO tidak tertahan tanpa sengaja.
    'draft_grn_stale_days' => (int) env('PURCHASING_DRAFT_GRN_STALE_DAYS', 3),
];
