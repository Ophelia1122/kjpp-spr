<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Menyimpan klien baru dari POP-UP MODAL (tanpa reload halaman).
     * Dipanggil via fetch/AJAX dari proposals/create.blade.php.
     * Mengembalikan JSON supaya bisa langsung dipakai untuk mengisi
     * combobox klien di form proposal tanpa reload.
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
            'client'  => $client, // dipakai JS untuk langsung select klien baru ini
        ]);
    }

    /**
     * Endpoint pencarian AJAX untuk combobox "Nama Klien" di form proposal.
     * Dipanggil tiap kali user mengetik (debounced) di
     * resources/views/proposals/create.blade.php.
     *
     * Query: GET /clients/search?q=uob
     * Return: [{id, client_name, client_type, address}, ...]
     */
    public function search(Request $request)
    {
        $keyword = trim((string) $request->get('q', ''));

        // Kalau keyword kosong, kembalikan daftar klien terbaru (max 10)
        // supaya combobox tidak kosong melompong saat pertama kali difokus.
        $query = Client::query();

        if ($keyword !== '') {
            $query->where('client_name', 'like', "%{$keyword}%");
        } else {
            $query->latest();
        }

        $clients = $query
            ->limit(15)
            ->get(['id', 'client_name', 'client_type', 'address']);

        return response()->json($clients);
    }
}
