<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Data klien dibuat generic (tidak ada kolom "role") supaya bisa
     * dipakai fleksibel: kadang sebagai Pemberi Tugas, kadang sebagai
     * Pengguna Laporan — tergantung relasi yang dipakai di ProjectSeeder.
     */
    public function run(): void
    {
        Client::updateOrCreate(
            ['client_name' => 'PT Bank UOB Indonesia'],
            [
                'client_type'    => 'Perbankan',
                'address'        => 'Jl. M.H. Thamrin No. 10, Jakarta Pusat',
                'contact_person' => 'Budi Santoso',
                'phone'          => '021-2350-6000',
                'email'          => 'appraisal.desk@uob.co.id',
            ]
        );

        Client::updateOrCreate(
            ['client_name' => 'PT Petrona Inti Chemindo'],
            [
                'client_type'    => 'Korporat',
                'address'        => 'Kawasan Industri MM2100, Cikarang Barat, Bekasi',
                'contact_person' => 'Sinta Wijaya',
                'phone'          => '021-8998-1234',
                'email'          => 'finance@petronachemindo.co.id',
            ]
        );

        Client::updateOrCreate(
            ['client_name' => 'PT Bank Mandiri (Persero) Tbk'],
            [
                'client_type'    => 'Perbankan',
                'address'        => 'Jl. Jenderal Gatot Subroto Kav. 36-38, Jakarta Selatan',
                'contact_person' => 'Rina Kartika',
                'phone'          => '021-524-5516',
                'email'          => 'appraisal@bankmandiri.co.id',
            ]
        );

        Client::updateOrCreate(
            ['client_name' => 'PT Graha Sentosa Abadi'],
            [
                'client_type'    => 'Korporat',
                'address'        => 'Ruko Sentra Bisnis Blok C No. 5, Tangerang Selatan',
                'contact_person' => 'Hendra Wibowo',
                'phone'          => '021-7455-9090',
                'email'          => 'hendra@grahasentosa.co.id',
            ]
        );

        Client::updateOrCreate(
            ['client_name' => 'Ahmad Fauzi'],
            [
                'client_type'    => 'Perorangan',
                'address'        => 'Jl. Kemang Selatan No. 22, Jakarta Selatan',
                'contact_person' => 'Ahmad Fauzi',
                'phone'          => '0812-3456-7890',
                'email'          => 'ahmad.fauzi@gmail.com',
            ]
        );

        Client::updateOrCreate(
            ['client_name' => 'PT Multi Guna Sejahtera'],
            [
                'client_type'    => 'Korporat',
                'address'        => 'Jl. Raya Serpong KM 8, Tangerang',
                'contact_person' => 'Dewi Lestari',
                'phone'          => '021-5314-7788',
                'email'          => 'dewi@multigunasejahtera.co.id',
            ]
        );
    }
}
