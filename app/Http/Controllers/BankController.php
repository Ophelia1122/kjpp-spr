<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD master rekening bank. Route digrup di bawah permission:banks.manage
 * (default hanya Administrator).
 */
class BankController extends Controller
{
    public function index()
    {
        $banks = Bank::withCount('projects')
            ->orderByDesc('is_default')
            ->orderBy('bank_name')
            ->get();

        return view('banks.index', compact('banks'));
    }

    public function create()
    {
        return view('banks.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $bank = Bank::create(['is_default' => false] + $data);
        $this->applyDefault($bank, $request->boolean('is_default'));

        AuditLogger::record('bank.created', "Menambah rekening bank \"{$bank->bank_name} - {$bank->account_number}\"", $bank);

        return redirect()->route('banks.index')
            ->with('success', "Rekening \"{$bank->bank_name}\" berhasil ditambahkan.");
    }

    public function edit(Bank $bank)
    {
        return view('banks.edit', compact('bank'));
    }

    public function update(Request $request, Bank $bank)
    {
        $bank->update($this->validated($request));
        $this->applyDefault($bank, $request->boolean('is_default'));

        AuditLogger::record('bank.updated', "Mengubah rekening bank \"{$bank->bank_name} - {$bank->account_number}\"", $bank);

        return redirect()->route('banks.index')
            ->with('success', "Rekening \"{$bank->bank_name}\" berhasil diperbarui.");
    }

    public function destroy(Bank $bank)
    {
        if ($bank->projects()->exists()) {
            return back()->with('error',
                "Rekening \"{$bank->bank_name}\" tidak dapat dihapus karena masih dipakai di satu atau lebih proposal.");
        }

        $name = $bank->bank_name;
        AuditLogger::record('bank.deleted', "Menghapus rekening bank \"{$name} - {$bank->account_number}\"", $bank);
        $bank->delete();

        return back()->with('success', "Rekening \"{$name}\" berhasil dihapus.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'bank_name'      => 'required|string|max:255',
            'branch'         => 'nullable|string|max:255',
            'account_number' => 'required|string|max:100',
            'account_name'   => 'required|string|max:255',
        ]);
    }

    /**
     * Jadikan $bank sebagai default (menonaktifkan default lama). Kalau
     * $makeDefault false tapi $bank sedang jadi satu-satunya default, biarkan
     * — selalu diusahakan ada 1 default bila ada minimal 1 bank.
     */
    private function applyDefault(Bank $bank, bool $makeDefault): void
    {
        if ($makeDefault && ! $bank->is_default) {
            DB::transaction(function () use ($bank) {
                Bank::where('id', '!=', $bank->id)->update(['is_default' => false]);
                $bank->update(['is_default' => true]);
            });
        } elseif (! $makeDefault && Bank::where('is_default', true)->doesntExist()) {
            // Tak ada default sama sekali -> pakai bank tertua sebagai default.
            Bank::oldest()->first()?->update(['is_default' => true]);
        }
    }
}
