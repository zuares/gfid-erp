<?php

return [
    // Pakai CODE langsung (stabil, konsisten dev/prod)
    'inventory_account_code' => '1201', // Persediaan Bahan Baku
    'ap_account_code' => '2101', // Hutang Dagang

    // Selisih pembulatan nominal purchasing maksimal yang dianggap lunas.
    'purchase_payment_tolerance' => 1.00,

    // Payroll memakai transaksi yang sama dengan ledger lama; tanpa saldo awal otomatis.
    'standalone_dual_posting_payroll_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_PAYROLL_ENABLED', false),

    // Pencairan/cicilan pinjaman serta penarikan tabungan karyawan di luar payroll.
    'standalone_dual_posting_employee_balances_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_EMPLOYEE_BALANCES_ENABLED', false),

    // Supplier loan siap di-mirror setelah mapping akun disetujui. Sengaja OFF.
    'standalone_dual_posting_supplier_loans_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_SUPPLIER_LOANS_ENABLED', false),
    'standalone_payroll_wip_accounts' => ['cutting' => '1-151-01', 'sewing' => '1-151-02'],

    // Mirror pembayaran purchasing ke Akuntansi Baru selama masa trial.
    'standalone_dual_posting_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_ENABLED', true),
    'standalone_dual_posting_types' => ['dp', 'payment', 'dp_apply'],

    // Mirror penerimaan barang (GRN) ke Akuntansi Baru selama masa trial.
    'standalone_dual_posting_grn_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_GRN_ENABLED', true),

    // Mirror adjustment periodic dari stock opname ke Akuntansi Baru.
    'standalone_dual_posting_inventory_adjustment_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_INVENTORY_ADJUSTMENT_ENABLED', true),

    // Mirror saldo awal legacy ke Akuntansi Baru.
    'standalone_dual_posting_opening_balance_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_OPENING_BALANCE_ENABLED', true),

    // Pilot mirror produksi. Default OFF agar seluruh alur produksi tetap
    // berjalan dengan akuntansi legacy sampai pilot Cutting disetujui.
    'standalone_dual_posting_production_cutting_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_PRODUCTION_CUTTING_ENABLED', false),

    // Konsumsi BOM sewing_supply dilakukan saat QC Cutting, bukan saat
    // Sewing Pickup. Default OFF agar alur lama tetap aman selama pilot.
    'standalone_production_cutting_qc_auto_bom_enabled' => env('ACCOUNTING_STANDALONE_PRODUCTION_CUTTING_QC_AUTO_BOM_ENABLED', false),

    // Pilot mirror produksi Sewing Pickup. Default OFF sampai pilot Cutting dan
    // readiness Sewing Pickup disetujui secara terpisah.
    'standalone_dual_posting_production_sewing_pickup_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_PRODUCTION_SEWING_PICKUP_ENABLED', false),

    // Pilot auto-potong BOM kelengkapan Sewing Pickup. Default OFF: mode lama
    // dengan checklist/modal tetap menjadi perilaku utama sampai pilot lulus.
    'standalone_production_sewing_pickup_auto_bom_negative_enabled' => env('ACCOUNTING_STANDALONE_PRODUCTION_SEWING_PICKUP_AUTO_BOM_NEGATIVE_ENABLED', false),

    // Pilot mirror produksi Sewing Return OK/reject. Default OFF sampai pilot
    // Sewing Pickup dan readiness Sewing Return disetujui secara terpisah.
    'standalone_dual_posting_production_sewing_return_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_PRODUCTION_SEWING_RETURN_ENABLED', false),

    // Sewing Return dapat langsung posted ke WIP-FIN untuk dilanjutkan ke
    // Finishing. Gate QC tetap tersedia sebagai opsi konfigurasi.
    'sewing_return_requires_qc' => env('PRODUCTION_SEWING_RETURN_REQUIRES_QC', false),
    'sewing_return_to_wip_fin' => env('PRODUCTION_SEWING_RETURN_TO_WIP_FIN', true),
    // Jangan bypass kontrol supply pada transaksi produksi kecuali memang
    // diaktifkan eksplisit untuk environment testing.
    'sewing_return_allow_developer_supply_bypass' => env('PRODUCTION_SEWING_RETURN_ALLOW_DEVELOPER_SUPPLY_BYPASS', false),

    // Pilot mirror produksi Finishing. Default OFF sampai readiness dan pilot
    // Finishing disetujui secara terpisah.
    'standalone_dual_posting_production_finishing_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_PRODUCTION_FINISHING_ENABLED', false),

    // Pilot mirror HPP saat shipment/packing mengeluarkan barang jadi.
    // Default OFF agar posting legacy tetap menjadi satu-satunya posting
    // aktif sampai pilot shipment disetujui.
    'standalone_dual_posting_production_shipment_enabled' => env('ACCOUNTING_STANDALONE_DUAL_POSTING_PRODUCTION_SHIPMENT_ENABLED', false),

    // Audit produksi boleh menampilkan gap mirror tanpa menjadikannya gagal
    // sebelum pilot produksi benar-benar diaktifkan.
    'standalone_production_audit_required' => env('ACCOUNTING_STANDALONE_PRODUCTION_AUDIT_REQUIRED', false),

    // Transaksi legacy sebelum cutover tidak dibackfill dan ditandai sebagai
    // historical gap saat mirror standalone belum tersedia.
    'standalone_production_audit_cutover_at' => env('ACCOUNTING_STANDALONE_PRODUCTION_AUDIT_CUTOVER_AT'),
];
