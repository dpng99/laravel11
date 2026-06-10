<?php

namespace App\Services;

use App\Models\SpipKertasKerja;
use App\Models\SpipKluster;
use App\Models\SpipSubUnsur;
use App\Models\SpipUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Symfony\Component\Process\Process;

class SpipImportService
{
    public function import(string $xlsxPath, int $tahun = 2026): array
    {
        if (! is_file($xlsxPath)) {
            throw new RuntimeException("File XLSX tidak ditemukan: {$xlsxPath}");
        }

        $process = new Process([
            'node',
            base_path('scripts/spip_xlsx_to_json.cjs'),
            $xlsxPath,
        ], base_path());
        $process->setTimeout(120);
        $process->mustRun();

        $payload = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $spip = app(SpipService::class);

        DB::transaction(function () use ($payload, $tahun, $spip) {
            foreach ($payload['users'] ?? [] as $row) {
                SpipUser::updateOrCreate(
                    ['tahun' => $tahun, 'user_id' => strtolower((string) $row['user_id'])],
                    [
                        'name' => $row['name'] ?? $row['user_id'],
                        'role' => $row['role'] ?? 'User',
                        'allowed_satker' => $row['allowed_satker'] ?? null,
                        'password_pm_hash' => $row['password_pm'] ? Hash::make((string) $row['password_pm']) : null,
                        'password_pk_hash' => $row['password_pk'] ? Hash::make((string) $row['password_pk']) : null,
                        'status_pk' => $row['status_pk'] ?? 'Tidak Aktif',
                        'link_download' => $row['link_download'] ?? null,
                        'spreadsheet_url' => $row['spreadsheet_url'] ?? null,
                        'gid' => $row['gid'] ?? null,
                        'edit_url' => $row['edit_url'] ?? null,
                    ]
                );
            }

            foreach ($payload['sub_unsurs'] ?? [] as $row) {
                SpipSubUnsur::updateOrCreate(
                    ['tahun' => $tahun, 'kode_sub_unsur' => (string) $row['kode_sub_unsur']],
                    [
                        'kode' => $row['kode'] ?? null,
                        'sub_unsur' => $row['sub_unsur'] ?? null,
                        'nomor' => is_numeric($row['nomor'] ?? null) ? (int) $row['nomor'] : null,
                        'uraian_parameter' => $row['uraian_parameter'] ?? null,
                        'spip' => $row['spip'] ?? null,
                        'mri' => $row['mri'] ?? null,
                        'iepk' => $row['iepk'] ?? null,
                    ]
                );
            }

            foreach ($payload['kertas_kerja'] ?? [] as $row) {
                $sourceUserId = (string) $row['user_id'];
                $kertasUserId = $spip->kertasUserIdForSpipUserId($sourceUserId, $tahun);

                SpipKertasKerja::updateOrCreate(
                    [
                        'tahun' => $tahun,
                        'user_id' => strtolower($kertasUserId),
                        'kode_sub_unsur' => (string) $row['kode_sub_unsur'],
                        'grade' => strtoupper((string) $row['grade']),
                    ],
                    [
                        'kriteria' => $row['kriteria'] ?? null,
                        'penjelasan' => $row['penjelasan'] ?? null,
                        'cara_pengujian' => $row['cara_pengujian'] ?? null,
                        'uraian_hasil_pengujian' => $row['uraian_hasil_pengujian'] ?? null,
                        'grade_pm' => $this->nullableGrade($row['grade_pm'] ?? null),
                        'grade_pk' => $this->nullableGrade($row['grade_pk'] ?? null),
                        'kluster_aoi' => $row['kluster_aoi'] ?? null,
                        'uraian_aoi' => $row['uraian_aoi'] ?? null,
                        'kluster_penyebab' => $row['kluster_penyebab'] ?? null,
                        'uraian_penyebab' => $row['uraian_penyebab'] ?? null,
                    ]
                );
            }

            SpipKluster::where('tahun', $tahun)->delete();
            foreach ($payload['klusters'] ?? [] as $row) {
                SpipKluster::create([
                    'tahun' => $tahun,
                    'kluster_aoi' => $row['kluster_aoi'] ?? null,
                    'kluster_penyebab' => $row['kluster_penyebab'] ?? null,
                ]);
            }
        });

        return [
            'users' => count($payload['users'] ?? []),
            'sub_unsurs' => count($payload['sub_unsurs'] ?? []),
            'kertas_kerja' => count($payload['kertas_kerja'] ?? []),
            'klusters' => count($payload['klusters'] ?? []),
        ];
    }

    private function nullableGrade($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' || $value === '-' ? null : strtoupper($value);
    }
}
