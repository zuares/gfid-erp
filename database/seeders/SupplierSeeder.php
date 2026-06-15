<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code'     => 'BDY',
                'name'     => '@ JAKET & SWEATER @ BADAI ATO & TONI',
                'phone'    => '62895339443198',
                'email'    => null,
                'address'  => 'Jl Inpres',
                'active'   => 1,
                'po_types' => ['finished_good'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '3791734884', 'account_holder' => 'Yanto', 'notes' => null],
                ],
            ],
            [
                'code'     => 'OHN',
                'name'     => '@ KAIN & RIB & ACC @ Haji Ohan Cikeueus',
                'phone'    => '08211222333',
                'email'    => null,
                'address'  => null,
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '3791336153', 'account_holder' => 'Ohan Burhanudin', 'notes' => null],
                ],
            ],
            [
                'code'     => 'FRS',
                'name'     => '@ KAIN & RIB @ Fransyino Textile',
                'phone'    => null,
                'email'    => null,
                'address'  => 'Jl. Cikeueus Cigondewah Hilir',
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '3791511273', 'account_holder' => 'Ririn Anggraeni', 'notes' => null],
                ],
            ],
            [
                'code'     => 'DDN',
                'name'     => '@ KAIN & RIB @ HJ Didin Cigondewah',
                'phone'    => null,
                'email'    => null,
                'address'  => 'Jl. Cigondewah Kaler Deket Stopan Taman Holis',
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '1571304827', 'account_holder' => 'Didin', 'notes' => null],
                ],
            ],
            [
                'code'     => 'ORG',
                'name'     => '@ KAIN & RIB @ ORIGAMI TEXTILE',
                'phone'    => '6282284964421',
                'email'    => null,
                'address'  => 'Jl Cijantung Cigondewah Hilir',
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '8390324333', 'account_holder' => 'Syafendri', 'notes' => null],
                ],
            ],
            [
                'code'     => 'TPL',
                'name'     => '@ KAIN & RIB @ TOPLIS JAYA',
                'phone'    => '081234567890',
                'email'    => null,
                'address'  => 'Palembang, Sumatera Selatan',
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '3795028000', 'account_holder' => 'Toplis Jaya Textile', 'notes' => null],
                ],
            ],
            [
                'code'     => 'BRY',
                'name'     => '@ KARET & RIB @ Toko Briyan',
                'phone'    => null,
                'email'    => null,
                'address'  => null,
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '3790945106', 'account_holder' => 'Mochamad Briyan', 'notes' => null],
                ],
            ],
            [
                'code'     => 'JFM',
                'name'     => '@ KARET & TALI @ Jhony F Man',
                'phone'    => '6281322398603',
                'email'    => null,
                'address'  => null,
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '1561398513', 'account_holder' => 'Joni F Man Ir', 'notes' => null],
                ],
            ],
            [
                'code'     => 'SRI',
                'name'     => '@ PLASTIK & TERMAL @ Sri Haryati',
                'phone'    => '62882000345979',
                'email'    => null,
                'address'  => 'Jl Cikeueus',
                'active'   => 1,
                'po_types' => ['finished_good'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '3790792296', 'account_holder' => 'Sri Haryati', 'notes' => null],
                ],
            ],
            [
                'code'     => 'RDN',
                'name'     => '@ SHOT @ Lia & Dede Ridwan',
                'phone'    => null,
                'email'    => null,
                'address'  => null,
                'active'   => 1,
                'po_types' => ['finished_good'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '416101040202535', 'account_holder' => 'Dede Ridwan Komara', 'notes' => null],
                ],
            ],
            [
                'code'     => 'INY',
                'name'     => '@ TALIKUR @ Inayah Ragil',
                'phone'    => '6287848733992',
                'email'    => null,
                'address'  => 'Jl Cikeueus Cigondewah Hilir',
                'active'   => 1,
                'po_types' => ['material'],
                'banks'    => [
                    ['bank_name' => 'BCA', 'account_number' => '8930401685', 'account_holder' => 'Inayah Rahmiatul', 'notes' => null],
                ],
            ],
        ];

        foreach ($suppliers as $row) {
            $banks = $row['banks'] ?? [];
            unset($row['banks']);

            $supplier = Supplier::updateOrCreate(
                ['code' => $row['code']],
                $row,
            );

            foreach ($banks as $bank) {
                SupplierBankAccount::updateOrCreate(
                    [
                        'supplier_id'    => $supplier->id,
                        'account_number' => $bank['account_number'],
                    ],
                    array_merge($bank, ['supplier_id' => $supplier->id]),
                );
            }
        }
    }
}
