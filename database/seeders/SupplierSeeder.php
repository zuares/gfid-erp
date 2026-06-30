<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
            DB::table('suppliers')->updateOrInsert(['code' => 'TPL'], [
                'code' => 'TPL',
                'name' => 'Toplis Jaya',
                'phone' => '081234567890',
                'email' => null,
                'address' => 'Palembang, Sumatera Selatan',
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'ORG'], [
                'code' => 'ORG',
                'name' => 'Origami Textile',
                'phone' => '6282284964421',
                'email' => null,
                'address' => 'Jl Cijantung Cigondewah Hilir',
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'DDN'], [
                'code' => 'DDN',
                'name' => 'HJ Didin Cigondewah',
                'phone' => null,
                'email' => null,
                'address' => 'Jl. Cigondewah Kaler Deket Stopan Taman Holis',
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'RDN'], [
                'code' => 'RDN',
                'name' => 'Lia & Dede Ridwan',
                'phone' => null,
                'email' => null,
                'address' => null,
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["finished_good"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'FRS'], [
                'code' => 'FRS',
                'name' => 'Fransyino Textile',
                'phone' => null,
                'email' => null,
                'address' => 'Jl. Cikeueus Cigondewah Hilir',
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'OHN'], [
                'code' => 'OHN',
                'name' => 'Haji Ohan Cikeueus',
                'phone' => '08211222333',
                'email' => null,
                'address' => null,
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'BDY'], [
                'code' => 'BDY',
                'name' => 'Badai Ato & Toni',
                'phone' => '62895339443198',
                'email' => null,
                'address' => 'Jl Inpres',
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["finished_good"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'BRY'], [
                'code' => 'BRY',
                'name' => 'Toko Briyan',
                'phone' => null,
                'email' => null,
                'address' => null,
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'JFM'], [
                'code' => 'JFM',
                'name' => 'Jhony F Man',
                'phone' => '6281322398603',
                'email' => null,
                'address' => null,
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'SRI'], [
                'code' => 'SRI',
                'name' => 'Sri Haryati',
                'phone' => '62882000345979',
                'email' => null,
                'address' => 'Jl Cikeueus',
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["finished_good"]',
            ]);
            DB::table('suppliers')->updateOrInsert(['code' => 'INY'], [
                'code' => 'INY',
                'name' => 'Inayah Ragil',
                'phone' => '6287848733992',
                'email' => null,
                'address' => 'Jl Cikeueus Cigondewah Hilir',
                'type' => 'supplier',
                'active' => 1,
                'po_types' => '["material"]',
            ]);

            $supId = DB::table('suppliers')->where('code', 'BDY')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '3791734884'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '3791734884', 'account_holder' => 'Yanto', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'OHN')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '3791336153'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '3791336153', 'account_holder' => 'Ohan Burhanudin', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'FRS')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '3791511273'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '3791511273', 'account_holder' => 'Ririn Anggraeni', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'DDN')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '1571304827'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '1571304827', 'account_holder' => 'Didin', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'ORG')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '8390324333'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '8390324333', 'account_holder' => 'Syafendri', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'TPL')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '3795028000'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '3795028000', 'account_holder' => 'Toplis Jaya Textile', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'BRY')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '3790945106'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '3790945106', 'account_holder' => 'Mochamad Briyan', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'JFM')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '1561398513'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '1561398513', 'account_holder' => 'Joni F Man Ir', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'SRI')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '3790792296'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '3790792296', 'account_holder' => 'Sri Haryati', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'RDN')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '416101040202535'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '416101040202535', 'account_holder' => 'Dede Ridwan Komara', 'notes' => null]
            );
            $supId = DB::table('suppliers')->where('code', 'INY')->value('id');
            if ($supId) DB::table('supplier_bank_accounts')->updateOrInsert(
                ['supplier_id' => $supId, 'account_number' => '8930401685'],
                ['supplier_id' => $supId, 'bank_name' => 'BCA', 'account_number' => '8930401685', 'account_holder' => 'Inayah Rahmiatul', 'notes' => null]
            );
    }
}
