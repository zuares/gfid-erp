<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModuleAccess extends Model
{
    public const MODULES = [
        'dashboard' => 'Dashboard',
        'master' => 'Master Data',
        'inventory' => 'Inventory',
        'production' => 'Production',
        'sales' => 'Sales',
        'purchasing' => 'Purchasing',
        'marketplace' => 'Marketplace',
        'imports' => 'Imports',
        'accounting' => 'Accounting',
        'payroll' => 'Payroll',
        'costing' => 'Costing & HPP',
    ];

    /**
     * Ikon Bootstrap Icons + deskripsi singkat tiap modul (untuk tooltip di UI).
     *
     * @var array<string, array{icon: string, desc: string}>
     */
    public const MODULE_META = [
        'dashboard'   => ['icon' => 'bi-house',        'desc' => 'Ringkasan & halaman utama'],
        'master'      => ['icon' => 'bi-collection',   'desc' => 'Item, kategori, supplier, customer, BOM'],
        'inventory'   => ['icon' => 'bi-box-seam',     'desc' => 'Stok, opname, transfer, penyesuaian'],
        'production'  => ['icon' => 'bi-scissors',     'desc' => 'Cutting, sewing, finishing, QC, packing'],
        'sales'       => ['icon' => 'bi-truck',        'desc' => 'Pengiriman, retur, invoice, laporan jual'],
        'purchasing'  => ['icon' => 'bi-cart3',        'desc' => 'Purchase order & penerimaan barang'],
        'marketplace' => ['icon' => 'bi-shop',         'desc' => 'Order marketplace, rekonsiliasi, profit'],
        'imports'     => ['icon' => 'bi-upload',       'desc' => 'Import data order & income marketplace'],
        'accounting'  => ['icon' => 'bi-journal-text', 'desc' => 'Jurnal, kas, laporan keuangan'],
        'payroll'     => ['icon' => 'bi-cash-coin',    'desc' => 'Penggajian & upah borongan'],
        'costing'     => ['icon' => 'bi-calculator',   'desc' => 'HPP & biaya produksi'],
    ];

    /**
     * Pengelompokan modul untuk tampilan checklist yang lebih rapi.
     *
     * @var array<string, array{label: string, modules: string[]}>
     */
    public const MODULE_GROUPS = [
        'core'       => ['label' => 'Inti',        'modules' => ['dashboard', 'master']],
        'operations' => ['label' => 'Operasional', 'modules' => ['inventory', 'production', 'purchasing']],
        'sales'      => ['label' => 'Penjualan',   'modules' => ['sales', 'marketplace', 'imports']],
        'finance'    => ['label' => 'Keuangan',    'modules' => ['accounting', 'payroll', 'costing']],
    ];

    protected $fillable = [
        'user_id',
        'module',
        'can_access',
        'updated_by',
    ];

    protected $casts = [
        'can_access' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
