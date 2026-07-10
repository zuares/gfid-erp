<?php

namespace App\Observers;

use App\Models\PurchaseReturnLine;
use Illuminate\Validation\ValidationException;

class PurchaseReturnLineObserver
{
    /**
     * Handle the PurchaseReturnLine "created" event.
     */
    public function created(PurchaseReturnLine $purchaseReturnLine): void
    {
        //
    }

    /**
     * Handle the PurchaseReturnLine "updated" event.
     */
    public function updated(PurchaseReturnLine $purchaseReturnLine): void
    {
        //
    }

    /**
     * Handle the PurchaseReturnLine "deleting" event.
     */
    public function deleting(PurchaseReturnLine $line): void
    {
        if ($line->allocated_qty > 0.0001) {
            throw ValidationException::withMessages([
                'line' => 'Baris retur masih memiliki alokasi stok. Batalkan atau lepaskan alokasi terlebih dahulu.',
            ]);
        }
    }

    /**
     * Handle the PurchaseReturnLine "restored" event.
     */
    public function restored(PurchaseReturnLine $purchaseReturnLine): void
    {
        //
    }

    /**
     * Handle the PurchaseReturnLine "force deleted" event.
     */
    public function forceDeleted(PurchaseReturnLine $purchaseReturnLine): void
    {
        //
    }
}
