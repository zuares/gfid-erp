<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixStoreCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:reset-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan data otentikasi toko yang gagal didekripsi akibat perubahan APP_KEY';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan kredensial toko...');

        $updated = DB::table('stores')->update([
            'credentials' => null,
            'token_expires_at' => null,
        ]);

        $this->info("Berhasil mereset data otentikasi untuk {$updated} toko.");
        $this->line('Semua toko sekarang berstatus "Belum Terhubung" dan siap dihubungkan ulang tanpa error.');
    }
}
