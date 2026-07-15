<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Menyimpan klien baru dari POP-UP MODAL (tanpa reload halaman).
     * Dipanggil via fetch/AJAX dari create_proposal.blade.php.
     * Mengembalikan JSON supaya bisa langsung dipakai untuk mengisi
     * <select> klien di form proposal tanpa reload.
     */
    public function storeAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name'    => 'required|string|max:255',
            'client_type'    => 'required|in:Perbankan,Korporat,Perorangan',
            'address'        => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $client = Client::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Klien baru berhasil ditambahkan.',
            'client'  => $client, // dipakai JS untuk append <option> baru
        ]);
    }

    /**
     * Endpoint pendukung: cari klien untuk autocomplete/select2 (opsional).
     */
    public function search(Request $request)
    {
        $keyword = $request->get('q', '');

        $clients = Client::where('client_name', 'like', "%{$keyword}%")
            ->limit(20)
            ->get(['id', 'client_name', 'client_type']);

        return response()->json($clients);
    }
}
