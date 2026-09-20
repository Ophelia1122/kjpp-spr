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
        // Jumlah PROYEK UNIK yang memakai klien ini (2026-09-14, feedback user) —
        // sebelumnya peran dijumlahkan, jadi klien yang sekaligus Pemberi Tugas &
        // Pengguna Laporan di satu proyek terhitung 2.
        // Peran yang dihitung sama dengan halaman detail klien (show()).
        $projectsCount = \App\Models\Project::selectRaw('COUNT(*)')
            ->where(fn ($q) => $q->whereColumn('projects.instructing_client_id', 'clients.id')
                ->orWhereColumn('projects.client_id', 'clients.id')
                ->orWhereColumn('projects.approver_client_id', 'clients.id')
                ->orWhereColumn('projects.assignment_letter_recipient_client_id', 'clients.id')
                ->orWhereColumn('projects.assignment_letter_on_behalf_client_id', 'clients.id')
                ->orWhereExists(fn ($e) => $e->selectRaw('1')->from('project_intended_users')
                    ->whereColumn('project_intended_users.project_id', 'projects.id')
                    ->whereColumn('project_intended_users.client_id', 'clients.id'))
                ->orWhereExists(fn ($e) => $e->selectRaw('1')->from('invoices')
                    ->whereColumn('invoices.project_id', 'projects.id')
                    ->where(fn ($i) => $i->whereColumn('invoices.received_from_client_id', 'clients.id')
                        ->orWhereColumn('invoices.on_behalf_of_client_id', 'clients.id'))));

        // Urutan & jumlah per halaman seragam dengan List Project (2026-09-15).
        $currentSort = in_array($request->get('sort'), ['client_name', 'client_type', 'projects_count'], true) ? $request->get('sort') : 'client_name';
        $currentDir  = $request->get('dir') === 'desc' ? 'desc' : 'asc';

        $query = Client::select('clients.*')
            ->selectSub($projectsCount, 'projects_count')
            ->orderBy($currentSort, $currentDir)
            ->orderBy('client_name');

        // Cari juga di alamat — satu nama (mis. Bank Mandiri) bisa punya
        // banyak cabang yang dibedakan dari alamatnya.
        if ($request->filled('q')) {
            $keyword = '%' . $request->q . '%';
            $query->where(fn ($q) => $q->where('client_name', 'like', $keyword)
                                       ->orWhere('address', 'like', $keyword));
        }

        // Jumlah baris dikunci 15 (2026-09-20, feedback user).
        $perPage = 15;
        $clients = $query->paginate($perPage)->withQueryString();

        if ($request->ajax()) {
            return view('clients._results', compact('clients', 'currentSort', 'currentDir'));
        }

        return view('clients.index', compact('clients', 'currentSort', 'currentDir'));
    }

    /**
     * Detail klien + semua proyek yang melibatkannya, beserta perannya di tiap
     * proyek (2026-09-15, feedback user).
     */
    public function show(Client $client)
    {
        $id = $client->id;

        $projects = \App\Models\Project::with([
                'instructingClient', 'namedClient', 'intendedUsers:id',
                'invoices:id,project_id,received_from_client_id,on_behalf_of_client_id',
            ])
            ->where(fn ($q) => $q->where('instructing_client_id', $id)
                ->orWhere('client_id', $id)
                ->orWhere('approver_client_id', $id)
                ->orWhere('assignment_letter_recipient_client_id', $id)
                ->orWhere('assignment_letter_on_behalf_client_id', $id)
                ->orWhereHas('intendedUsers', fn ($s) => $s->where('clients.id', $id))
                ->orWhereHas('invoices', fn ($s) => $s->where('received_from_client_id', $id)->orWhere('on_behalf_of_client_id', $id)))
            ->orderByDesc('proposal_date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($p) use ($id) {
                $roles = array_keys(array_filter([
                    'Pemberi Tugas'        => $p->instructing_client_id === $id,
                    'Nama Klien'           => $p->client_id === $id,
                    'Pengguna Laporan'     => $p->intendedUsers->contains('id', $id),
                    'Pihak Menyetujui'     => $p->approver_client_id === $id,
                    'Surat Tugas'          => $p->assignment_letter_recipient_client_id === $id || $p->assignment_letter_on_behalf_client_id === $id,
                    'Pembayar Invoice'     => $p->invoices->contains(fn ($i) => $i->received_from_client_id === $id || $i->on_behalf_of_client_id === $id),
                ]));

                return ['project' => $p, 'roles' => $roles];
            });

        return view('clients.show', compact('client', 'projects'));
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
        $isUsedAsNamed       = $client->projectsAsNamedClient()->exists();

        if ($isUsedAsInstructing || $isUsedAsIntended || $isUsedAsApprover || $isUsedAsNamed) {
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
    /**
     * Halaman "Klien Baru" (2026-09-19, feedback user) — sebelumnya klien hanya
     * bisa dibuat lewat modal di form proposal (storeAjax), sehingga Database
     * Klien tak punya jalan menambah data.
     */
    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name'    => 'required|string|max:255',
            'client_type'    => 'required|in:Perbankan,Korporat,Perorangan',
            'address'        => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
        ]);

        $client = Client::create($validated);
        \App\Helpers\AuditLogger::record('client.created', "Menambahkan klien \"{$client->client_name}\"", $client);

        return redirect()->route('clients.show', $client)->with('success', 'Klien baru berhasil ditambahkan.');
    }

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
     * Klien yang mirip dengan nama (& alamat) yang sedang diketik di modal
     * "Tambah Klien Baru" (2026-09-15, feedback user). Tidak memblokir — hanya
     * saran. Nama dianggap mirip bila kemiripan >= 85% setelah normalisasi, atau
     * salah satunya memuat yang lain (mis. "Bank Mandiri" vs "Bank Mandiri KCP").
     */
    public function similar(Request $request)
    {
        $name    = Client::normalizeName($request->query('name'));
        $address = Client::normalizeAddress($request->query('address'));
        if (mb_strlen($name) < 3) {
            return response()->json([]);
        }

        $matches = Client::get(['id', 'client_name', 'client_type', 'address'])
            ->map(function (Client $c) use ($name, $address) {
                $cName     = Client::normalizeName($c->client_name);
                $nameScore = Client::similarity($name, $cName);
                // Salah ketik pada nama yang baru diketik sebagian, mis. "bank danamom"
                // vs "bank danamon indonesia" -> bandingkan dengan awal nama yang sepanjang ketikan.
                if (mb_strlen($name) >= 8 && mb_strlen($cName) > mb_strlen($name)) {
                    $nameScore = max($nameScore, Client::similarity($name, mb_substr($cName, 0, mb_strlen($name))));
                }
                if ($nameScore < 85 && mb_strlen($name) >= 5 && $cName !== ''
                    && (str_contains($cName, $name) || str_contains($name, $cName))) {
                    $nameScore = 85;
                }
                if ($nameScore < 85) {
                    return null;
                }

                $cAddress = Client::normalizeAddress($c->address);
                $checked  = $address !== '' && $cAddress !== '';

                return [
                    'id'              => $c->id,
                    'client_name'     => $c->client_name,
                    'client_type'     => $c->client_type,
                    'address'         => $c->address,
                    'name_score'      => (int) round($nameScore),
                    'address_checked' => $checked,
                    // Sama bila teksnya >= 85% mirip, atau >= 85% kata alamat yang lebih pendek
                    // ada di alamat lain (alamat diketik sebagian).
                    'same_address'    => $checked && max(Client::similarity($address, $cAddress), Client::tokenOverlap($address, $cAddress)) >= 85,
                ];
            })
            ->filter()
            ->sortByDesc(fn ($m) => ($m['same_address'] ? 1000 : 0) + $m['name_score'])
            ->take(5)
            ->values();

        return response()->json($matches);
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
