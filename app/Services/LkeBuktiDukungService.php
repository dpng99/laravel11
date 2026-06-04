<?php

namespace App\Services;

use App\Models\{
    absen_pm,
    ba_pleno,
    ba_praeval,
    Dipa,
    Iku,
    LheAkip,
    lke_komponen,
    Lkjip,
    memo_datakinerja,
    memo_lkjip,
    MonevRenaksi,
    nodis_datakinerja,
    nodis_eval_sakip,
    nodis_p_sakip,
    notulensi_pm,
    Pk,
    PokinRanwal,
    Renaksi,
    Renja,
    Renstra,
    reward_punish,
    Rkakl,
    sampel_rekom,
    sample_skp,
    sk_pk,
    sk_pm,
    ss_laporanapp,
    ss_laporanweb,
    ss_perencanaan,
    tar_lkjip,
    tar_pm,
    TlLheAkip,
    RapatStaffEka
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LkeBuktiDukungService
{
    public function modelMapping(): array
    {
        return [
            1 => Renstra::class, 2 => Renja::class, 3 => Renaksi::class, 4 => Rkakl::class,
            5 => Dipa::class, 6 => Pk::class, 7 => Pk::class, 8 => Iku::class, 9 => Iku::class,
            10 => Lkjip::class, 11 => Lkjip::class, 12 => LheAkip::class,
            13 => RapatStaffEka::class, 14 => RapatStaffEka::class,
            15 => LheAkip::class, 16 => LheAkip::class, 17 => TlLheAkip::class,
            18 => MonevRenaksi::class, 19 => MonevRenaksi::class, 20 => PokinRanwal::class,
            21 => Renstra::class, 22 => Lkjip::class, 23 => sample_skp::class,
            24 => sk_pm::class, 25 => sk_pk::class, 26 => absen_pm::class,
            27 => notulensi_pm::class, 28 => nodis_p_sakip::class, 29 => nodis_eval_sakip::class,
            30 => memo_datakinerja::class, 31 => nodis_datakinerja::class, 32 => reward_punish::class,
            33 => sampel_rekom::class, 34 => ss_perencanaan::class, 35 => ss_laporanweb::class,
            36 => ss_laporanapp::class, 37 => tar_lkjip::class, 38 => tar_lkjip::class,
            39 => memo_lkjip::class, 40 => memo_lkjip::class, 41 => tar_pm::class,
            42 => ba_praeval::class, 43 => ba_pleno::class, 44 => LheAkip::class,
        ];
    }

    public function triwulanMapping(): array
    {
        return [
            10 => 'TW 1', 11 => 'TW 2', 12 => 'TW 4', 13 => 'TW 1', 14 => 'TW 2',
            18 => 'TW 1', 19 => 'TW 2', 37 => 'TW 1', 38 => 'TW 2',
            39 => 'TW 1', 40 => 'TW 2',
        ];
    }

    public function grouped(string $idSatker, $tahun)
    {
        $hierarki = lke_komponen::with([
            'subKomponens' => fn ($query) => $query->orderBy('kode'),
            'subKomponens.kriterias' => fn ($query) => $query->orderBy('kode'),
            'subKomponens.kriterias.buktiDukungs' => fn ($query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $uploaded = DB::table('bukti_dukung')
            ->where('id_satker', $idSatker)
            ->whereNotNull('link_bukti_dukung')
            ->where('link_bukti_dukung', '<>', '')
            ->orderByDesc('id')
            ->get();

        $sourceCache = [];
        $flat = collect();

        foreach ($hierarki as $komponen) {
            foreach ($komponen->subKomponens as $sub) {
                foreach ($sub->kriterias as $kriteria) {
                    $buktiList = [];

                    foreach ($kriteria->buktiDukungs as $buktiRef) {
                        $kode = (int) $buktiRef->id;
                        $expectedPeriod = $this->expectedPeriod($kode, $tahun);
                        $uploadedEvidence = $this->uploadedEvidence($uploaded, $kode, (string) $kriteria->kode, $expectedPeriod);
                        $status = 'Tidak Ada';
                        $fileLink = null;

                        if ($uploadedEvidence) {
                            $status = 'Ada';
                            $fileLink = url("/file/view/{$idSatker}/" . rawurlencode($uploadedEvidence->link_bukti_dukung));
                        } elseif (isset($this->modelMapping()[$kode])) {
                            if (!array_key_exists($kode, $sourceCache)) {
                                $sourceCache[$kode] = $this->sourceDocument($kode, $idSatker, $tahun);
                            }

                            if ($sourceCache[$kode]) {
                                $status = 'Tersedia di Sistem (Belum Verif)';
                            }
                        }

                        $buktiList[] = [
                            'kode_bukti' => $kode,
                            'nama_dokumen' => $buktiRef->dokumen,
                            'status' => $status,
                            'file_link' => $fileLink,
                            'periode' => $expectedPeriod,
                            'is_manual' => (bool) $uploadedEvidence,
                        ];
                    }

                    $flat->push((object) [
                        'id_komponen' => $komponen->id,
                        'nama_komponen' => $komponen->nama,
                        'id_sub_komponen' => $sub->kode,
                        'nama_subkomponen' => $sub->nama,
                        'id_kriteria' => $kriteria->id,
                        'kode_kriteria' => $kriteria->kode,
                        'nama_kriteria' => $kriteria->nama,
                        'bukti_list' => $buktiList,
                    ]);
                }
            }
        }

        return $flat->groupBy('id_komponen')->map(fn ($subItems) => $subItems->groupBy('id_sub_komponen'));
    }

    public function sourceDocument(int $kode, string $idSatker, $tahun)
    {
        $mapping = $this->modelMapping();
        if (! isset($mapping[$kode])) {
            return null;
        }

        $modelClass = $mapping[$kode];
        $model = new $modelClass();
        $table = $model->getTable();
        $query = $modelClass::query()->where('id_satker', $idSatker);
        $period = $this->expectedPeriod($kode, $tahun);

        if (Schema::hasColumn($table, 'id_periode')) {
            $query->where('id_periode', $period);
        }

        if (isset($this->triwulanMapping()[$kode])) {
            $this->applyTriwulanFilter($query, $table, $this->triwulanMapping()[$kode]);
        }

        if (Schema::hasColumn($table, 'id_perubahan')) {
            if (in_array($kode, [1, 6, 8, 18], true)) {
                $query->where('id_perubahan', 0);
            }

            $query->orderByRaw('CAST(id_perubahan AS UNSIGNED) DESC');
        }

        $this->whereHasFilename($query, $table);
        $this->applyLatestOrdering($query, $table);

        return $query->first();
    }

    public function filenameFromSource($row): ?string
    {
        if (! $row) {
            return null;
        }

        foreach (['id_filename', 'file', 'nama_file', 'link_bukti_dukung'] as $column) {
            $value = trim((string) ($row->{$column} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function expectedPeriod(int $kode, $tahun): string
    {
        $year = $this->yearValue($tahun);
        $offsets = [
            7 => -1, 12 => -1, 15 => -1, 17 => -1,
            22 => -2, 44 => -2,
        ];

        return (string) ($year + ($offsets[$kode] ?? 0));
    }

    private function uploadedEvidence($uploaded, int $kode, string $kodeKriteria, string $expectedPeriod)
    {
        $withCode = $uploaded
            ->filter(fn ($row) => (string) ($row->kode_bukti ?? '') === (string) $kode)
            ->filter(fn ($row) => $this->filenameMatchesPeriod($row->link_bukti_dukung, $expectedPeriod))
            ->values();

        $exact = $withCode->first(fn ($row) => (string) ($row->id_kriteria ?? '') === $kodeKriteria);
        if ($exact) {
            return $exact;
        }

        if ($withCode->isNotEmpty()) {
            return $withCode->first();
        }

        return $uploaded
            ->filter(fn ($row) => (string) ($row->id_kriteria ?? '') === $kodeKriteria)
            ->filter(fn ($row) => in_array((string) ($row->kode_bukti ?? ''), ['', '0'], true))
            ->filter(fn ($row) => $this->filenameMatchesPeriod($row->link_bukti_dukung, $expectedPeriod))
            ->first(fn ($row) => $this->filenameLooksLikeKode($row->link_bukti_dukung, $kode));
    }

    private function filenameMatchesPeriod(?string $filename, string $expectedPeriod): bool
    {
        $filename = (string) $filename;
        if (! preg_match_all('/(?:19|20)\d{2}/', $filename, $matches)) {
            return true;
        }

        return in_array($expectedPeriod, $matches[0], true);
    }

    private function filenameLooksLikeKode(?string $filename, int $kode): bool
    {
        $filename = strtolower((string) $filename);
        $prefixes = [
            1 => 'renstra', 2 => 'renja', 3 => 'renaksi', 4 => 'rkakl', 5 => 'dipa',
            6 => 'pk', 7 => 'pk', 8 => 'iku', 9 => 'iku', 10 => 'lkjip', 11 => 'lkjip',
            12 => 'lkjip', 13 => 'rastaff', 14 => 'rastaff', 15 => 'lhe', 16 => 'lhe',
            17 => 'tl_lhe_akip', 18 => 'monev', 19 => 'monev', 20 => 'pokin_ranwal',
            21 => 'renstra', 22 => 'lkjip', 23 => 'sampel_skp', 24 => 'tim_pm',
            25 => 'tim_evaluator', 26 => 'absen_pm', 27 => 'notulensi_bimtek',
            28 => 'nodis_penyelenggaraan_akip', 29 => 'nodis_evaluasi_akip',
            30 => 'memo_data_kinerja', 31 => 'nodis_data_kinerja', 32 => 'reward_punishment',
            33 => 'sampel_rekom', 34 => 'ss_perencanaan', 35 => 'ss_laporan_web',
            36 => 'ss_laporan_app', 37 => 'tar_lkjip', 38 => 'tar_lkjip',
            39 => 'memo_lkjip', 40 => 'memo_lkjip', 41 => 'tar_pm',
            42 => 'ba_praevaluasi', 43 => 'ba_pleno', 44 => 'lhe',
        ];

        return isset($prefixes[$kode]) && str_contains($filename, $prefixes[$kode]);
    }

    private function applyTriwulanFilter(Builder $query, string $table, string $triwulan): void
    {
        $hasIdTriwulan = Schema::hasColumn($table, 'id_triwulan');
        $hasTriwulan = Schema::hasColumn($table, 'triwulan');

        if (! $hasIdTriwulan && ! $hasTriwulan) {
            return;
        }

        $angka = trim(str_replace('TW', '', $triwulan));

        $query->where(function ($query) use ($hasIdTriwulan, $hasTriwulan, $triwulan, $angka) {
            if ($hasIdTriwulan) {
                $query->where('id_triwulan', $triwulan)
                    ->orWhere('id_triwulan', $angka)
                    ->orWhere('id_triwulan', 'TW_' . $angka);
            } elseif ($hasTriwulan) {
                $query->where('triwulan', $angka)
                    ->orWhere('triwulan', $triwulan);
            }
        });
    }

    private function whereHasFilename(Builder $query, string $table): void
    {
        $columns = collect(['id_filename', 'file', 'nama_file', 'link_bukti_dukung'])
            ->filter(fn ($column) => Schema::hasColumn($table, $column))
            ->values();

        if ($columns->isEmpty()) {
            return;
        }

        $query->where(function ($query) use ($columns) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}(function ($query) use ($column) {
                    $query->whereNotNull($column)->where($column, '<>', '');
                });
            }
        });
    }

    private function applyLatestOrdering(Builder $query, string $table): void
    {
        foreach (['id_tglupload', 'tgl_upload', 'updated_at', 'created_at', 'id'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $query->orderByDesc($column);
                return;
            }
        }
    }

    private function yearValue($tahun): int
    {
        preg_match('/\d{4}/', (string) $tahun, $matches);

        return isset($matches[0]) ? (int) $matches[0] : (int) date('Y');
    }
}
