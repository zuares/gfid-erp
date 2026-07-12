<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class FixChannelCodes extends Command
{
    protected $signature = 'app:fix-channel-codes';
    protected $description = 'Standarisasi dan gabungkan duplikat kode channel (SHP -> shopee, TTK -> tiktok)';

    public function handle()
    {
        $this->info("Memulai standarisasi channel code di database...");

        // Peta standarisasi
        $map = [
            'SHP' => 'shopee',
            'SHOPEE' => 'shopee',
            'TTK' => 'tiktok',
            'TIKTOK' => 'tiktok',
            'OFFL' => 'offline',
            'OFFLINE' => 'offline',
        ];

        DB::beginTransaction();
        try {
            $channels = Channel::all();

            foreach ($channels as $channel) {
                $originalCode = $channel->code;
                
                // Cek apakah perlu distandarisasi (huruf besar/kecil atau alias)
                $targetCode = null;
                if (isset($map[strtoupper($originalCode)])) {
                    $targetCode = $map[strtoupper($originalCode)];
                } else if ($originalCode !== strtolower($originalCode)) {
                    $targetCode = strtolower($originalCode);
                }

                if ($targetCode && $targetCode !== $originalCode) {
                    $this->info("Menemukan channel ID {$channel->id} ('{$originalCode}'). Target standarisasi: '{$targetCode}'.");

                    // Cek apakah channel dengan target code sudah ada
                    $existingTarget = Channel::where('code', $targetCode)
                        ->where('id', '!=', $channel->id)
                        ->first();

                    if ($existingTarget) {
                        $this->warn(" -> Channel dengan kode '{$targetCode}' sudah ada (ID {$existingTarget->id}). Menggabungkan toko-toko...");
                        
                        // Pindahkan toko-toko ke channel target
                        $updated = Store::where('channel_id', $channel->id)
                            ->update(['channel_id' => $existingTarget->id]);
                            
                        $this->info("    Memindahkan {$updated} toko ke channel ID {$existingTarget->id}.");
                        
                        // Hapus channel lama yang duplikat
                        $channel->delete();
                        $this->warn("    Channel ID {$channel->id} ('{$originalCode}') telah dihapus karena duplikat.");
                    } else {
                        // Ubah namanya saja karena belum ada konflik
                        $channel->update(['code' => $targetCode]);
                        $this->info(" -> Berhasil mengubah kode channel ID {$channel->id} menjadi '{$targetCode}'.");
                    }
                }
            }

            DB::commit();
            $this->info("Selesai! Database Channel dan Store sudah terstandarisasi dengan rapi tanpa error.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan: " . $e->getMessage());
        }
    }
}
