<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemCheckService
{
    public function satkers(?string $keyword = null, int $limit = 25): Collection
    {
        $keyword = trim((string) $keyword);

        return app(SatkerAccessService::class)
            ->baseSatkerQuery()
            ->select('id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('id_satker', 'like', "%{$keyword}%")
                        ->orWhere('satkernama', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('id_kejati')
            ->orderBy('id_kejari')
            ->limit($limit)
            ->get()
            ->map(fn ($satker) => [
                'id_satker' => (string) $satker->id_satker,
                'satkernama' => str_replace('_', ' ', (string) $satker->satkernama),
                'id_kejati' => $satker->id_kejati,
                'id_kejari' => $satker->id_kejari,
                'id_sakip_level' => $satker->id_sakip_level,
            ]);
    }

    public function apiHealth(): array
    {
        $steps = [];

        $requiredConfigs = [
            'Client ID' => config('google.client_id'),
            'Client Secret' => config('google.client_secret'),
            'Refresh Token' => config('google.refresh_token'),
            'Folder ID' => config('filesystems.disks.google.folder_id'),
        ];

        $missingConfigs = collect($requiredConfigs)
            ->filter(fn ($value) => blank($value))
            ->keys()
            ->values()
            ->all();

        $steps[] = [
            'name' => 'Konfigurasi Google Drive',
            'status' => empty($missingConfigs) ? 'success' : 'failed',
            'message' => empty($missingConfigs)
                ? 'Konfigurasi utama tersedia.'
                : 'Konfigurasi belum lengkap: ' . implode(', ', $missingConfigs),
        ];

        if (! empty($missingConfigs)) {
            return $this->apiHealthResponse($steps);
        }

        try {
            $client = app(GoogleService::class)->getClient();
            $token = $client->fetchAccessTokenWithRefreshToken();

            if (isset($token['error'])) {
                throw new \RuntimeException($token['error_description'] ?? $token['error']);
            }

            $steps[] = [
                'name' => 'Token Google API',
                'status' => 'success',
                'message' => 'Token berhasil diperbarui.',
            ];
        } catch (Throwable $exception) {
            $steps[] = [
                'name' => 'Token Google API',
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];

            return $this->apiHealthResponse($steps);
        }

        try {
            $files = Storage::disk('google')->files('/');
            $steps[] = [
                'name' => 'Akses Baca Drive',
                'status' => 'success',
                'message' => 'Berhasil membaca root folder.',
                'meta' => ['jumlah_file' => count($files)],
            ];
        } catch (Throwable $exception) {
            $steps[] = [
                'name' => 'Akses Baca Drive',
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];

            return $this->apiHealthResponse($steps);
        }

        $testFileName = 'diagnostic_test_' . now()->format('Ymd_His') . '.txt';

        try {
            Storage::disk('google')->put($testFileName, 'Diagnostic test ' . now()->toIso8601String());
            $steps[] = [
                'name' => 'Akses Tulis Drive',
                'status' => 'success',
                'message' => "Berhasil mengunggah {$testFileName}.",
            ];
        } catch (Throwable $exception) {
            $steps[] = [
                'name' => 'Akses Tulis Drive',
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];

            return $this->apiHealthResponse($steps);
        }

        try {
            Storage::disk('google')->delete($testFileName);
            $steps[] = [
                'name' => 'Akses Hapus Drive',
                'status' => 'success',
                'message' => "Berhasil menghapus {$testFileName}.",
            ];
        } catch (Throwable $exception) {
            $steps[] = [
                'name' => 'Akses Hapus Drive',
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ];
        }

        return $this->apiHealthResponse($steps);
    }

    public function documentCheck(string $idSatker, $tahun): array
    {
        $satkerIds = $this->satkerCandidates($idSatker);
        $satker = $this->satker($satkerIds);
        $databaseResult = $this->databaseDocuments($satkerIds, $tahun);
        $databaseDocuments = $databaseResult['documents'];
        $driveIndex = $this->driveIndex($satkerIds);

        $rows = $databaseDocuments
            ->map(function ($document) use ($driveIndex) {
                $match = $this->findDriveMatch($document['filename'], $driveIndex);

                return [
                    ...$document,
                    'exists_on_drive' => $match !== null,
                    'matched_path' => $match,
                    'status' => $match ? 'ada' : 'hilang',
                ];
            })
            ->values();

        $found = $rows->where('exists_on_drive', true)->count();
        $missing = $rows->where('exists_on_drive', false)->count();

        return [
            'satker' => $satker,
            'tahun' => (string) $tahun,
            'summary' => [
                'total_database' => $rows->count(),
                'ada_di_drive' => $found,
                'tidak_ada_di_drive' => $missing,
                'folder_dicek' => count($driveIndex['folders']),
                'file_drive_terbaca' => count($driveIndex['paths']),
            ],
            'folders' => $driveIndex['folders'],
            'rows' => $rows,
            'skipped_sources' => $databaseResult['skipped_sources'],
        ];
    }

    private function apiHealthResponse(array $steps): array
    {
        return [
            'status' => collect($steps)->contains('status', 'failed') ? 'failed' : 'success',
            'checked_at' => now()->toIso8601String(),
            'steps' => $steps,
        ];
    }

    private function satkerCandidates(string $idSatker): array
    {
        $idSatker = trim($idSatker);
        $candidates = [$idSatker];

        if (ctype_digit($idSatker)) {
            $candidates[] = str_pad($idSatker, 6, '0', STR_PAD_LEFT);
            $candidates[] = (string) ((int) $idSatker);
        }

        return collect($candidates)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function satker(array $satkerIds): ?array
    {
        $satker = DB::table('sinori_login')
            ->whereIn('id_satker', $satkerIds)
            ->first(['id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level']);

        if (! $satker) {
            return null;
        }

        return [
            'id_satker' => (string) $satker->id_satker,
            'satkernama' => str_replace('_', ' ', (string) $satker->satkernama),
            'id_kejati' => $satker->id_kejati,
            'id_kejari' => $satker->id_kejari,
            'id_sakip_level' => $satker->id_sakip_level,
        ];
    }

    private function databaseDocuments(array $satkerIds, $tahun): array
    {
        $documents = collect();
        $skippedSources = [];

        foreach ($this->documentSources($tahun) as $source) {
            if (! Schema::hasTable($source['table'])) {
                $skippedSources[] = ['label' => $source['label'], 'reason' => 'Tabel tidak tersedia'];
                continue;
            }

            if (! Schema::hasColumn($source['table'], $source['file_column'])) {
                $skippedSources[] = ['label' => $source['label'], 'reason' => 'Kolom file tidak tersedia'];
                continue;
            }

            $query = DB::table($source['table'])
                ->whereIn('id_satker', $satkerIds)
                ->whereNotNull($source['file_column'])
                ->where($source['file_column'], '<>', '');

            if (! empty($source['period_column']) && Schema::hasColumn($source['table'], $source['period_column'])) {
                $query->where($source['period_column'], $source['period_value']);
            }

            $columns = $this->selectColumns($source['table'], $source['file_column']);

            $query->select($columns)
                ->orderByDesc(Schema::hasColumn($source['table'], 'id') ? 'id' : $source['file_column'])
                ->get()
                ->each(function ($row) use ($documents, $source) {
                    $filename = $this->filename((string) ($row->{$source['file_column']} ?? ''));

                    if ($filename === '') {
                        return;
                    }

                    $documents->push([
                        'document' => $source['label'],
                        'table' => $source['table'],
                        'file_column' => $source['file_column'],
                        'row_id' => $row->id ?? $row->no ?? null,
                        'id_satker' => (string) ($row->id_satker ?? ''),
                        'filename' => $filename,
                        'period' => $row->id_periode ?? $row->id_tahun ?? null,
                        'triwulan' => $row->id_triwulan ?? $row->triwulan ?? $row->TW ?? null,
                        'version' => $row->id_perubahan ?? null,
                        'uploaded_at' => $row->id_tglupload ?? $row->tgl_pengisian ?? $row->created_at ?? $row->updated_at ?? null,
                    ]);
                });
        }

        return [
            'documents' => $documents,
            'skipped_sources' => $skippedSources,
        ];
    }

    private function documentSources($tahun): array
    {
        $tahun = (string) $tahun;
        $renstraPeriod = $tahun === '2024' ? 'P1' : 'P2';

        return [
            ['label' => 'Keputusan Tim SAKIP', 'table' => 'sinori_sakip_keputusan', 'file_column' => 'id_filesurat', 'period_column' => 'id_tahun', 'period_value' => $tahun],
            ['label' => 'Renstra', 'table' => 'sinori_sakip_renstra', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $renstraPeriod],
            ['label' => 'IKU', 'table' => 'sinori_sakip_iku', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Renja', 'table' => 'sinori_sakip_renja', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'RKAKL', 'table' => 'sinori_sakip_rkakl', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'DIPA', 'table' => 'sinori_sakip_dipa', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Renaksi', 'table' => 'sinori_sakip_renaksi', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Perjanjian Kinerja', 'table' => 'pk', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'LKJiP', 'table' => 'sinori_sakip_lakip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Rapat Staff EKA', 'table' => 'sinori_sakip_rastaff', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'LHE AKIP', 'table' => 'lhe', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'TL LHE AKIP', 'table' => 'tl_lhe_akip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Monev Renaksi', 'table' => 'sinori_sakip_renaksieval', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Pokin Ranwal', 'table' => 'pokin_ranwal', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Sampel SKP', 'table' => 'sample_skp', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'SK PM', 'table' => 'sk_pm', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'SK PK', 'table' => 'sk_pk', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Absensi PM', 'table' => 'absen_pm', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Notulensi PM', 'table' => 'notulensi_pm', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Nodis Penyelenggaraan AKIP', 'table' => 'nodis_p_akip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Nodis Evaluasi AKIP', 'table' => 'nodis_eval_akip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Memo Data Kinerja', 'table' => 'memo_datakinerja', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Nodis Data Kinerja', 'table' => 'nodis_datakinerja', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Reward Punishment', 'table' => 'reward_punish', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Sampel Rekomendasi', 'table' => 'sampel_rekom', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'SS Perencanaan', 'table' => 'ss_perencanaan', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'SS Laporan Web', 'table' => 'ss_laporweb', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'SS Laporan App', 'table' => 'ss_laporapp', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'TAR LKJiP', 'table' => 'tar_lkjip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Memo LKJiP', 'table' => 'memo_lkjip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'TAR PM', 'table' => 'tar_pm', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'BA Praevaluasi', 'table' => 'ba_praeval', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'BA Pleno', 'table' => 'ba_pleno', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['label' => 'Bukti Dukung LKE Manual', 'table' => 'bukti_dukung', 'file_column' => 'link_bukti_dukung', 'period_column' => null, 'period_value' => null],
        ];
    }

    private function selectColumns(string $table, string $fileColumn): array
    {
        return collect([
            'id',
            'no',
            'id_satker',
            $fileColumn,
            'id_periode',
            'id_tahun',
            'id_perubahan',
            'id_triwulan',
            'triwulan',
            'TW',
            'id_tglupload',
            'tgl_pengisian',
            'created_at',
            'updated_at',
        ])
            ->unique()
            ->filter(fn ($column) => Schema::hasColumn($table, $column))
            ->values()
            ->all();
    }

    private function driveIndex(array $satkerIds): array
    {
        $paths = [];
        $names = [];
        $folders = [];
        $disk = Storage::disk('google');

        foreach ($satkerIds as $satkerId) {
            $folder = "uploads/repository/{$satkerId}";

            try {
                $files = $disk->files($folder);
                $folders[] = [
                    'folder' => $folder,
                    'status' => 'success',
                    'count' => count($files),
                ];

                foreach ($files as $path) {
                    $paths[] = (string) $path;
                    $names[] = $this->filename((string) $path);
                }
            } catch (Throwable $exception) {
                $folders[] = [
                    'folder' => $folder,
                    'status' => 'failed',
                    'count' => 0,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'paths' => collect($paths)->unique()->values()->all(),
            'names' => collect($names)->filter()->unique()->values()->all(),
            'folders' => $folders,
        ];
    }

    private function findDriveMatch(string $filename, array $driveIndex): ?string
    {
        $normalizedFilename = $this->filename($filename);

        foreach ($driveIndex['paths'] as $path) {
            if ($this->filename($path) === $normalizedFilename) {
                return $path;
            }
        }

        return null;
    }

    private function filename(string $value): string
    {
        $value = trim(rawurldecode(str_replace('\\', '/', $value)));

        return $value === '' ? '' : basename($value);
    }
}
