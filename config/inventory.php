<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sumber data daftar "Siap Jahit" / "Siap Finishing" (FASE 3)
    |--------------------------------------------------------------------------
    |
    | 'cache'  : pakai kolom cache di cutting_job_bundles (cut_wip_qty / wip_qty).
    |            Perilaku lama. Bisa menampilkan stok hantu jika cache > ledger.
    |
    | 'ledger' : turunkan kesiapan langsung dari saldo ledger per-bundle
    |            (inventory_mutations.cutting_job_bundle_id). Bebas hantu karena
    |            = stok fisik. Costing/HPP tidak terpengaruh (hanya pembacaan).
    |
    | Toggle via env INVENTORY_READINESS_SOURCE. Default 'cache' supaya aman;
    | set 'ledger' untuk mengaktifkan Fase 3. Rollback = kembalikan ke 'cache'.
    |
    */
    'readiness_source' => env('INVENTORY_READINESS_SOURCE', 'cache'),

    /*
    |--------------------------------------------------------------------------
    | Kode gudang WIP per tahap
    |--------------------------------------------------------------------------
    */
    'warehouses' => [
        'wip_cut' => 'WIP-CUT',
        'wip_sew' => 'WIP-SEW',
        'wip_fin' => 'WIP-FIN',
    ],

];
