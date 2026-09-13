<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Halaman daftar klien (menu "Database Klien" di sidebar).
     */
    public function index(Request $request)
    {
        $query = Client::withCount(['projectsAsInstructingClient', 'projectsAsIntendedUser', 'projectsAsApprover'])
            ->orderBy('client_name');

        // Cari juga di alamat — satu nama (mis. Bank Mandiri) bisa punya
        // banyak cabang yang dibedakan dari alamatnya.
        if ($request->filled('q')) {
            $keyword = '%' . $request->q . '%';
            $query->where(fn ($q) => $q->where('client_name', 'like', $keyword)
                                       ->orWhere('address', 'like', $keyword));
        }

        $clients = $query->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('clients._results', compact('clients'));
        }

        return view('clients.index', compact('clients'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'client_name'    => 'required|string|max:255',
            'client_type'    => 'required|in:Perbankan,Korporat,Perorangan',
            'address'        => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
        ]);

        $client->update($validated);
        \App\Helpers\AuditLogger::record('client.updated', "Mengubah data klien \"{$client->client_name}\"", $client);
        return redirect()
            ->route('clients.index')
            ->with('success', "Data klien \"{$client->client_name}\" berhasil diperbarui.");
    }

    /**
     * GUARD KETAT: klien yang masih terpakai di proyek manapun (sebagai
     * Pemberi Tugas ATAU Pengguna Laporan) TIDAK BOLEH dihapus — akan
     * merusak riwayat proposal/invoice yang sudah tercetak. Admin harus
     * ganti dulu keterkaitannya kalau memang klien ini duplikat/salah
     * input, baru boleh dihapus.
     */
    public function destroy(Client $client)
    {
        $isUsedAsInstructing = $client->projectsAsInstructingClient()->exists();
        $isUsedAsIntended    = $client->projectsAsIntendedUser()->exists();
        $isUsedAsApprover    = $client->projectsAsApprover()->exists();

        if ($isUsedAsInstructing || $isUsedAsIntended || $isUsedAsApprover) {
            return back()->with('error',
                "Klien \"{$client->client_name}\" tidak dapat dihapus karena masih terhubung dengan satu atau lebih proyek."
            );
        }

        $clientName = $client->client_name;
        \App\Helpers\AuditLogger::record('client.deleted', "Menghapus klien \"{$clientName}\"", $client);
        $client->delete();

        return back()->with('success', "Klien \"{$clientName}\" berhasil dihapus.");
    }

    /**
     * Menyimpan klien baru dari POP-UP MODAL di form proposal (AJAX).
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
            'client'  => $client,
        ]);
    }

    /**
     * Endpoint pencarian AJAX untuk combobox "Nama Klien" di form proposal.
     */
    public function search(Request $request)
    {
        $keyword = trim((string) $request->get('q', ''));

        $query = Client::query();

        if ($keyword !== '') {
            // Nama ATAU alamat — supaya cabang tertentu (mis. "Mandiri Kuningan")
            // bisa langsung dicari dari lokasinya.
            $query->where(fn ($q) => $q->where('client_name', 'like', "%{$keyword}%")
                                       ->orWhere('address', 'like', "%{$keyword}%"));
        } else {
            $query->latest();
        }

        $clients = $query->limit(15)->get(['id', 'client_name', 'client_type', 'address']);

        return response()->json($clients);
    }
}
