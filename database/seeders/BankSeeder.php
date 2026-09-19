<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

/**
 * Master rekening bank KJPP SPR ("Data Rekening"). Idempotent — kunci pada
 * account_number. Default = Bank Mandiri (bisa diganti lewat menu Kelola
 * Rekening Bank).
 */
class BankSeeder extends Seeder
{
    private const DEFAULT_ACC = '070-00-1324576-1';   // Bank Mandiri

    private const ROWS = [
        ['bank_name' => 'Bank BRI',                 'branch' => 'Parung-Pamulang',            'account_number' => '0812.01.000039.56.3'],
        ['bank_name' => 'Bank BCA',                 'branch' => 'Graha Inti Fauzi',           'account_number' => '3753022941'],
        ['bank_name' => 'Bank Bukopin',             'branch' => 'Capem. Kebayoran Baru',      'account_number' => '100-1558-443'],
        ['bank_name' => 'Bank Maybank',             'branch' => 'Graha Simatupang',           'account_number' => '2142 7562 26'],
        ['bank_name' => 'Bank Jtrust',              'branch' => null,                         'account_number' => '10014-111-79'],
        ['bank_name' => 'Bank Oke Indonesia',       'branch' => 'Cabang Sudirman',            'account_number' => '1101-219-0000-5011'],
        ['bank_name' => 'Bank UOB',                 'branch' => null,                         'account_number' => '3.143.005.575'],
        ['bank_name' => 'Bank BNI',                 'branch' => 'KNL Mampang',                'account_number' => '0152 094 417'],
        ['bank_name' => 'Bank NISP',                'branch' => 'Kantor Mampang',             'account_number' => '416 800000 695'],
        ['bank_name' => 'Bank Panin',              'branch' => 'TB. Simatupang',             'account_number' => '116 500 8711'],
        ['bank_name' => 'Bank BSI',                 'branch' => 'Cilandak',                   'account_number' => '812 561 7250'],
        ['bank_name' => 'Panin Dubai Syariah',      'branch' => null,                         'account_number' => '7301121479'],
        ['bank_name' => 'Bank Danamon',             'branch' => 'Jakarta Warung Buncit',      'account_number' => '003583605781'],
        ['bank_name' => 'Bank Mandiri',             'branch' => 'Wisma Danantara Indonesia',  'account_number' => '070-00-1324576-1'],
        ['bank_name' => 'Bank Jtrust (Appraisal)',  'branch' => null,                         'account_number' => '102028015360008'],
        ['bank_name' => 'Bank OCBC',                'branch' => null,                         'account_number' => '416800000695'],
    ];

    public function run(): void
    {
        $accountName = config('kjpp.bank_account.account_name', config('kjpp.company_name'));

        foreach (self::ROWS as $row) {
            Bank::updateOrCreate(
                ['account_number' => $row['account_number']],
                [
                    'bank_name'    => $row['bank_name'],
                    'branch'       => $row['branch'],
                    'account_name' => $accountName,
                    'is_default'   => $row['account_number'] === self::DEFAULT_ACC,
                ]
            );
        }

        // Pastikan hanya SATU default.
        Bank::where('account_number', '!=', self::DEFAULT_ACC)->update(['is_default' => false]);
    }
}
