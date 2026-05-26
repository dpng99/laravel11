<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Indikator;
use App\Models\Bidang;
use App\Models\Saspro;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

class KeloladataController extends Controller
{
    /**
     * Menampilkan halaman utama Kelola Data.
     */
    public function index(Request $request)
    {
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $tahun = session('tahun_terpilih');

        // Optimasi: Ambil data tabel bidang yang diperlukan saja
        $bidangs = Bidang::select(['id', 'bidang_nama', 'bidang_level', 'bidang_lokasi', 'rumpun', 'hide'])
            ->orderBy('bidang_lokasi')
            ->orderBy('bidang_level')
            ->orderBy('rumpun')
            ->orderBy('id')
            ->paginate(10);
            
        $bidangall = Bidang::select('id', 'bidang_nama', 'rumpun')
            ->where('hide', 0)
            ->get();

        $indikators = Indikator::with(['bidangById', 'saspro'])
            ->orderBy('tahun', 'desc')
            ->orderBy('link', 'asc')
            ->paginate(10);

        $saspros = Saspro::with('bidang')
            ->orderBy('tahun', 'desc')
            ->orderBy('link', 'asc')
            ->paginate(10); 

        // Optimasi: Hindari overfetching. Jika hanya untuk dropdown UI, cukup ambil ID & Nama.
        $sasproAll = Saspro::select('id', 'saspro_nama', 'tahun', 'link')
            ->orderBy('tahun', 'desc')
            ->orderBy('link', 'asc')
            ->get();

        return Inertia::render('KelolaData', [
            'tahun'      => $tahun,
            'bidangs'    => $bidangs,
            'saspros'    => $saspros,
            'bidangall'  => $bidangall,
            'indikators' => $indikators,
            'sasproAll'  => $sasproAll,
            'dataSastra' => $this->indikatorSastraData($request),
            'dataSaspro' => $this->indikatorSasproData($request),
            'filters'    => $request->only(['search', 'tab']),
        ]);
    }

    /**
     * Query Builder untuk Indikator Sastra
     */
    private function indikatorSastraData(Request $request)
    {
        $search = trim((string) $request->input('search'));

        return DB::table('indikator_sastra as indikator')
            ->join('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->select([
                'indikator.kode_indikator',
                'indikator.nama_indikator',
                'indikator.kode_sastra as id_sastra',
                'sastra.nama_sastra',
                DB::raw("COALESCE(NULLIF(indikator.link, ''), NULLIF(sastra.link, ''), 0) as link"),
                DB::raw("COALESCE(NULLIF(indikator.lingkup, ''), NULLIF(sastra.lingkup, ''), 0) as lingkup"),
                'indikator.deskripsi_indikator_sastra',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('indikator.kode_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator.kode_sastra', 'like', "%{$search}%")
                        ->orWhere('sastra.nama_sastra', 'like', "%{$search}%")
                        ->orWhere('indikator.link', 'like', "%{$search}%")
                        ->orWhere('indikator.lingkup', 'like', "%{$search}%");
                });
            })
            ->orderBy('indikator.kode_sastra')
            ->orderBy('indikator.kode_indikator')
            ->paginate(10, ['*'], 'sastra_page')
            ->withQueryString();
    }

    /**
     * Query Builder untuk Indikator Saspro
     */
    private function indikatorSasproData(Request $request)
    {
        $search = trim((string) $request->input('search'));
        
        $sastraScope = DB::table('indikator_sastra')
            ->select([
                'kode_sastra',
                DB::raw("MAX(NULLIF(link, '')) as link"),
                DB::raw("MAX(NULLIF(lingkup, '')) as lingkup"),
            ])
            ->groupBy('kode_sastra');

        return DB::table('indikator_saspro as indikator')
            ->join('sakip_saspro_new as saspro', 'indikator.kode_saspro', '=', 'saspro.id_saspro')
            ->join('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->leftJoinSub($sastraScope, 'sastra_scope', 'sastra_scope.kode_sastra', '=', 'sastra.id_sastra')
            ->select([
                'indikator.kode_indikator',
                'indikator.nama_indikator',
                'indikator.kode_sastra as id_sastra',
                'sastra.nama_sastra',
                'indikator.kode_saspro as id_saspro',
                'saspro.nama_saspro',
                DB::raw("COALESCE(NULLIF(saspro.link, ''), NULLIF(sastra.link, ''), sastra_scope.link, 0) as link"),
                DB::raw("COALESCE(NULLIF(sastra.lingkup, ''), sastra_scope.lingkup, 0) as lingkup"),
                'indikator.deskripsi_indikator_saspro',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('indikator.kode_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator.kode_sastra', 'like', "%{$search}%")
                        ->orWhere('sastra.nama_sastra', 'like', "%{$search}%")
                        ->orWhere('indikator.kode_saspro', 'like', "%{$search}%")
                        ->orWhere('saspro.nama_saspro', 'like', "%{$search}%");
                });
            })
            ->orderBy('indikator.kode_sastra')
            ->orderBy('indikator.kode_saspro')
            ->orderBy('indikator.kode_indikator')
            ->paginate(10, ['*'], 'saspro_indikator_page')
            ->withQueryString();
    }

    /* =======================================================
       KELOLA INDIKATOR
       ======================================================= */

    public function storeIndikator(Request $request)
    {
        $validated = $request->validate([
            'bidang'                 => 'required|exists:sinori_sakip_bidang,id',
            'lingkup'                => 'required|numeric',
            'id_saspro'              => 'required|exists:sinori_sakip_saspro,id',
            'indikator_nama'         => 'required|string|max:255',
            'indikator_pembilang'    => 'required|string|max:255',
            'indikator_penyebut'     => 'required|string|max:255',
            'indikator_penjelasan'   => 'required|string',
            'sub_indikator'          => 'nullable|string',
            'tahun'                  => 'nullable|string',
            'indikator_penghitungan' => 'nullable|string',
            'tren'                   => 'nullable|string',
        ]);

        Indikator::create([
            'link'                   => $validated['bidang'],
            'lingkup'                => $validated['lingkup'],
            'id_saspro'              => $validated['id_saspro'],
            'indikator_nama'         => $validated['indikator_nama'],
            'indikator_pembilang'    => $validated['indikator_pembilang'],
            'indikator_penyebut'     => $validated['indikator_penyebut'],
            'indikator_penjelasan'   => $validated['indikator_penjelasan'],
            'sub_indikator'          => $validated['sub_indikator'],
            'tahun'                  => $validated['tahun'],
            'indikator_penghitungan' => $validated['indikator_penghitungan'],
            'tren'                   => $validated['tren'],
        ]);

        return Redirect::back()->with('success', 'Data Indikator berhasil disimpan.');
    }

    public function updateIndikator(Request $request, $id)
    {
        $validated = $request->validate([
            'bidang'                 => 'required|exists:sinori_sakip_bidang,id',
            'lingkup'                => 'required|numeric',
            'id_saspro'              => 'required|exists:sinori_sakip_saspro,id',
            'indikator_nama'         => 'required|string|max:255',
            'indikator_pembilang'    => 'required|string|max:255',
            'indikator_penyebut'     => 'required|string|max:255',
            'indikator_penjelasan'   => 'required|string',
            'sub_indikator'          => 'nullable|string',
            'tahun'                  => 'nullable|string', 
            'indikator_penghitungan' => 'nullable|string',
            'tren'                   => 'nullable|string',
        ]);

        $indikator = Indikator::findOrFail($id);
        
        $indikator->update([
            'link'                   => $validated['bidang'],
            'lingkup'                => $validated['lingkup'],
            'id_saspro'              => $validated['id_saspro'],
            'indikator_nama'         => $validated['indikator_nama'],
            'indikator_pembilang'    => $validated['indikator_pembilang'],
            'indikator_penyebut'     => $validated['indikator_penyebut'],
            'indikator_penjelasan'   => $validated['indikator_penjelasan'],
            'sub_indikator'          => $validated['sub_indikator'],
            'tahun'                  => $validated['tahun'], 
            'indikator_penghitungan' => $validated['indikator_penghitungan'],
            'tren'                   => $validated['tren'],
        ]);

        return Redirect::back()->with('success', 'Data Indikator berhasil diperbarui.');
    }

    public function deleteIndikator($id)
    {
        $indikator = Indikator::find($id);

        if ($indikator) {
            $indikator->delete();
            return Redirect::back()->with('success', 'Indikator berhasil dihapus.');
        }

        return Redirect::back()->with('error', 'Indikator tidak ditemukan.');
    }


    /* =======================================================
       KELOLA BIDANG
       ======================================================= */

    public function storeOrUpdateBidang(Request $request)
    {
        $validated = $request->validate([
            'id'            => 'nullable|integer',
            'bidang_nama'   => 'required|string|max:255',
            'bidang_level'  => 'required|integer|min:0',
            'bidang_lokasi' => 'required|integer|min:0',
            'rumpun'        => 'required|integer|min:0',
            'hide'          => 'required|integer|in:0,1',
        ], [], [
            'bidang_nama'   => 'nama bidang',
            'bidang_level'  => 'level bidang',
            'bidang_lokasi' => 'lokasi bidang',
            'rumpun'        => 'rumpun bidang',
            'hide'          => 'status bidang',
        ]);

        $payload = [
            'bidang_nama'   => trim($validated['bidang_nama']),
            'bidang_level'  => (string) $validated['bidang_level'],
            'bidang_lokasi' => (string) $validated['bidang_lokasi'],
            'rumpun'        => (string) $validated['rumpun'],
            'hide'          => (string) $validated['hide'],
        ];

        if (!empty($validated['id'])) {
            $bidang = Bidang::findOrFail($validated['id']);
            $bidang->update($payload);
            return Redirect::route('keloladata')->with('success', 'Data bidang berhasil diperbarui!');
        }

        Bidang::create($payload);
        return Redirect::route('keloladata')->with('success', 'Data bidang berhasil disimpan!');
    }

    public function destroyBidang($id)
    {
        $bidang = Bidang::findOrFail($id);
        $bidang->delete();

        return Redirect::route('keloladata')->with('success', 'Data bidang berhasil dihapus.');
    }


    /* =======================================================
       KELOLA SASPRO
       ======================================================= */

    public function sasproStore(Request $request)
    {
        $validated = $request->validate([
            'link'              => 'required|string|max:255',
            'saspro_nama'       => 'required|string|max:255',
            'penjelasan_saspro' => 'required|string',
            'tahun'             => 'required|string',
            'hide'              => 'required|integer|in:0,1',
        ]);

        Saspro::create([
            'link'              => $validated['link'],
            'saspro_nama'       => $validated['saspro_nama'],
            'saspro_penjelasan' => $validated['penjelasan_saspro'],
            'lingkup'           => '0', // Nilai default konstan berdasarkan kode lama
            'tahun'             => $validated['tahun'],
            'hide'              => $validated['hide'],
        ]);

        return Redirect::back()->with('success', 'Data Saspro berhasil disimpan!');
    }

    public function sasproUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'link'              => 'required|string|max:255',
            'saspro_nama'       => 'required|string|max:255',
            'penjelasan_saspro' => 'required|string',
            'tahun'             => 'required|string',
            'hide'              => 'required|integer|in:0,1',
        ]);

        $saspro = Saspro::findOrFail($id);
        
        $saspro->update([
            'link'              => $validated['link'],
            'saspro_nama'       => $validated['saspro_nama'],
            'saspro_penjelasan' => $validated['penjelasan_saspro'],
            'tahun'             => $validated['tahun'],
            'hide'              => $validated['hide'],
        ]);

        return Redirect::back()->with('success', 'Data Saspro berhasil diperbarui!');
    }

    public function destroySaspro($id)
    {
        $saspro = Saspro::find($id);

        if (!$saspro) {
            return Redirect::back()->with('error', 'Data Saspro tidak ditemukan.');
        }

        $saspro->delete();
        return Redirect::back()->with('success', 'Data Saspro berhasil dihapus.');
    }
}