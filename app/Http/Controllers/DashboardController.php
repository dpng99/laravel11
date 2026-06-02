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

        if ($tahun == "2024") {
            $periode = "P1";
        } elseif ($tahun >= "2025" && $tahun <= "2029") {
            $periode = "P2";
        }

        // Lanjutkan dengan logika untuk menampilkan data berdasarkan tahun
        // return view('dashboard', ['tahun' => $tahun]);

        $pengumuman = DB::table('sinori_sakip_inbox')->get();
        $jumlahAturan = DB::table('sinori_sakip_literasi')->count(); // Hitung jumlah aturan
        // Data untuk chart
        $id = DB::table('sinori_login')->where('id_satker', $idSatker)->first();
        $data = DB::table('sinori_login')
            ->where('id_kejati', $id->id_kejati)
            ->get();

        $renstraTerisi = Renstra::where('id_satker', $idSatker)->where('id_periode', $periode)->exists();
        $ikuTerisi = Iku::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $renjaTerisi = Renja::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $rkaklTerisi = Rkakl::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $dipaTerisi = Dipa::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $rencanaAksiTerisi = Renaksi::where('id_satker', $idSatker)->where('id_periode', $tahun)->exists();
        $keputusanTimSakipTerisi = Kep::where('id_satker', $idSatker)->where('id_tahun', $tahun)->exists();

       $kepList = DB::table('sinori_sakip_keputusan')
            ->whereIn('id_satker', $data->pluck('id_satker'))
            ->where('id_tahun', $tahun)
            ->pluck('id_filesurat', 'id_satker');
        // dd($kepList);
        // Menyelaraskan urutan kepList dengan satker
        $sortedKepList = $data->pluck('id_satker')->map(function ($id) use ($kepList) {
            return $kepList[$id] ?? null;
        });
        // dd($sortedkepList);
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
            'jumlahAturan' => $jumlahAturan,
            'data' => $data,
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
            'sortedKepList' => $sortedKepList,

        ]);
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
