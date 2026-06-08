<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Models\Renstra;
use App\Models\Iku;
use App\Models\Renja;
use App\Models\Rkakl;
use App\Models\Dipa;
use App\Models\Renaksi;
use App\Models\Kep;
use App\Services\SatkerAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Cek apakah tahun sudah dipilih
        // if (!session()->has('tahun_terpilih')) {
        //     return redirect()->route('pilih.tahun');
        // }

        // Set tahun_terpilih ke tahun sekarang jika belum ada di session
        $tahun = session('tahun_terpilih', date('Y'));
        session(['tahun_terpilih' => $tahun]);
        $idSatker = session('id_satker'); // Ambil id_satker dari session
        // $periode = 'P2'; // Periode yang dicek

        $periode = ((string) $tahun === "2024") ? "P1" : "P2";

        // Lanjutkan dengan logika untuk menampilkan data berdasarkan tahun
        // return view('dashboard', ['tahun' => $tahun]);

        $pengumuman = DB::table('sinori_sakip_inbox')->get();
        $renstraTerisi = Renstra::where('id_satker', $idSatker)->where('id_periode', $periode)->exists();
        $ikuTerisi = Iku::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $renjaTerisi = Renja::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $rkaklTerisi = Rkakl::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $dipaTerisi = Dipa::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $rencanaAksiTerisi = Renaksi::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $keputusanTimSakipTerisi = Kep::where('id_satker', $idSatker)->where('id_tahun', $tahun)->exists();

        // Memeriksa tahun dan menentukan id_periode
        if ($tahun == "2024") {
            $id_periode = "P1";
        } else {
            $id_periode = "P2";
        }
    // pastikan kolom bernama `keputusan`, sesuaikan jika beda

        // Kirim data ke view
        // return view('dashboard', compact('pengumuman', 'jumlahAturan', 'data', ['tahun' => $tahun]));
        return Inertia::render('Dashboard', [
            'pengumuman' => $pengumuman,
            'tahun' => $tahun,
            'pohonKinerja' => $this->pohonKinerjaData($tahun, $request),
            'pohonPerPageOptions' => [10, 25, 50],
            'renstraTerisi' => $renstraTerisi,
            'ikuTerisi' => $ikuTerisi,
            'renjaTerisi' => $renjaTerisi,
            'rkaklTerisi' => $rkaklTerisi,
            'dipaTerisi' => $dipaTerisi,
            'rencanaAksiTerisi' => $rencanaAksiTerisi,
            'keputusanTimSakipTerisi' => $keputusanTimSakipTerisi,
            'documentUploads' => $this->documentUploadData($tahun, $periode),

        ]);
    }

    private function documentUploadData(string $tahun, string $renstraPeriod): array
    {
        $access = app(SatkerAccessService::class);
        $satkers = $access->scopedSatkerQuery()
            ->select('id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level')
            ->orderBy('id_kejati')
            ->orderBy('id_kejari')
            ->orderBy('id_satker')
            ->get()
            ->keyBy(fn ($satker) => (string) $satker->id_satker);

        $satkerIds = $satkers->keys()->all();

        $documents = [
            'renstra' => [
                'label' => 'Renstra',
                'table' => 'sinori_sakip_renstra',
                'period_column' => 'id_periode',
                'period_value' => $renstraPeriod,
            ],
            'iku' => [
                'label' => 'IKU',
                'table' => 'sinori_sakip_iku',
                'period_column' => 'id_periode',
                'period_value' => $tahun,
            ],
            'renja' => [
                'label' => 'Renja',
                'table' => 'sinori_sakip_renja',
                'period_column' => 'id_periode',
                'period_value' => $tahun,
            ],
            'rkakl' => [
                'label' => 'RKAKL',
                'table' => 'sinori_sakip_rkakl',
                'period_column' => 'id_periode',
                'period_value' => $tahun,
            ],
            'dipa' => [
                'label' => 'DIPA',
                'table' => 'sinori_sakip_dipa',
                'period_column' => 'id_periode',
                'period_value' => $tahun,
            ],
            'renaksi' => [
                'label' => 'Rencana Aksi',
                'table' => 'sinori_sakip_renaksi',
                'period_column' => 'id_periode',
                'period_value' => $tahun,
            ],
        ];

        return collect($documents)->mapWithKeys(function (array $document, string $key) use ($satkers, $satkerIds) {
            $rows = collect();

            if (! empty($satkerIds) && Schema::hasTable($document['table'])) {
                $query = DB::table($document['table'])
                    ->whereIn('id_satker', $satkerIds)
                    ->where($document['period_column'], $document['period_value'])
                    ->whereNotNull('id_filename')
                    ->where('id_filename', '!=', '');

                if (Schema::hasColumn($document['table'], 'id_perubahan')) {
                    $query->orderByRaw('CAST(id_perubahan AS UNSIGNED) DESC');
                }

                if (Schema::hasColumn($document['table'], 'id_tglupload')) {
                    $query->orderByDesc('id_tglupload');
                }

                $rows = $query->get();
            }

            $rowsBySatker = $rows->groupBy(fn ($row) => (string) $row->id_satker);

            $latestUploads = $rowsBySatker->map(function ($satkerRows, string $satkerId) use ($satkers) {
                $latest = $satkerRows->first();
                $satker = $satkers->get($satkerId);

                return [
                    'id_satker' => $satkerId,
                    'satkernama' => str_replace('_', ' ', (string) ($satker->satkernama ?? '-')),
                    'id_kejati' => $satker->id_kejati ?? null,
                    'id_kejari' => $satker->id_kejari ?? null,
                    'id_sakip_level' => $satker->id_sakip_level ?? null,
                    'filename' => $latest->id_filename ?? null,
                    'period' => $latest->id_periode ?? null,
                    'revision' => $latest->id_perubahan ?? null,
                    'uploaded_at' => $latest->id_tglupload ?? null,
                    'document_count' => $satkerRows->count(),
                ];
            })->sortBy([
                ['id_kejati', 'asc'],
                ['id_kejari', 'asc'],
                ['id_satker', 'asc'],
            ])->values();

            return [
                $key => [
                    'label' => $document['label'],
                    'total_satkers' => count($satkerIds),
                    'uploaded_satkers' => $latestUploads->count(),
                    'rows' => $latestUploads,
                ],
            ];
        })->all();
    }

    private function pohonKinerjaData(string $tahun, Request $request)
    {
        $sastras = $this->basePohonQuery('sakip_sastra_new', $tahun)
            ->select($this->selectPohonColumns('sakip_sastra_new', [
                'id_sastra',
                'nama_sastra',
                'deskripsi',
            ]))
            ->orderByRaw($this->orderExpression('sakip_sastra_new', 'id_sastra'))
            ->paginate($this->perPage($request), ['*'], 'pohon_page')
            ->withQueryString();

        $indikatorSastras = $this->basePohonQuery('indikator_sastra', $tahun)
            ->select($this->selectPohonColumns('indikator_sastra', [
                'kode_indikator',
                'kode_sastra',
                'nama_indikator',
                'deskripsi_indikator_sastra',
            ]))
            ->orderBy('kode_sastra')
            ->orderByRaw($this->orderExpression('indikator_sastra', 'kode_indikator'))
            ->get()
            ->groupBy('kode_sastra');

        $saspros = $this->basePohonQuery('sakip_saspro_new', $tahun)
            ->select($this->selectPohonColumns('sakip_saspro_new', [
                'id_saspro',
                'id_sastra',
                'nama_saspro',
                'deskripsi',
            ]))
            ->orderBy('id_sastra')
            ->orderByRaw($this->orderExpression('sakip_saspro_new', 'id_saspro'))
            ->get()
            ->groupBy('id_sastra');

        $indikatorSaspros = $this->basePohonQuery('indikator_saspro', $tahun)
            ->select($this->selectPohonColumns('indikator_saspro', [
                'kode_indikator',
                'kode_sastra',
                'kode_saspro',
                'nama_indikator',
                'deskripsi_indikator_saspro',
            ]))
            ->orderBy('kode_sastra')
            ->orderBy('kode_saspro')
            ->orderByRaw($this->orderExpression('indikator_saspro', 'kode_indikator'))
            ->get()
            ->groupBy('kode_saspro');

        $sastras->setCollection($sastras->getCollection()->map(function ($sastra) use ($indikatorSastras, $saspros, $indikatorSaspros) {
            $sastraRows = $indikatorSastras->get($sastra->id_sastra, collect());
            $sasproRows = $saspros->get($sastra->id_sastra, collect());

            return [
                'id' => (string) $sastra->id_sastra,
                'nama' => (string) $sastra->nama_sastra,
                'deskripsi' => $sastra->deskripsi,
                'target' => $sastra->target ?? null,
                'tahun' => $sastra->tahun ?? null,
                'lingkup' => $sastra->lingkup ?? null,
                'indikator' => $sastraRows->map(fn ($indikator) => [
                    'id' => (string) $indikator->kode_indikator,
                    'nama' => (string) $indikator->nama_indikator,
                    'tahun' => $indikator->tahun ?? null,
                    'lingkup' => $indikator->lingkup ?? null,
                ])->values(),
                'saspro' => $sasproRows->map(function ($saspro) use ($indikatorSaspros) {
                    $indikatorRows = $indikatorSaspros->get($saspro->id_saspro, collect());

                    return [
                        'id' => (string) $saspro->id_saspro,
                        'nama' => (string) $saspro->nama_saspro,
                        'deskripsi' => $saspro->deskripsi,
                        'tahun' => $saspro->tahun ?? null,
                        'lingkup' => $saspro->lingkup ?? null,
                        'indikator' => $indikatorRows->map(fn ($indikator) => [
                            'id' => (string) $indikator->kode_indikator,
                            'nama' => (string) $indikator->nama_indikator,
                            'tahun' => $indikator->tahun ?? null,
                            'lingkup' => $indikator->lingkup ?? null,
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values());

        return $sastras;
    }

    private function basePohonQuery(string $table, ?string $tahun = null)
    {
        return DB::table($table)
            ->when($tahun && Schema::hasColumn($table, 'tahun'), function ($query) use ($tahun) {
                $query->where(function ($query) use ($tahun) {
                    $query->whereNull('tahun')
                        ->orWhere('tahun', '')
                        ->orWhere('tahun', $tahun);
                });
            })
            ->when(Schema::hasColumn($table, 'hide'), function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('hide')
                        ->orWhere('hide', '')
                        ->orWhere('hide', '0')
                        ->orWhere('hide', 0);
                });
            });
    }

    private function selectPohonColumns(string $table, array $baseColumns): array
    {
        $columns = $baseColumns;

        $optionalColumns = ['link', 'lingkup', 'tahun', 'hide', 'urutan'];

        if ($table === 'sakip_sastra_new') {
            $optionalColumns[] = 'target';
        }

        foreach ($optionalColumns as $column) {
            $columns[] = Schema::hasColumn($table, $column)
                ? $column
                : DB::raw("NULL as {$column}");
        }

        return $columns;
    }

    private function orderExpression(string $table, string $fallbackColumn): string
    {
        if (Schema::hasColumn($table, 'urutan')) {
            return "COALESCE(urutan, 999999), {$fallbackColumn}";
        }

        return $fallbackColumn;
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }
}
