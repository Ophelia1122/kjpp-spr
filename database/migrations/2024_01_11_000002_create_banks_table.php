<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * banks — master rekening bank KJPP (CRUD khusus Administrator).
 *
 * Proposal menyimpan `bank_id` yang dipilih; Invoice + blok "Rekening Bank"
 * pada proposal .docx memakai rekening tsb. Bila proposal tidak memilih bank,
 * dipakai bank ber-`is_default` (fallback terakhir: config('kjpp.bank_account')).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('branch')->nullable();        // Nama Cabang (opsional)
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Isi 1 baris awal dari config lama supaya proposal/invoice yang sudah
        // ada tetap menampilkan rekening yang sama tanpa perlu di-set manual.
        $acc = config('kjpp.bank_account');
        if (is_array($acc) && ! empty($acc['account_number'])) {
            DB::table('banks')->insert([
                'bank_name'      => $acc['bank_name'] ?? '-',
                'branch'         => null,
                'account_number' => $acc['account_number'],
                'account_name'   => $acc['account_name'] ?? config('kjpp.company_name'),
                'is_default'     => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
