<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Indikator;
use App\Models\Bidang;
use App\Models\Saspro;
use App\Models\indikator_sastra_new;
use App\Models\saspro_indikator_new;
use App\Models\saspro_new;
use App\Models\sastra_new;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

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
        $perPage = $this->perPage($request);

        $bidangs = Bidang::select(['id', 'bidang_nama', 'bidang_level', 'bidang_lokasi', 'rumpun', 'hide'])
            ->orderBy('bidang_lokasi')
            ->orderBy('bidang_level')
            ->orderBy('rumpun')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'bidang_page')
            ->withQueryString();
            
        $bidangall = Bidang::select('id', 'bidang_nama', 'rumpun')
            ->where('hide', 0)
            ->get();

        $indikators = Indikator::with(['bidangById', 'saspro'])
            ->orderBy('tahun', 'desc')
            ->orderBy('link', 'asc')
            ->paginate($perPage, ['*'], 'indikator_page')
            ->withQueryString();

        $saspros = Saspro::with('bidang')
            ->orderBy('tahun', 'desc')
            ->orderBy('link', 'asc')
            ->paginate($perPage, ['*'], 'saspro_page')
            ->withQueryString(); 

        $sasproAll = Saspro::select('id', 'saspro_nama', 'tahun', 'link')
            ->orderBy('tahun', 'desc')
            ->orderBy('link', 'asc')
            ->get();

        return Inertia::render('KelolaData', [
            'tahun'          => $tahun,
            'bidangs'        => $bidangs,
            'saspros'        => $saspros,
            'bidangall'      => $bidangall,
            'indikators'     => $indikators,
            'sasproAll'      => $sasproAll,
            'masterDataTabs' => $this->masterDataTabs($request),
            'perPageOptions' => [10, 25, 50],
            'canManagePohonKinerja' => $this->isAdmin(),
            'filters'        => $request->only(['search', 'pohon_search', 'tab', 'per_page']),
        ]);
    }

    // =========================================================================
    // QUERY BUILDERS
    // =========================================================================

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
                $query->where(function ($q) use ($search) {
                    $q->where('indikator.kode_indikator', 'like', "%{$search}%")
                      ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                      ->orWhere('indikator.kode_sastra', 'like', "%{$search}%")
                      ->orWhere('sastra.nama_sastra', 'like', "%{$search}%");
                });
            })
            ->orderBy('indikator.kode_sastra')
            ->orderBy('indikator.kode_indikator')
            ->paginate($this->perPage($request), ['*'], 'sastra_page')
            ->withQueryString();
    }

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
                $query->where(function ($q) use ($search) {
                    $q->where('indikator.kode_indikator', 'like', "%{$search}%")
                      ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                      ->orWhere('sastra.nama_sastra', 'like', "%{$search}%")
                      ->orWhere('saspro.nama_saspro', 'like', "%{$search}%");
                });
            })
            ->orderBy('indikator.kode_sastra')
            ->orderBy('indikator.kode_saspro')
            ->orderBy('indikator.kode_indikator')
            ->paginate($this->perPage($request), ['*'], 'saspro_indikator_page')
            ->withQueryString();
    }

    private function pohonSastraData(Request $request)
    {
        $search = trim((string) $request->input('pohon_search', $request->input('search')));

        return DB::table('sakip_sastra_new')
            ->select($this->selectExistingColumns('sakip_sastra_new', [
                'id_sastra', 'nama_sastra', 'deskripsi', 'link', 'lingkup', 'target', 'tahun', 'hide', 'urutan'
            ]))
            ->when($search !== '', function ($query) use ($search) {
                $query->where('id_sastra', 'like', "%{$search}%")
                      ->orWhere('nama_sastra', 'like', "%{$search}%");
            })
            ->orderByRaw($this->orderExpression('sakip_sastra_new', 'id_sastra'))
            ->paginate($this->perPage($request), ['*'], 'pohon_sastra_page')
            ->withQueryString();
    }

    private function pohonIndikatorSastraData(Request $request)
    {
        $search = trim((string) $request->input('pohon_search', $request->input('search')));

        return DB::table('indikator_sastra as indikator')
            ->leftJoin('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->select([
                'indikator.kode_indikator', 'indikator.kode_sastra', 'indikator.nama_indikator',
                'indikator.deskripsi_indikator_sastra', 'sastra.nama_sastra',
                ...$this->selectExistingColumns('indikator_sastra', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'indikator'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('indikator.kode_indikator', 'like', "%{$search}%")
                      ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                      ->orWhere('sastra.nama_sastra', 'like', "%{$search}%");
            })
            ->orderBy('indikator.kode_sastra')
            ->orderByRaw($this->orderExpression('indikator_sastra', 'indikator.kode_indikator', 'indikator'))
            ->paginate($this->perPage($request), ['*'], 'pohon_indikator_sastra_page')
            ->withQueryString();
    }

    private function pohonSasproData(Request $request)
    {
        $search = trim((string) $request->input('pohon_search', $request->input('search')));

        return DB::table('sakip_saspro_new as saspro')
            ->leftJoin('sakip_sastra_new as sastra', 'saspro.id_sastra', '=', 'sastra.id_sastra')
            ->select([
                'saspro.id_saspro', 'saspro.id_sastra', 'saspro.nama_saspro', 'saspro.deskripsi', 'sastra.nama_sastra',
                ...$this->selectExistingColumns('sakip_saspro_new', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'saspro'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('saspro.id_saspro', 'like', "%{$search}%")
                      ->orWhere('saspro.nama_saspro', 'like', "%{$search}%")
                      ->orWhere('sastra.nama_sastra', 'like', "%{$search}%");
            })
            ->orderBy('saspro.id_sastra')
            ->orderByRaw($this->orderExpression('sakip_saspro_new', 'saspro.id_saspro', 'saspro'))
            ->paginate($this->perPage($request), ['*'], 'pohon_saspro_page')
            ->withQueryString();
    }

    private function pohonIndikatorSasproData(Request $request)
    {
        $search = trim((string) $request->input('pohon_search', $request->input('search')));

        return DB::table('indikator_saspro as indikator')
            ->leftJoin('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->leftJoin('sakip_saspro_new as saspro', 'indikator.kode_saspro', '=', 'saspro.id_saspro')
            ->select([
                'indikator.kode_indikator', 'indikator.kode_sastra', 'indikator.kode_saspro',
                'indikator.nama_indikator', 'indikator.deskripsi_indikator_saspro',
                'sastra.nama_sastra', 'saspro.nama_saspro',
                ...$this->selectExistingColumns('indikator_saspro', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'indikator'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('indikator.kode_indikator', 'like', "%{$search}%")
                      ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                      ->orWhere('sastra.nama_sastra', 'like', "%{$search}%")
                      ->orWhere('saspro.nama_saspro', 'like', "%{$search}%");
            })
            ->orderBy('indikator.kode_sastra')
            ->orderBy('indikator.kode_saspro')
            ->orderByRaw($this->orderExpression('indikator_saspro', 'indikator.kode_indikator', 'indikator'))
            ->paginate($this->perPage($request), ['*'], 'pohon_indikator_saspro_page')
            ->withQueryString();
    }

    // =========================================================================
    // MASTER DATA UI TABS SETUP
    // =========================================================================

    private function masterDataTabs(Request $request): array
    {
        $sastraOptions = DB::table('sakip_sastra_new')->select(['id_sastra', 'nama_sastra'])->orderBy('id_sastra')->get()
            ->map(fn ($row) => ['value' => (string) $row->id_sastra, 'label' => "{$row->id_sastra} - {$row->nama_sastra}"])->values()->all();

        $sasproOptions = DB::table('sakip_saspro_new')->select(['id_saspro', 'nama_saspro'])->orderBy('id_sastra')->orderBy('id_saspro')->get()
            ->map(fn ($row) => ['value' => (string) $row->id_saspro, 'label' => "{$row->id_saspro} - {$row->nama_saspro}"])->values()->all();

        $lingkupOptions = $this->lingkupOptions();
        $statusOptions = $this->statusOptions();

        return [
            // Sastra Tab
            [
                'key' => 'sastra', 'label' => 'Sasaran Strategis', 'addLabel' => 'Tambah Sastra', 'idKey' => 'id_sastra', 'pageKey' => 'pohon_sastra_page',
                'routes' => ['store' => 'pohon.sastra.store', 'update' => 'pohon.sastra.update', 'destroy' => 'pohon.sastra.destroy'],
                'columns' => [
                    ['field' => 'id_sastra', 'headerName' => 'Kode', 'width' => 110],
                    ['field' => 'nama_sastra', 'headerName' => 'Sasaran Strategis', 'flex' => 1, 'minWidth' => 260],
                    ['field' => 'target', 'headerName' => 'Target', 'width' => 160],
                    ['field' => 'hide', 'headerName' => 'Status', 'width' => 110],
                ],
                'fields' => [
                    ['name' => 'id_sastra', 'label' => 'Kode Sastra', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'nama_sastra', 'label' => 'Nama Sasaran Strategis', 'required' => true, 'md' => 8],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'target', 'label' => 'Target', 'md' => 4],
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => '0'],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonSastraData($request),
            ],
            // Indikator Sastra Tab
            [
                'key' => 'indikatorSastra', 'label' => 'Indikator Sastra', 'addLabel' => 'Tambah Indikator Sastra', 'idKey' => 'kode_indikator', 'pageKey' => 'pohon_indikator_sastra_page',
                'routes' => ['store' => 'pohon.indikator-sastra.store', 'update' => 'pohon.indikator-sastra.update', 'destroy' => 'pohon.indikator-sastra.destroy'],
                'columns' => [
                    ['field' => 'kode_indikator', 'headerName' => 'Kode', 'width' => 130],
                    ['field' => 'nama_sastra', 'headerName' => 'Nama Sastra', 'flex' => 1, 'minWidth' => 220],
                    ['field' => 'nama_indikator', 'headerName' => 'Indikator', 'flex' => 1, 'minWidth' => 280],
                ],
                'fields' => [
                    ['name' => 'kode_indikator', 'label' => 'Kode Indikator', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'kode_sastra', 'label' => 'Sasaran Strategis', 'type' => 'select', 'options' => $sastraOptions, 'required' => true, 'md' => 8],
                    ['name' => 'nama_indikator', 'label' => 'Nama Indikator', 'required' => true, 'md' => 12],
                    ['name' => 'deskripsi_indikator_sastra', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => '0'],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonIndikatorSastraData($request),
            ],
            // Saspro Tab
            [
                'key' => 'saspro', 'label' => 'Sasaran Program', 'addLabel' => 'Tambah Saspro', 'idKey' => 'id_saspro', 'pageKey' => 'pohon_saspro_page',
                'routes' => ['store' => 'pohon.saspro.store', 'update' => 'pohon.saspro.update', 'destroy' => 'pohon.saspro.destroy'],
                'columns' => [
                    ['field' => 'id_saspro', 'headerName' => 'Kode', 'width' => 120],
                    ['field' => 'nama_saspro', 'headerName' => 'Sasaran Program', 'flex' => 1, 'minWidth' => 280],
                ],
                'fields' => [
                    ['name' => 'id_saspro', 'label' => 'Kode Saspro', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'id_sastra', 'label' => 'Sasaran Strategis', 'type' => 'select', 'options' => $sastraOptions, 'required' => true, 'md' => 8],
                    ['name' => 'nama_saspro', 'label' => 'Nama Sasaran Program', 'required' => true, 'md' => 12],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => '0'],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonSasproData($request),
            ],
            // Indikator Saspro Tab
            [
                'key' => 'indikatorSaspro', 'label' => 'Indikator Saspro', 'addLabel' => 'Tambah Indikator Saspro', 'idKey' => 'kode_indikator', 'pageKey' => 'pohon_indikator_saspro_page',
                'routes' => ['store' => 'pohon.indikator-saspro.store', 'update' => 'pohon.indikator-saspro.update', 'destroy' => 'pohon.indikator-saspro.destroy'],
                'columns' => [
                    ['field' => 'kode_indikator', 'headerName' => 'Kode', 'width' => 130],
                    ['field' => 'nama_saspro', 'headerName' => 'Nama Saspro', 'flex' => 1, 'minWidth' => 220],
                    ['field' => 'nama_indikator', 'headerName' => 'Indikator', 'flex' => 1, 'minWidth' => 280],
                ],
                'fields' => [
                    ['name' => 'kode_indikator', 'label' => 'Kode Indikator', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'kode_saspro', 'label' => 'Sasaran Program', 'type' => 'select', 'options' => $sasproOptions, 'required' => true, 'md' => 8],
                    ['name' => 'nama_indikator', 'label' => 'Nama Indikator', 'required' => true, 'md' => 12],
                    ['name' => 'deskripsi_indikator_saspro', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => '0'],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonIndikatorSasproData($request),
            ],
        ];
    }

    private function lingkupOptions(): array
    {
        return [
            ['value' => '0', 'label' => 'Semua Satker'],
            ['value' => '1', 'label' => 'Pusat'],
            ['value' => '2', 'label' => 'Kejati'],
            ['value' => '3', 'label' => 'Kejari'],
            ['value' => '4', 'label' => 'Cabjari'],
            ['value' => '5', 'label' => 'Kejati, Kejari'],
            ['value' => '6', 'label' => 'Kejari, Cabjari'],
            ['value' => '7', 'label' => 'Kejati, Kejari, Cabjari'],
        ];
    }

    private function statusOptions(): array
    {
        return [
            ['value' => '0', 'label' => 'Tampil'],
            ['value' => '1', 'label' => 'Sembunyikan'],
        ];
    }

    // =========================================================================
    // KELOLA INDIKATOR
    // =========================================================================

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

        try {
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
        } catch (Exception $e) {
            Log::error('Error storeIndikator: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Gagal menyimpan data. Pastikan ID unik dan relasi sesuai.');
        }
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

        try {
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
        } catch (Exception $e) {
            Log::error('Error updateIndikator: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Gagal memperbarui indikator.');
        }
    }

    public function deleteIndikator($id)
    {
        try {
            $indikator = Indikator::findOrFail($id);
            $indikator->delete();
            return Redirect::back()->with('success', 'Indikator berhasil dihapus.');
        } catch (Exception $e) {
            Log::error('Error deleteIndikator: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Indikator tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    // =========================================================================
    // KELOLA DATA MASTER POHON KINERJA (SASTRA & SASPRO)
    // =========================================================================

    public function pohonSastraStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'id_sastra'   => ['required', 'string', 'max:255', Rule::unique('sakip_sastra_new', 'id_sastra')],
            'nama_sastra' => 'required|string|max:255',
            'deskripsi'   => 'nullable|string',
            'link'        => 'nullable|string|max:255',
            'lingkup'     => 'nullable|string|max:20',
            'target'      => 'nullable|string|max:255',
            'tahun'       => 'nullable|string|max:10',
            'hide'        => 'required|integer|in:0,1',
            'urutan'      => 'nullable|integer|min:0',
        ]);

        try {
            sastra_new::create($this->existingPayload('sakip_sastra_new', $validated));
            return Redirect::back()->with('success', 'Data sasaran strategis berhasil disimpan.');
        } catch (Exception $e) {
            Log::error('Error pohonSastraStore: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Gagal menyimpan sasaran strategis.');
        }
    }

    public function pohonSastraUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'nama_sastra' => 'required|string|max:255',
            'deskripsi'   => 'nullable|string',
            'link'        => 'nullable|string|max:255',
            'lingkup'     => 'nullable|string|max:20',
            'target'      => 'nullable|string|max:255',
            'tahun'       => 'nullable|string|max:10',
            'hide'        => 'required|integer|in:0,1',
            'urutan'      => 'nullable|integer|min:0',
        ]);

        try {
            $sastra = sastra_new::findOrFail($id);
            $sastra->update($this->existingPayload('sakip_sastra_new', $validated));
            return Redirect::back()->with('success', 'Data sasaran strategis berhasil diperbarui.');
        } catch (Exception $e) {
            Log::error('Error pohonSastraUpdate: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Gagal memperbarui sasaran strategis.');
        }
    }

    public function pohonSastraDestroy(string $id)
    {
        $this->ensureAdmin();
        try {
            sastra_new::findOrFail($id)->delete();
            return Redirect::back()->with('success', 'Data sasaran strategis berhasil dihapus.');
        } catch (Exception $e) {
            return Redirect::back()->with('error', 'Tidak dapat dihapus karena terikat relasi Foreign Key.');
        }
    }

    public function pohonIndikatorSastraStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'kode_indikator'             => ['required', 'string', 'max:255', Rule::unique('indikator_sastra', 'kode_indikator')],
            'kode_sastra'                => 'required|exists:sakip_sastra_new,id_sastra',
            'nama_indikator'             => 'required|string|max:255',
            'deskripsi_indikator_sastra' => 'nullable|string',
            'lingkup'                    => 'nullable|string|max:20',
            'hide'                       => 'required|integer|in:0,1',
        ]);

        try {
            indikator_sastra_new::create($this->existingPayload('indikator_sastra', $validated));
            return Redirect::back()->with('success', 'Indikator sasaran strategis berhasil disimpan.');
        } catch (Exception $e) {
            Log::error('Error pohonIndikatorSastraStore: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Gagal menyimpan indikator sasaran strategis.');
        }
    }

    public function pohonIndikatorSastraUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'kode_sastra'                => 'required|exists:sakip_sastra_new,id_sastra',
            'nama_indikator'             => 'required|string|max:255',
            'deskripsi_indikator_sastra' => 'nullable|string',
            'lingkup'                    => 'nullable|string|max:20',
            'hide'                       => 'required|integer|in:0,1',
        ]);

        try {
            $indikator = indikator_sastra_new::findOrFail($id);
            $indikator->update($this->existingPayload('indikator_sastra', $validated));
            return Redirect::back()->with('success', 'Indikator sasaran strategis berhasil diperbarui.');
        } catch (Exception $e) {
            Log::error('Error pohonIndikatorSastraUpdate: ' . $e->getMessage());
            return Redirect::back()->with('error', 'Gagal memperbarui indikator sasaran strategis.');
        }
    }

    public function pohonIndikatorSastraDestroy(string $id)
    {
        $this->ensureAdmin();
        try {
            indikator_sastra_new::findOrFail($id)->delete();
            return Redirect::back()->with('success', 'Data indikator sasaran strategis berhasil dihapus.');
        } catch (Exception $e) {
            return Redirect::back()->with('error', 'Tidak dapat dihapus karena terikat relasi Target PK.');
        }
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);
        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }

    private function selectExistingColumns(string $table, array $columns, ?string $alias = null): array
    {
        return collect($columns)->map(function ($column) use ($table, $alias) {
            return Schema::hasColumn($table, $column) ? ($alias ? "{$alias}.{$column}" : $column) : DB::raw("NULL as {$column}");
        })->all();
    }

    private function orderExpression(string $table, string $fallbackColumn, ?string $alias = null): string
    {
        if (Schema::hasColumn($table, 'urutan')) {
            $urutanColumn = $alias ? "{$alias}.urutan" : 'urutan';
            return "COALESCE({$urutanColumn}, 999999), {$fallbackColumn}";
        }
        return $fallbackColumn;
    }

    private function existingPayload(string $table, array $payload): array
    {
        return collect($payload)->filter(fn($val, $key) => Schema::hasColumn($table, $key))->all();
    }

    private function isAdmin(): bool
    {
        return (int) (auth()->user()?->id_sakip_level ?? session('id_sakip_level', 0)) === 99;
    }

    private function ensureAdmin(): void
    {
        abort_unless($this->isAdmin(), 403);
    }
}