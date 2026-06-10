<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Services\SpipImportService;
use App\Services\SpipService;
use App\Services\IkssLegacyImportService;
use App\Services\IkssReportCatalogService;
use App\Services\IkssRegionSimulationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('spip:import-xlsx {path? : Lokasi file Web_APP_KK SPIP XLSX} {--tahun=2026}', function (?string $path = null) {
    $defaultPath = 'C:\\Users\\ACER1\\Downloads\\Web_APP_KK 3.1 SPIP 2026.xlsx';
    $path = $path ?: $defaultPath;

    $result = app(SpipImportService::class)->import($path, (int) $this->option('tahun'));

    $this->info('Import SPIP selesai.');
    foreach ($result as $key => $count) {
        $this->line("{$key}: {$count}");
    }
})->purpose('Import database SPIP dari workbook XLSX');

Artisan::command('spip:sync-user-id-satker {--tahun=2026}', function () {
    $tahun = (int) $this->option('tahun');
    $spip = app(SpipService::class);
    $changed = 0;
    $unmapped = [];

    $userIds = DB::table('spip_kertas_kerjas')
        ->where('tahun', $tahun)
        ->select('user_id')
        ->distinct()
        ->pluck('user_id');

    foreach ($userIds as $sourceUserId) {
        $targetUserId = $spip->appSatkerIdForSpipUserId($sourceUserId, $tahun);

        if (! $targetUserId) {
            $unmapped[] = $sourceUserId;
            continue;
        }

        if (strtolower((string) $sourceUserId) === strtolower((string) $targetUserId)) {
            continue;
        }

        $count = DB::table('spip_kertas_kerjas')
            ->where('tahun', $tahun)
            ->where('user_id', $sourceUserId)
            ->update(['user_id' => $targetUserId]);

        $changed += $count;
        $this->line("{$sourceUserId} -> {$targetUserId}: {$count} baris");
    }

    $this->info("Sinkronisasi selesai. Baris berubah: {$changed}");
    if ($unmapped) {
        $this->warn('Belum terpetakan: '.implode(', ', $unmapped));
    }
})->purpose('Sinkronisasi user_id kertas kerja SPIP menjadi id_satker sinori_login');

Artisan::command('ikss:import-legacy {--year=} {--quarter=} {--satker=*}', function () {
    $year = (int) ($this->option('year') ?: date('Y'));
    $quarter = (int) ($this->option('quarter') ?: max(1, min(4, (int) ceil(date('n') / 3))));
    $satkers = array_values(array_filter((array) $this->option('satker')));

    $result = app(IkssLegacyImportService::class)->import($year, $quarter, $satkers);

    $this->info("Sinkronisasi parameter IKSS {$year} TW {$quarter} selesai.");
    foreach ($result as $key => $count) {
        $this->line("{$key}: {$count}");
    }
})->purpose('Impor parameter dan nilai Pelaporan lama lalu bangun agregasi Kejati');

Artisan::command('ikss:seed-report-catalog', function () {
    $result = app(IkssReportCatalogService::class)->seedExamples();

    $this->info('Katalog lengkap parameter rinci SS/IKSS berhasil dibuat.');
    foreach ($result as $key => $count) {
        $this->line("{$key}: {$count}");
    }
})->purpose('Buat katalog lengkap parameter rinci untuk seluruh SS dan IKSS');

Artisan::command('ikss:simulate-region-full-entry {id_kejati=1} {--year=2026} {--quarter=1} {--replace}', function () {
    $idKejati = (string) $this->argument('id_kejati');
    $year = (int) $this->option('year');
    $quarter = (int) $this->option('quarter');
    $replace = (bool) $this->option('replace');

    if ($replace) {
        $this->warn('Mode replace aktif: isian manual yang sudah ada akan diganti dengan data simulasi.');
    }

    $this->info("Mengisi simulasi wilayah id_kejati {$idKejati}, Tahun {$year}, Triwulan {$quarter}...");
    $result = app(IkssRegionSimulationService::class)->simulate($idKejati, $year, $quarter, $replace);

    foreach ($result as $key => $value) {
        $this->line($key.': '.(is_array($value) ? json_encode($value) : $value));
    }
})->purpose('Lengkapi isian simulasi seluruh Kejari dan Cabjari lalu hitung ulang IKSS wilayah');
