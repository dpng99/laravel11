<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Indikator;
use App\Models\Saspro;
use App\Models\indikator_sastra_new;
use App\Models\saspro_indikator_new;
use App\Models\saspro_new;
use App\Models\sastra_new;
use App\Services\SatkerAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class KeloladataController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50];
    private const STATUS_VALUES = ['0', '1'];

    public function index(Request $request)
    {
        $this->ensureAdmin();

        if (! session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $perPage = $this->perPage($request);

        return Inertia::render('KelolaData', [
            'tahun' => session('tahun_terpilih'),
            'bidangs' => $this->bidangData($request, $perPage),
            'saspros' => $this->sasproData($request, $perPage),
            'indikators' => $this->indikatorData($request, $perPage),
            'bidangall' => $this->bidangOptions(),
            'sasproAll' => $this->sasproOptions(),
            'masterDataTabs' => $this->masterDataTabs($request),
            'lingkupOptions' => $this->lingkupOptions(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'canManagePohonKinerja' => $this->isAdmin(),
            'filters' => $request->only([
                'search',
                'pohon_search',
                'tab',
                'per_page',
            ]),
        ]);
    }

    // ---------------------------------------------------------------------
    // Data halaman utama
    // ---------------------------------------------------------------------

    private function bidangData(Request $request, int $perPage)
    {
        return Bidang::select(['id', 'bidang_nama', 'bidang_level', 'bidang_lokasi', 'rumpun', 'hide'])
            ->when($this->search($request) !== '', function ($query) use ($request) {
                $search = $this->search($request);

                $query->where(function ($q) use ($search) {
                    $q->where('bidang_nama', 'like', "%{$search}%")
                        ->orWhere('rumpun', 'like', "%{$search}%");
                });
            })
            ->orderBy('bidang_lokasi')
            ->orderBy('bidang_level')
            ->orderBy('rumpun')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'bidang_page')
            ->withQueryString();
    }

    private function sasproData(Request $request, int $perPage)
    {
        return Saspro::with('bidang:id,bidang_nama')
            ->when($this->search($request) !== '', function ($query) use ($request) {
                $search = $this->search($request);

                $query->where(function ($q) use ($search) {
                    $q->where('saspro_nama', 'like', "%{$search}%")
                        ->orWhere('saspro_penjelasan', 'like', "%{$search}%")
                        ->orWhere('target', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('tahun')
            ->orderBy('link')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'saspro_page')
            ->withQueryString();
    }

    private function indikatorData(Request $request, int $perPage)
    {
        return Indikator::with([
                'bidangById:id,bidang_nama',
                'bidangByLink:id,bidang_nama,rumpun',
                'saspro:id,saspro_nama,tahun',
            ])
            ->when($this->search($request) !== '', function ($query) use ($request) {
                $search = $this->search($request);

                $query->where(function ($q) use ($search) {
                    $q->where('indikator_nama', 'like', "%{$search}%")
                        ->orWhere('sub_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator_penjelasan', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('tahun')
            ->orderBy('link')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'indikator_page')
            ->withQueryString();
    }

    private function bidangOptions()
    {
        return Bidang::select(['id', 'bidang_nama', 'rumpun'])
            ->where('hide', 0)
            ->orderBy('bidang_lokasi')
            ->orderBy('bidang_level')
            ->orderBy('rumpun')
            ->get();
    }

    private function sasproOptions()
    {
        return Saspro::select(['id', 'saspro_nama', 'tahun', 'link'])
            ->orderByDesc('tahun')
            ->orderBy('link')
            ->orderBy('id')
            ->get();
    }

    // ---------------------------------------------------------------------
    // Data Bidang
    // ---------------------------------------------------------------------

    public function bidang(Request $request)
    {
        $this->ensureAdmin();

        return $this->storeOrUpdateBidang($request);
    }

    public function storeOrUpdateBidang(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validateBidang($request);

        try {
            DB::transaction(function () use ($validated) {
                $payload = [
                    'bidang_nama' => $validated['bidang_nama'],
                    'bidang_level' => $validated['bidang_level'],
                    'bidang_lokasi' => $validated['bidang_lokasi'],
                    'rumpun' => $validated['rumpun'],
                    'hide' => $validated['hide'],
                ];

                if (isset($validated['id'])) {
                    Bidang::findOrFail($validated['id'])->update($payload);

                    return;
                }

                Bidang::create($payload);
            });

            $message = isset($validated['id'])
                ? 'Data bidang berhasil diperbarui.'
                : 'Data bidang berhasil disimpan.';

            return Redirect::back()->with('success', $message);
        } catch (Throwable $e) {
            Log::error('Keloladata bidang save failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan data bidang.');
        }
    }

    public function edit($id)
    {
        $this->ensureAdmin();

        return response()->json(Bidang::findOrFail($id));
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $bidang = Bidang::findOrFail($id);

        if (
            Saspro::where('link', $bidang->id)->exists()
            || Indikator::where('link', $bidang->id)->exists()
        ) {
            return Redirect::back()->with('error', 'Bidang tidak dapat dihapus karena masih dipakai Saspro atau Indikator.');
        }

        try {
            $bidang->delete();

            return Redirect::back()->with('success', 'Data bidang berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata bidang delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menghapus data bidang.');
        }
    }

    // ---------------------------------------------------------------------
    // Data Saspro lama
    // ---------------------------------------------------------------------

    public function sasproStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validateSaspro($request);

        try {
            DB::transaction(fn () => Saspro::create($this->sasproPayload($validated)));

            return Redirect::back()->with('success', 'Data Saspro berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata saspro store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan data Saspro.');
        }
    }

    public function sasproUpdate(Request $request, $id)
    {
        $this->ensureAdmin();

        $saspro = Saspro::findOrFail($id);
        $validated = $this->validateSaspro($request);

        try {
            DB::transaction(fn () => $saspro->update($this->sasproPayload($validated, $saspro)));

            return Redirect::back()->with('success', 'Data Saspro berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata saspro update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui data Saspro.');
        }
    }

    public function destroySaspro($id)
    {
        $this->ensureAdmin();

        $saspro = Saspro::findOrFail($id);

        if (Indikator::where('id_saspro', $saspro->id)->exists()) {
            return Redirect::back()->with('error', 'Saspro tidak dapat dihapus karena masih dipakai Indikator.');
        }

        try {
            $saspro->delete();

            return Redirect::back()->with('success', 'Data Saspro berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata saspro delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menghapus data Saspro.');
        }
    }

    // ---------------------------------------------------------------------
    // Data Indikator lama
    // ---------------------------------------------------------------------

    public function indikator(Request $request)
    {
        $this->ensureAdmin();

        return $this->storeIndikator($request);
    }

    public function storeIndikator(Request $request)
    {
        $this->ensureAdmin();

        $payload = $this->indikatorPayload($request);

        try {
            DB::transaction(fn () => Indikator::create($payload));

            return Redirect::back()->with('success', 'Data Indikator berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata indikator store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan data Indikator.');
        }
    }

    public function updateIndikator(Request $request, $id)
    {
        $this->ensureAdmin();

        $indikator = Indikator::findOrFail($id);
        $payload = $this->indikatorPayload($request, $indikator);

        try {
            DB::transaction(fn () => $indikator->update($payload));

            return Redirect::back()->with('success', 'Data Indikator berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata indikator update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui data Indikator.');
        }
    }

    public function deleteIndikator($id)
    {
        $this->ensureAdmin();

        try {
            Indikator::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data Indikator berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata indikator delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menghapus data Indikator.');
        }
    }

    // ---------------------------------------------------------------------
    // Tab master pohon kinerja
    // ---------------------------------------------------------------------

    private function masterDataTabs(Request $request): array
    {
        $sastraOptions = DB::table('sakip_sastra_new')
            ->select(['id_sastra', 'nama_sastra'])
            ->orderByRaw($this->orderExpression('sakip_sastra_new', 'id_sastra'))
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->id_sastra,
                'label' => "{$row->id_sastra} - {$row->nama_sastra}",
            ])
            ->values()
            ->all();

        $sasproOptions = DB::table('sakip_saspro_new')
            ->select(['id_saspro', 'nama_saspro'])
            ->orderBy('id_sastra')
            ->orderByRaw($this->orderExpression('sakip_saspro_new', 'id_saspro'))
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->id_saspro,
                'label' => "{$row->id_saspro} - {$row->nama_saspro}",
            ])
            ->values()
            ->all();

        $lingkupOptions = $this->lingkupOptions();
        $statusOptions = $this->statusOptions();
        $defaultLingkup = $this->defaultLingkupValue();

        return [
            [
                'key' => 'sastra',
                'label' => 'Sasaran Strategis',
                'addLabel' => 'Tambah Sastra',
                'idKey' => 'id_sastra',
                'pageKey' => 'pohon_sastra_page',
                'routes' => [
                    'store' => 'pohon.sastra.store',
                    'update' => 'pohon.sastra.update',
                    'destroy' => 'pohon.sastra.destroy',
                ],
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
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonSastraData($request),
            ],
            [
                'key' => 'indikatorSastra',
                'label' => 'Indikator Sastra',
                'addLabel' => 'Tambah Indikator Sastra',
                'idKey' => 'kode_indikator',
                'pageKey' => 'pohon_indikator_sastra_page',
                'routes' => [
                    'store' => 'pohon.indikator-sastra.store',
                    'update' => 'pohon.indikator-sastra.update',
                    'destroy' => 'pohon.indikator-sastra.destroy',
                ],
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
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonIndikatorSastraData($request),
            ],
            [
                'key' => 'saspro',
                'label' => 'Sasaran Program',
                'addLabel' => 'Tambah Saspro',
                'idKey' => 'id_saspro',
                'pageKey' => 'pohon_saspro_page',
                'routes' => [
                    'store' => 'pohon.saspro.store',
                    'update' => 'pohon.saspro.update',
                    'destroy' => 'pohon.saspro.destroy',
                ],
                'columns' => [
                    ['field' => 'id_saspro', 'headerName' => 'Kode', 'width' => 120],
                    ['field' => 'nama_sastra', 'headerName' => 'Nama Sastra', 'flex' => 1, 'minWidth' => 220],
                    ['field' => 'nama_saspro', 'headerName' => 'Sasaran Program', 'flex' => 1, 'minWidth' => 280],
                ],
                'fields' => [
                    ['name' => 'id_saspro', 'label' => 'Kode Saspro', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'id_sastra', 'label' => 'Sasaran Strategis', 'type' => 'select', 'options' => $sastraOptions, 'required' => true, 'md' => 8],
                    ['name' => 'nama_saspro', 'label' => 'Nama Sasaran Program', 'required' => true, 'md' => 12],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonSasproData($request),
            ],
            [
                'key' => 'indikatorSaspro',
                'label' => 'Indikator Saspro',
                'addLabel' => 'Tambah Indikator Saspro',
                'idKey' => 'kode_indikator',
                'pageKey' => 'pohon_indikator_saspro_page',
                'routes' => [
                    'store' => 'pohon.indikator-saspro.store',
                    'update' => 'pohon.indikator-saspro.update',
                    'destroy' => 'pohon.indikator-saspro.destroy',
                ],
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
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonIndikatorSasproData($request),
            ],
        ];
    }

    private function pohonSastraData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('sakip_sastra_new')
            ->select($this->selectExistingColumns('sakip_sastra_new', [
                'id_sastra',
                'nama_sastra',
                'deskripsi',
                'link',
                'lingkup',
                'target',
                'tahun',
                'hide',
                'urutan',
            ]))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_sastra', 'like', "%{$search}%")
                        ->orWhere('nama_sastra', 'like', "%{$search}%");
                });
            })
            ->orderByRaw($this->orderExpression('sakip_sastra_new', 'id_sastra'))
            ->paginate($this->perPage($request), ['*'], 'pohon_sastra_page')
            ->withQueryString();
    }

    private function pohonIndikatorSastraData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('indikator_sastra as indikator')
            ->leftJoin('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->select([
                'indikator.kode_indikator',
                'indikator.kode_sastra',
                'indikator.nama_indikator',
                'indikator.deskripsi_indikator_sastra',
                'sastra.nama_sastra',
                ...$this->selectExistingColumns('indikator_sastra', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'indikator'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('indikator.kode_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                        ->orWhere('sastra.nama_sastra', 'like', "%{$search}%");
                });
            })
            ->orderBy('indikator.kode_sastra')
            ->orderByRaw($this->orderExpression('indikator_sastra', 'indikator.kode_indikator', 'indikator'))
            ->paginate($this->perPage($request), ['*'], 'pohon_indikator_sastra_page')
            ->withQueryString();
    }

    private function pohonSasproData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('sakip_saspro_new as saspro')
            ->leftJoin('sakip_sastra_new as sastra', 'saspro.id_sastra', '=', 'sastra.id_sastra')
            ->select([
                'saspro.id_saspro',
                'saspro.id_sastra',
                'saspro.nama_saspro',
                'saspro.deskripsi',
                'sastra.nama_sastra',
                ...$this->selectExistingColumns('sakip_saspro_new', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'saspro'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('saspro.id_saspro', 'like', "%{$search}%")
                        ->orWhere('saspro.nama_saspro', 'like', "%{$search}%")
                        ->orWhere('sastra.nama_sastra', 'like', "%{$search}%");
                });
            })
            ->orderBy('saspro.id_sastra')
            ->orderByRaw($this->orderExpression('sakip_saspro_new', 'saspro.id_saspro', 'saspro'))
            ->paginate($this->perPage($request), ['*'], 'pohon_saspro_page')
            ->withQueryString();
    }

    private function pohonIndikatorSasproData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('indikator_saspro as indikator')
            ->leftJoin('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->leftJoin('sakip_saspro_new as saspro', 'indikator.kode_saspro', '=', 'saspro.id_saspro')
            ->select([
                'indikator.kode_indikator',
                'indikator.kode_sastra',
                'indikator.kode_saspro',
                'indikator.nama_indikator',
                'indikator.deskripsi_indikator_saspro',
                'sastra.nama_sastra',
                'saspro.nama_saspro',
                ...$this->selectExistingColumns('indikator_saspro', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'indikator'),
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
            ->orderByRaw($this->orderExpression('indikator_saspro', 'indikator.kode_indikator', 'indikator'))
            ->paginate($this->perPage($request), ['*'], 'pohon_indikator_saspro_page')
            ->withQueryString();
    }

    // ---------------------------------------------------------------------
    // CRUD pohon kinerja
    // ---------------------------------------------------------------------

    public function pohonSastraStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonSastra($request, null);

        try {
            DB::transaction(fn () => sastra_new::create($this->existingPayload('sakip_sastra_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran strategis berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon sastra store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan sasaran strategis.');
        }
    }

    public function pohonSastraUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $sastra = sastra_new::findOrFail($id);
        $validated = $this->validatePohonSastra($request, $id);

        try {
            DB::transaction(fn () => $sastra->update($this->existingPayload('sakip_sastra_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran strategis berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon sastra update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui sasaran strategis.');
        }
    }

    public function pohonSastraDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            sastra_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data sasaran strategis berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon sastra delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    public function pohonIndikatorSastraStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonIndikatorSastra($request, null);

        try {
            DB::transaction(fn () => indikator_sastra_new::create($this->existingPayload('indikator_sastra', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran strategis berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator sastra store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan indikator sasaran strategis.');
        }
    }

    public function pohonIndikatorSastraUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $indikator = indikator_sastra_new::findOrFail($id);
        $validated = $this->validatePohonIndikatorSastra($request, $id);

        try {
            DB::transaction(fn () => $indikator->update($this->existingPayload('indikator_sastra', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran strategis berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator sastra update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui indikator sasaran strategis.');
        }
    }

    public function pohonIndikatorSastraDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            indikator_sastra_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data indikator sasaran strategis berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator sastra delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    public function pohonSasproStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonSaspro($request, null);

        try {
            DB::transaction(fn () => saspro_new::create($this->existingPayload('sakip_saspro_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran program berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon saspro store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan sasaran program.');
        }
    }

    public function pohonSasproUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $saspro = saspro_new::findOrFail($id);
        $validated = $this->validatePohonSaspro($request, $id);

        try {
            DB::transaction(fn () => $saspro->update($this->existingPayload('sakip_saspro_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran program berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon saspro update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui sasaran program.');
        }
    }

    public function pohonSasproDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            saspro_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data sasaran program berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon saspro delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    public function pohonIndikatorSasproStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonIndikatorSaspro($request, null);

        try {
            DB::transaction(fn () => saspro_indikator_new::create($this->existingPayload('indikator_saspro', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran program berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator saspro store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan indikator sasaran program.');
        }
    }

    public function pohonIndikatorSasproUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $indikator = saspro_indikator_new::findOrFail($id);
        $validated = $this->validatePohonIndikatorSaspro($request, $id);

        try {
            DB::transaction(fn () => $indikator->update($this->existingPayload('indikator_saspro', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran program berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator saspro update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui indikator sasaran program.');
        }
    }

    public function pohonIndikatorSasproDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            saspro_indikator_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data indikator sasaran program berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator saspro delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    // ---------------------------------------------------------------------
    // Validasi dan payload
    // ---------------------------------------------------------------------

    private function validateBidang(Request $request): array
    {
        return $request->validate([
            'id' => ['nullable', 'integer', Rule::exists('sinori_sakip_bidang', 'id')],
            'bidang_nama' => ['required', 'string', 'max:255'],
            'bidang_level' => ['required', 'integer', 'min:0'],
            'bidang_lokasi' => ['required', 'integer', 'between:1,5'],
            'rumpun' => ['required', 'integer', 'min:0'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
        ]);
    }

    private function validateSaspro(Request $request): array
    {
        return $request->validate([
            'link' => ['required', 'integer', Rule::exists('sinori_sakip_bidang', 'id')],
            'saspro_nama' => ['required', 'string', 'max:255'],
            'penjelasan_saspro' => ['nullable', 'string'],
            'saspro_penjelasan' => ['nullable', 'string'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'target' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['nullable', 'integer', Rule::in(self::STATUS_VALUES)],
        ]);
    }

    private function sasproPayload(array $validated, ?Saspro $existing = null): array
    {
        return [
            'link' => $validated['link'] ?? $existing?->link,
            'saspro_nama' => $validated['saspro_nama'] ?? $existing?->saspro_nama,
            'saspro_penjelasan' => $validated['penjelasan_saspro']
                ?? $validated['saspro_penjelasan']
                ?? $existing?->saspro_penjelasan,
            'lingkup' => $validated['lingkup'] ?? $existing?->lingkup ?? $this->defaultLingkupValue(),
            'target' => $validated['target'] ?? $existing?->target,
            'tahun' => $validated['tahun'] ?? $existing?->tahun,
            'hide' => $validated['hide'] ?? $existing?->hide ?? 0,
        ];
    }

    private function indikatorPayload(Request $request, ?Indikator $existing = null): array
    {
        $isUpdate = $existing !== null;

        $validated = $request->validate([
            'bidang' => [$isUpdate ? 'nullable' : 'required', 'integer', Rule::exists('sinori_sakip_bidang', 'id')],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'id_saspro' => [$isUpdate ? 'nullable' : 'required', 'integer', Rule::exists('sinori_sakip_saspro', 'id')],
            'indikator_nama' => ['required', 'string', 'max:255'],
            'indikator_pembilang' => ['nullable', 'string', 'max:255'],
            'indikator_penyebut' => ['nullable', 'string', 'max:255'],
            'indikator_penjelasan' => ['nullable', 'string'],
            'sub_indikator' => ['nullable', 'string'],
            'tahun' => ['nullable', 'string', 'max:10'],
            'indikator_penghitungan' => ['nullable', 'string'],
            'tren' => ['nullable', 'string', 'max:255'],
        ]);

        $bidangId = $validated['bidang'] ?? $existing?->link;
        $sasproId = $validated['id_saspro'] ?? $existing?->id_saspro;

        if ($bidangId && $sasproId && ! Saspro::whereKey($sasproId)->where('link', $bidangId)->exists()) {
            throw ValidationException::withMessages([
                'id_saspro' => 'Saspro yang dipilih tidak sesuai dengan bidang.',
            ]);
        }

        return [
            'link' => $bidangId,
            'lingkup' => $validated['lingkup'] ?? $existing?->lingkup ?? $this->defaultLingkupValue(),
            'id_saspro' => $sasproId,
            'indikator_nama' => $validated['indikator_nama'],
            'indikator_pembilang' => $validated['indikator_pembilang'] ?? $existing?->indikator_pembilang,
            'indikator_penyebut' => $validated['indikator_penyebut'] ?? $existing?->indikator_penyebut,
            'indikator_penjelasan' => $validated['indikator_penjelasan'] ?? $existing?->indikator_penjelasan,
            'sub_indikator' => $validated['sub_indikator'] ?? $existing?->sub_indikator,
            'indikator_penghitungan' => $validated['indikator_penghitungan'] ?? $existing?->indikator_penghitungan,
            'tahun' => $validated['tahun'] ?? $existing?->tahun,
            'tren' => $validated['tren'] ?? $existing?->tren,
        ];
    }

    private function validatePohonSastra(Request $request, ?string $id): array
    {
        return $request->validate([
            'id_sastra' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('sakip_sastra_new', 'id_sastra')->ignore($id, 'id_sastra'),
            ],
            'nama_sastra' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'target' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validatePohonIndikatorSastra(Request $request, ?string $id): array
    {
        return $request->validate([
            'kode_indikator' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('indikator_sastra', 'kode_indikator')->ignore($id, 'kode_indikator'),
            ],
            'kode_sastra' => ['required', 'string', Rule::exists('sakip_sastra_new', 'id_sastra')],
            'nama_indikator' => ['required', 'string', 'max:255'],
            'deskripsi_indikator_sastra' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validatePohonSaspro(Request $request, ?string $id): array
    {
        return $request->validate([
            'id_saspro' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('sakip_saspro_new', 'id_saspro')->ignore($id, 'id_saspro'),
            ],
            'id_sastra' => ['required', 'string', Rule::exists('sakip_sastra_new', 'id_sastra')],
            'nama_saspro' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validatePohonIndikatorSaspro(Request $request, ?string $id): array
    {
        $validated = $request->validate([
            'kode_indikator' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('indikator_saspro', 'kode_indikator')->ignore($id, 'kode_indikator'),
            ],
            'kode_saspro' => ['required', 'string', Rule::exists('sakip_saspro_new', 'id_saspro')],
            'nama_indikator' => ['required', 'string', 'max:255'],
            'deskripsi_indikator_saspro' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['kode_sastra'] = DB::table('sakip_saspro_new')
            ->where('id_saspro', $validated['kode_saspro'])
            ->value('id_sastra');

        if (! $validated['kode_sastra']) {
            throw ValidationException::withMessages([
                'kode_saspro' => 'Saspro yang dipilih tidak memiliki relasi Sastra.',
            ]);
        }

        return $validated;
    }

    // ---------------------------------------------------------------------
    // Helper umum
    // ---------------------------------------------------------------------

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    private function search(Request $request): string
    {
        return trim((string) $request->input('search', ''));
    }

    private function pohonSearch(Request $request): string
    {
        return trim((string) $request->input('pohon_search', $request->input('search', '')));
    }

    private function selectExistingColumns(string $table, array $columns, ?string $alias = null): array
    {
        return collect($columns)
            ->map(function ($column) use ($table, $alias) {
                if (! Schema::hasColumn($table, $column)) {
                    return DB::raw("NULL as {$column}");
                }

                return $alias ? "{$alias}.{$column}" : $column;
            })
            ->all();
    }

    private function orderExpression(string $table, string $fallbackColumn, ?string $alias = null): string
    {
        if (! Schema::hasColumn($table, 'urutan')) {
            return $fallbackColumn;
        }

        $urutanColumn = $alias ? "{$alias}.urutan" : 'urutan';

        return "COALESCE({$urutanColumn}, 999999), {$fallbackColumn}";
    }

    private function existingPayload(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->all();
    }

    private function lingkupOptions(): array
    {
        return Bidang::select(['rumpun', 'bidang_nama'])
            ->where('bidang_level', '1')
            ->where('hide', '0')
            ->whereNotNull('rumpun')
            ->get()
            ->filter(fn ($bidang) => trim((string) $bidang->rumpun) !== '')
            ->unique(fn ($bidang) => (string) $bidang->rumpun)
            ->sortBy(fn ($bidang) => str_pad((string) $bidang->rumpun, 10, '0', STR_PAD_LEFT))
            ->map(fn ($bidang) => [
                'value' => (string) $bidang->rumpun,
                'label' => (string) $bidang->bidang_nama,
            ])
            ->values()
            ->all();
    }

    private function lingkupValues(): array
    {
        $values = collect($this->lingkupOptions())
            ->pluck('value')
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        return $values ?: [$this->defaultLingkupValue()];
    }

    private function defaultLingkupValue(): string
    {
        $firstOption = collect($this->lingkupOptions())->first();

        return (string) ($firstOption['value'] ?? '0');
    }

    private function statusOptions(): array
    {
        return [
            ['value' => '0', 'label' => 'Tampil'],
            ['value' => '1', 'label' => 'Sembunyikan'],
        ];
    }

    private function isAdmin(): bool
    {
        return app(SatkerAccessService::class)->isAdmin();
    }

    private function ensureAdmin(): void
    {
        abort_unless($this->isAdmin(), 403);
    }
}
