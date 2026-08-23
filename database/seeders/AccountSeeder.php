<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
            DB::table('accounts')->updateOrInsert(['code' => '1101'], [
                'code' => '1101',
                'name' => 'Kas Tunai',
                'type' => 'asset',
                'is_cash' => 1,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1111'], [
                'code' => '1111',
                'name' => 'Bank Jago (Bisnis)',
                'type' => 'asset',
                'is_cash' => 1,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1112'], [
                'code' => '1112',
                'name' => 'Bank BCA (Operasional)',
                'type' => 'asset',
                'is_cash' => 1,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1201'], [
                'code' => '1201',
                'name' => 'Persediaan Bahan Baku',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1202'], [
                'code' => '1202',
                'name' => 'Persediaan WIP',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1203'], [
                'code' => '1203',
                'name' => 'Persediaan Barang Jadi',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1204'], [
                'code' => '1204',
                'name' => 'Persediaan Barang Cacat',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1205'], [
                'code' => '1205',
                'name' => 'Persediaan Packaging',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1301'], [
                'code' => '1301',
                'name' => 'Piutang Dagang',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1302'], [
                'code' => '1302',
                'name' => 'Piutang Marketplace',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1401'], [
                'code' => '1401',
                'name' => 'PPN Masukan',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1151'], [
                'code' => '1151',
                'name' => 'Uang Muka Pembelian',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '2101'], [
                'code' => '2101',
                'name' => 'Hutang Dagang',
                'type' => 'liability',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '3101'], [
                'code' => '3101',
                'name' => 'Modal Pemilik',
                'type' => 'equity',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '3301'], [
                'code' => '3301',
                'name' => 'Prive Pemilik',
                'type' => 'equity',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '4101'], [
                'code' => '4101',
                'name' => 'Penjualan',
                'type' => 'revenue',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '4201'], [
                'code' => '4201',
                'name' => 'Retur Penjualan',
                'type' => 'revenue',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '5101'], [
                'code' => '5101',
                'name' => 'Harga Pokok Penjualan (HPP)',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6101'], [
                'code' => '6101',
                'name' => 'Biaya Operasional Umum',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6102'], [
                'code' => '6102',
                'name' => 'Biaya Transport / Ongkir',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6103'], [
                'code' => '6103',
                'name' => 'Biaya Gaji Operasional',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6104'], [
                'code' => '6104',
                'name' => 'Biaya ATK',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6105'], [
                'code' => '6105',
                'name' => 'Biaya Pemeliharaan Mesin',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6110'], [
                'code' => '6110',
                'name' => 'Biaya Packing',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6115'], [
                'code' => '6115',
                'name' => 'Selisih Stock Opname',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6116'], [
                'code' => '6116',
                'name' => 'Koreksi Persediaan Legacy',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6120'], [
                'code' => '6120',
                'name' => 'Kerugian Produksi / Reject',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '6201'], [
                'code' => '6201',
                'name' => 'Biaya Marketplace',
                'type' => 'expense',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
            DB::table('accounts')->updateOrInsert(['code' => '1305'], [
                'code' => '1305',
                'name' => 'Piutang Supplier (Retur / Klaim Pembelian)',
                'type' => 'asset',
                'is_cash' => 0,
                'is_active' => 1,
            ]);
    }
}
