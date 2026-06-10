<?php

namespace App\Services;

use App\Models\SpipKertasKerja;
use App\Models\SpipKluster;
use App\Models\SpipSubUnsur;
use App\Models\SpipUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SpipService
{
    private const STAGE_PM = 'Penilaian Mandiri';
    private const STAGE_PK = 'Penjaminan Kualitas';
    private const GRADES = ['A', 'B', 'C', 'D', 'E'];
    private const APP_USER_MAP = [
        'BADAN_PEMULIHAN_ASET' => 'bpa',
        'BADAN_PENDIDIKAN_DAN_PELATIHAN' => 'badiklat',
        'BADAN_PENDIDIKAN_DAN_PELATIHAN_KEJAKSAAN_RI' => 'badiklat',
        'JAM_BIDANG_PEMBINAAN' => 'jambin',
        'JAM_BIDANG_INTELIJEN' => 'jamintel',
        'JAM_BIDANG_PENGAWASAN' => 'jamwas',
        'JAM_BIDANG_PERDATA_DAN_TUN' => 'jamdatun',
        'JAM_BIDANG_PERDATA_DAN_TATA_USAHA_NEGARA' => 'jamdatun',
        'JAM_BIDANG_PIDANA_MILITER' => 'jampidmil',
        'JAM_BIDANG_TINDAK_PIDANA_KHUSUS' => 'jampidsus',
        'JAM_BIDANG_TINDAK_PIDANA_UMUM' => 'jampidum',
    ];
    private const SPIP_USER_TO_APP_SATKER_ID = [
        'bpa' => '691270',
        'badiklat' => '666405',
        'jamintel' => '419345',
        'jamwas' => '419346',
        'jamdatun' => '417023',
        'jampidmil' => '677111',
        'jampidsus' => '419344',
        'jampidum' => '418326',
        'jambin' => '005016',
    ];
    private const KEJATI_USER_MAP = [
        1 => 'kt_aceh',
        2 => 'kt_sumut',
        3 => 'kt_sumbar',
        4 => 'kt_riau',
        5 => 'kt_jambi',
        6 => 'kt_sumsel',
        7 => 'kt_bkl',
        8 => 'kt_lpg',
        9 => 'kt_jkt',
        10 => 'kt_jabar',
        11 => 'kt_jateng',
        12 => 'kt_diy',
        13 => 'kt_jatim',
        14 => 'kt_kalbar',
        15 => 'kt_kalteng',
        16 => 'kt_kalsel',
        17 => 'kt_kaltim',
        18 => 'kt_sulut',
        19 => 'kt_sulteng',
        20 => 'kt_sultra',
        21 => 'kt_sulsel',
        22 => 'kt_bali',
        23 => 'kt_ntb',
        24 => 'kt_ntt',
        25 => 'kt_maluku',
        26 => 'kt_papua',
        27 => 'kt_malut',
        28 => 'kt_banten',
        29 => 'kt_babel',
        30 => 'kt_gto',
        31 => 'kt_kpriau',
        32 => 'kt_sulbar',
        33 => 'kt_papbar',
        34 => 'kt_kaltara',
    ];

    public function authenticate(string $userId, string $password, string $tahapan, int $tahun): array|string
    {
        $user = $this->user($userId, $tahun);

        if (! $user) {
            return 'Gagal: User ID atau Password salah.';
        }

        if ($tahapan === self::STAGE_PK && ! in_array($user->status_pk, ['Aktif', 'Selesai'], true)) {
            return $user->status_pk === 'Menunggu Approve PK'
                ? 'Tahapan Penilaian Mandiri sedang diajukan. Menunggu persetujuan Admin untuk memulai PK.'
                : 'Masih Dalam Tahapan Penilaian Mandiri';
        }

        $hash = $tahapan === self::STAGE_PM ? $user->password_pm_hash : $user->password_pk_hash;
        if (! $hash || ! Hash::check($password, $hash)) {
            return 'Gagal: User ID atau Password salah.';
        }

        return [
            'status' => 'Berhasil',
            'nama' => $user->name,
            'userId' => $this->kertasUserIdForSpipUserId($user->user_id, $tahun),
            'spipUserId' => $user->user_id,
            'tahapan' => $tahapan,
            'statusPK' => $user->status_pk,
            'isReadOnly' => $this->isReadOnly($user->status_pk, $tahapan),
            'role' => $user->role,
            'linkDownload' => $user->link_download,
        ];
    }

    public function sessionForAppUser($appUser, int $tahun, ?string $tahapan = null): array|string
    {
        $user = $this->resolveUserForAppUser($appUser, $tahun);
        if (! $user) {
            return 'Akun aplikasi ini belum terhubung dengan satker SPIP. SPIP saat ini tersedia untuk JAM/Badan dan Kejati sesuai database SPIP.';
        }

        $tahapan = $tahapan ?: self::STAGE_PM;
        if (! in_array($tahapan, [self::STAGE_PM, self::STAGE_PK], true)) {
            return 'Tahapan SPIP tidak valid.';
        }

        if ($tahapan === self::STAGE_PK && ! in_array($user->status_pk, ['Aktif', 'Selesai'], true)) {
            return $user->status_pk === 'Menunggu Approve PK'
                ? 'Tahapan Penilaian Mandiri sedang diajukan. Menunggu persetujuan Admin untuk memulai PK.'
                : 'Masih Dalam Tahapan Penilaian Mandiri';
        }

        return [
            'status' => 'Berhasil',
            'nama' => $user->name,
            'userId' => $this->kertasUserIdForSpipUserId($user->user_id, $tahun),
            'spipUserId' => $user->user_id,
            'tahapan' => $tahapan,
            'statusPK' => $user->status_pk,
            'isReadOnly' => $this->isReadOnly($user->status_pk, $tahapan),
            'role' => $user->role,
            'linkDownload' => $user->link_download,
            'requiresLogin' => false,
            'availableTahapan' => in_array($user->status_pk, ['Aktif', 'Selesai'], true)
                ? [self::STAGE_PM, self::STAGE_PK]
                : [self::STAGE_PM],
        ];
    }

    public function resolveUserForAppUser($appUser, int $tahun): ?SpipUser
    {
        if (! $appUser) {
            return null;
        }

        $idSatker = (string) ($appUser->id_satker ?? '');
        $level = (int) ($appUser->id_sakip_level ?? 0);
        $idKejati = (int) ($appUser->id_kejati ?? 0);
        $satkerName = (string) ($appUser->satkernama ?? '');

        if ($level === 99 || in_array($idSatker, ['admin', '999999', '888881'], true)) {
            return null;
        }

        $normalizedName = $this->normalizeAppSatkerName($satkerName);
        if (isset(self::APP_USER_MAP[$normalizedName])) {
            return $this->user(self::APP_USER_MAP[$normalizedName], $tahun);
        }

        if ($level === 2 && isset(self::KEJATI_USER_MAP[$idKejati])) {
            return $this->user(self::KEJATI_USER_MAP[$idKejati], $tahun);
        }

        if (str_starts_with($normalizedName, 'KEJATI_') && isset(self::KEJATI_USER_MAP[$idKejati])) {
            return $this->user(self::KEJATI_USER_MAP[$idKejati], $tahun);
        }

        return null;
    }

    public function appSatkerIdForSpipUserId(?string $userId, int $tahun): ?string
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            return null;
        }

        if (DB::table('sinori_login')->where('id_satker', $userId)->exists()) {
            return $userId;
        }

        $lowerUserId = strtolower($userId);
        if (isset(self::SPIP_USER_TO_APP_SATKER_ID[$lowerUserId])) {
            return self::SPIP_USER_TO_APP_SATKER_ID[$lowerUserId];
        }

        $idKejati = array_search($lowerUserId, self::KEJATI_USER_MAP, true);
        if ($idKejati !== false) {
            return DB::table('sinori_login')
                ->where('id_sakip_level', 2)
                ->where('id_kejati', $idKejati)
                ->value('id_satker');
        }

        return null;
    }

    public function kertasUserIdForSpipUserId(?string $userId, int $tahun): string
    {
        $userId = trim((string) $userId);

        return $this->appSatkerIdForSpipUserId($userId, $tahun) ?: strtolower($userId);
    }

    public function subUnsurData(string $userId, int $tahun): array
    {
        $gradeMap = $this->gradeMap($userId, $tahun);

        return SpipSubUnsur::query()
            ->where('tahun', $tahun)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'kode' => $row->kode_sub_unsur,
                'parameter' => $row->uraian_parameter,
                'spip' => $row->spip ?: '-',
                'mri' => $row->mri ?: '-',
                'iepk' => $row->iepk ?: '-',
                'grade_pm' => $gradeMap[$row->kode_sub_unsur]['pm'] ?? '-',
                'grade_pk' => $gradeMap[$row->kode_sub_unsur]['pk'] ?? '-',
            ])
            ->values()
            ->all();
    }

    public function detailKriteria(string $userId, string $kodeSub, int $tahun): array
    {
        $rows = SpipKertasKerja::query()
            ->where('tahun', $tahun)
            ->where('user_id', strtolower($userId))
            ->where('kode_sub_unsur', $kodeSub)
            ->orderByRaw("FIELD(grade, 'A', 'B', 'C', 'D', 'E')")
            ->get();

        $aoiRow = $rows->first(fn ($row) => $row->uraian_aoi || $row->uraian_penyebab || $row->kluster_aoi || $row->kluster_penyebab);
        $selectedGradePm = $rows->first(fn ($row) => $this->validGrade($row->grade_pm))?->grade_pm;
        $selectedGradePk = $rows->first(fn ($row) => $this->validGrade($row->grade_pk))?->grade_pk;

        return [
            'selected_grade_pm' => $selectedGradePm ? strtoupper($selectedGradePm) : '-',
            'selected_grade_pk' => $selectedGradePk ? strtoupper($selectedGradePk) : '-',
            'kriteria' => $rows->map(fn ($row) => [
                'grade' => $row->grade,
                'kriteria' => $row->kriteria,
                'penjelasan' => $row->penjelasan,
                'uraian' => $row->uraian_hasil_pengujian,
                'grade_pm' => $row->grade_pm ?: '-',
                'grade_pk' => $row->grade_pk ?: '-',
            ])->values()->all(),
            'aoi' => [
                'kAoI' => $aoiRow?->kluster_aoi ?? '',
                'uAoI' => $aoiRow?->uraian_aoi ?? '',
                'kSebab' => $aoiRow?->kluster_penyebab ?? '',
                'uSebab' => $aoiRow?->uraian_penyebab ?? '',
            ],
        ];
    }

    public function klusterOptions(int $tahun): array
    {
        $rows = SpipKluster::where('tahun', $tahun)->get(['kluster_aoi', 'kluster_penyebab']);

        return [
            'aoi' => $rows->pluck('kluster_aoi')->filter()->unique()->values()->all(),
            'penyebab' => $rows->pluck('kluster_penyebab')->filter()->unique()->values()->all(),
        ];
    }

    public function saveKertasKerja(array $payload, int $tahun): string
    {
        $user = $this->user((string) ($payload['spipUserId'] ?? $payload['userId']), $tahun);
        if (! $user) {
            throw new RuntimeException('User SPIP tidak ditemukan.');
        }

        $kertasUserId = (string) $payload['userId'];
        $tahapan = (string) $payload['tahapan'];
        if ($this->isReadOnly($user->status_pk, $tahapan)) {
            throw new RuntimeException('Data pada tahapan ini sedang terkunci.');
        }

        $kodeSub = (string) $payload['kodeSub'];
        $gradeTerpilih = strtoupper((string) $payload['gradeTerpilih']);
        if (! in_array($gradeTerpilih, self::GRADES, true)) {
            throw new RuntimeException('Grade tidak valid.');
        }

        $uraianMap = $payload['uraianMap'] ?? [];
        $aoiData = $payload['aoiData'] ?? [];
        $gradeColumn = $tahapan === self::STAGE_PM ? 'grade_pm' : 'grade_pk';

        DB::transaction(function () use ($kertasUserId, $tahun, $kodeSub, $gradeTerpilih, $gradeColumn, $uraianMap, $aoiData, $tahapan) {
            $rows = SpipKertasKerja::query()
                ->where('tahun', $tahun)
                ->where('user_id', strtolower($kertasUserId))
                ->where('kode_sub_unsur', $kodeSub)
                ->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException('Kertas kerja SPIP tidak ditemukan.');
            }

            foreach ($rows as $row) {
                $row->{$gradeColumn} = $gradeTerpilih;

                if (array_key_exists($row->grade, $uraianMap)) {
                    $row->uraian_hasil_pengujian = $uraianMap[$row->grade];
                }

                if ($aoiData) {
                    if ($tahapan === self::STAGE_PM) {
                        $row->kluster_aoi = $aoiData['kAoI'] ?? '-';
                        $row->uraian_aoi = $aoiData['uAoI'] ?? null;
                        $row->kluster_penyebab = $aoiData['kSebab'] ?? '-';
                        $row->uraian_penyebab = $aoiData['uSebab'] ?? null;
                    } else {
                        $row->uraian_aoi = $aoiData['uAoI'] ?? null;
                    }
                }

                $row->save();
            }
        });

        return 'Data Berhasil Disimpan!';
    }

    public function updateStatusPk(string $userId, string $statusBaru, int $tahun): string
    {
        $user = $this->user($userId, $tahun);
        if (! $user) {
            return 'Gagal: User ID tidak ditemukan.';
        }

        $kertasUserId = $this->kertasUserIdForSpipUserId($user->user_id, $tahun);

        if ($statusBaru === 'Menunggu Approve PK' && ! $this->allSubUnsursFilled($kertasUserId, $tahun, 'grade_pm')) {
            return 'Gagal: Semua Grade PM harus diisi sebelum mengajukan PK!';
        }

        if ($statusBaru === 'Selesai' && ! $this->allSubUnsursFilled($kertasUserId, $tahun, 'grade_pk')) {
            return 'Gagal: Semua Grade PK harus terisi!';
        }

        $user->status_pk = $statusBaru;
        $user->save();

        return match ($statusBaru) {
            'Menunggu Approve PK' => 'Berhasil: Permohonan PK berhasil dikirim. Menunggu persetujuan Admin.',
            'Aktif' => 'Berhasil: Tahapan PK telah disetujui & diaktifkan.',
            default => 'Berhasil: Status diubah menjadi '.$statusBaru,
        };
    }

    public function changePassword(string $userId, string $tahapan, string $password, int $tahun): string
    {
        $user = $this->user($userId, $tahun);
        if (! $user) {
            return 'Gagal: User tidak ditemukan.';
        }

        if ($tahapan === self::STAGE_PM) {
            $user->password_pm_hash = Hash::make($password);
        } else {
            $user->password_pk_hash = Hash::make($password);
        }

        $user->save();

        return 'Password Berhasil Diubah!';
    }

    public function downloadLink(string $userId, int $tahun): array
    {
        $user = $this->user($userId, $tahun);
        if (! $user) {
            return ['status' => 'error', 'pesan' => 'User ID tidak ditemukan.'];
        }

        if (! $user->link_download || ! preg_match('/^https?:\/\//', $user->link_download)) {
            return ['status' => 'error', 'pesan' => 'Tautan unduhan masih kosong atau tidak valid.'];
        }

        return ['status' => 'sukses', 'url' => $user->link_download];
    }

    public function adminDashboard(int $tahun): array
    {
        $totalParameter = max(SpipSubUnsur::where('tahun', $tahun)->count(), 1);
        $users = SpipUser::query()
            ->where('tahun', $tahun)
            ->whereRaw('LOWER(role) <> ?', ['admin'])
            ->orderBy('name')
            ->get();

        return $users->values()->map(function ($user, $index) use ($tahun, $totalParameter) {
            $summary = $this->progressSummary($this->kertasUserIdForSpipUserId($user->user_id, $tahun), $tahun, $totalParameter);
            $progressPM = $summary['pm_percent'];
            $progressPK = $summary['pk_percent'];

            if ($user->status_pk === 'Menunggu Approve PK') {
                $progressPM = 'Menunggu Persetujuan';
            } elseif ($user->status_pk === 'Aktif') {
                $progressPM = 'PK Sedang Berlangsung';
            } elseif ($user->status_pk === 'Selesai') {
                $progressPM = 'PM & PK Telah Dilaksanakan';
                $progressPK = 'PM & PK Telah Dilaksanakan';
            }

            return [
                'no' => $index + 1,
                'nama_satker' => $user->name,
                'progress_pm' => $progressPM,
                'progress_pk' => $progressPK,
                'user_id' => $user->user_id,
                'id_satker' => $this->appSatkerIdForSpipUserId($user->user_id, $tahun),
                'missing_links' => $summary['missing_links'],
                'status_pk' => $user->status_pk,
                'link_download' => $user->link_download,
            ];
        })->all();
    }

    public function adminIntip(string $userId, int $tahun): array
    {
        $kertasUserId = $this->kertasUserIdForSpipUserId($userId, $tahun);

        return DB::table('spip_kertas_kerjas as kk')
            ->leftJoin('spip_sub_unsurs as su', function ($join) {
                $join->on('su.tahun', '=', 'kk.tahun')
                    ->on('su.kode_sub_unsur', '=', 'kk.kode_sub_unsur');
            })
            ->where('kk.tahun', $tahun)
            ->where('kk.user_id', strtolower($kertasUserId))
            ->orderBy('kk.kode_sub_unsur')
            ->orderByRaw("FIELD(kk.grade, 'A', 'B', 'C', 'D', 'E')")
            ->select([
                'kk.kode_sub_unsur',
                'kk.grade',
                'kk.kriteria',
                'kk.uraian_hasil_pengujian',
                'kk.grade_pm',
                'kk.grade_pk',
                'kk.uraian_aoi',
                'kk.uraian_penyebab',
                'su.uraian_parameter',
            ])
            ->get()
            ->map(fn ($row) => [
                'kodeSubUnsur' => $row->kode_sub_unsur,
                'hurufGrade' => $row->grade,
                'namaParameter' => $row->uraian_parameter ?: $row->kriteria,
                'uraianF' => $row->uraian_hasil_pengujian ?: '',
                'gradePM' => $row->grade_pm ?: '-',
                'gradePK' => $row->grade_pk ?: '-',
                'aoiJ' => $row->uraian_aoi ?: '-',
                'sebabL' => $row->uraian_penyebab ?: '-',
            ])
            ->all();
    }

    public function approvePm(string $userId, int $tahun): array
    {
        $user = $this->user($userId, $tahun);
        if (! $user) {
            return ['status' => 'error', 'pesan' => 'User ID Satker tidak ditemukan.'];
        }

        $user->status_pk = 'Aktif';
        $user->save();

        return [
            'status' => 'sukses',
            'pesan' => 'Persetujuan Berhasil! Status Penilaian Mandiri disetujui & Tahapan PK untuk '.$user->name.' kini AKTIF.',
        ];
    }

    public function resetStatus(string $userId, int $tahun): array
    {
        $user = $this->user($userId, $tahun);
        if (! $user) {
            return ['status' => 'error', 'pesan' => 'User ID tidak ditemukan.'];
        }

        $user->status_pk = 'Tidak Aktif';
        $user->save();

        return ['status' => 'sukses', 'pesan' => 'Berhasil membuka kunci pengisian untuk '.$user->name];
    }

    public function resetPassword(string $userId, string $tahapan, int $tahun): array
    {
        $user = $this->user($userId, $tahun);
        if (! $user) {
            return ['status' => 'error', 'pesan' => 'User ID tidak ditemukan.'];
        }

        if ($tahapan === self::STAGE_PM) {
            $user->password_pm_hash = Hash::make('pm2026');
            $message = 'Password PM berhasil direset ke default: pm2026';
        } else {
            $user->password_pk_hash = Hash::make('pk12345');
            $message = 'Password PK berhasil direset ke default: pk12345';
        }

        $user->save();

        return ['status' => 'sukses', 'pesan' => $message];
    }

    public function user(?string $userId, int $tahun): ?SpipUser
    {
        return SpipUser::query()
            ->where('tahun', $tahun)
            ->whereRaw('LOWER(user_id) = ?', [strtolower((string) $userId)])
            ->first();
    }

    private function isReadOnly(string $statusPk, string $tahapan): bool
    {
        return ($tahapan === self::STAGE_PM && in_array($statusPk, ['Menunggu Approve PK', 'Aktif', 'Selesai'], true))
            || ($tahapan === self::STAGE_PK && $statusPk === 'Selesai');
    }

    private function gradeMap(string $userId, int $tahun): array
    {
        return SpipKertasKerja::query()
            ->where('tahun', $tahun)
            ->where('user_id', strtolower($userId))
            ->orderByRaw("FIELD(grade, 'A', 'B', 'C', 'D', 'E')")
            ->get(['kode_sub_unsur', 'grade_pm', 'grade_pk'])
            ->groupBy('kode_sub_unsur')
            ->map(fn (Collection $rows) => [
                'pm' => strtoupper((string) ($rows->first(fn ($row) => $this->validGrade($row->grade_pm))?->grade_pm ?: '-')),
                'pk' => strtoupper((string) ($rows->first(fn ($row) => $this->validGrade($row->grade_pk))?->grade_pk ?: '-')),
            ])
            ->all();
    }

    private function allSubUnsursFilled(string $userId, int $tahun, string $column): bool
    {
        $total = SpipSubUnsur::where('tahun', $tahun)->count();
        if ($total === 0) {
            return false;
        }

        $filled = SpipKertasKerja::query()
            ->where('tahun', $tahun)
            ->where('user_id', strtolower($userId))
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->count('kode_sub_unsur');

        return $filled >= $total;
    }

    private function progressSummary(string $userId, int $tahun, int $totalParameter): array
    {
        $rows = SpipKertasKerja::query()
            ->where('tahun', $tahun)
            ->where('user_id', strtolower($userId))
            ->get(['kode_sub_unsur', 'grade', 'grade_pm', 'grade_pk', 'uraian_hasil_pengujian']);

        $filledPm = $rows
            ->filter(fn ($row) => $row->grade_pm)
            ->pluck('kode_sub_unsur')
            ->unique()
            ->count();
        $filledPk = $rows
            ->filter(fn ($row) => $row->grade_pk)
            ->pluck('kode_sub_unsur')
            ->unique()
            ->count();

        $missingLinks = $rows
            ->filter(function ($row) {
                $uraian = trim((string) $row->uraian_hasil_pengujian);

                return $uraian !== '' && $uraian !== '-' && ! preg_match('/https?:\/\//i', $uraian);
            })
            ->map(fn ($row) => $row->kode_sub_unsur.' [Grade '.$row->grade.']')
            ->unique()
            ->values()
            ->all();

        return [
            'pm_percent' => number_format(($filledPm / $totalParameter) * 100, 2).'%',
            'pk_percent' => number_format(($filledPk / $totalParameter) * 100, 2).'%',
            'missing_links' => $missingLinks,
        ];
    }

    private function normalizeAppSatkerName(string $name): string
    {
        $name = strtoupper($name);
        $name = preg_replace('/[^A-Z0-9]+/', '_', $name) ?: '';
        $name = preg_replace('/_+/', '_', $name) ?: '';

        return trim($name, '_');
    }

    private function validGrade($grade): bool
    {
        return in_array(strtoupper(trim((string) $grade)), self::GRADES, true);
    }
}
