<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BusinessIntelligenceService
{
    public function analyze(string $selectedYear): array
    {
        $snapshot = $this->snapshot($selectedYear);
        $process = new Process([
            config('bi.python_binary'),
            base_path('bi/akip_bi.py'),
        ]);

        $process->setTimeout(config('bi.timeout'));
        $process->setInput(json_encode($snapshot, JSON_THROW_ON_ERROR));

        try {
            $process->mustRun();

            return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Mesin BI Python tidak dapat dijalankan: '.trim($process->getErrorOutput() ?: $exception->getMessage()),
                previous: $exception
            );
        }
    }

    private function snapshot(string $selectedYear): array
    {
        $year = is_numeric($selectedYear) ? (int) $selectedYear : (int) date('Y');
        $years = range($year - 3, $year);
        $satkers = app(SatkerAccessService::class)
            ->baseSatkerQuery()
            ->select('id_satker', 'satkernama', 'id_kejati')
            ->orderBy('id_satker')
            ->get();
        $satkerIds = $satkers->pluck('id_satker')->map(fn ($id) => (string) $id)->all();
        $kejatiNames = $satkers
            ->filter(fn ($satker) => (string) $satker->id_satker === (string) $satker->id_kejati)
            ->mapWithKeys(fn ($satker) => [(string) $satker->id_kejati => str_replace('_', ' ', (string) $satker->satkernama)])
            ->all();

        $sources = collect($this->documentSources())
            ->filter(fn ($source) => $this->sourceIsReady($source))
            ->values();
        $coverage = [];
        $documentCoverage = [];

        foreach ($years as $analysisYear) {
            foreach ($sources as $source) {
                $uploadedIds = $this->uploadedSatkerIds($source, (string) $analysisYear, $satkerIds);
                $coverage[$analysisYear][$source['key']] = array_fill_keys($uploadedIds, true);
                $documentCoverage[$analysisYear][$source['key']] = count($uploadedIds);
            }
        }

        $documentCount = max($sources->count(), 1);
        $satkerRows = $satkers->map(function ($satker) use ($years, $year, $sources, $coverage, $documentCount, $kejatiNames) {
            $id = (string) $satker->id_satker;
            $history = [];
            $missingDocuments = [];

            foreach ($years as $analysisYear) {
                $uploaded = $sources->filter(fn ($source) => isset($coverage[$analysisYear][$source['key']][$id]))->count();
                $history[(string) $analysisYear] = round(($uploaded / $documentCount) * 100, 1);
            }

            foreach ($sources as $source) {
                if (! isset($coverage[$year][$source['key']][$id])) {
                    $missingDocuments[] = $source['label'];
                }
            }

            return [
                'id_satker' => $id,
                'satkernama' => str_replace('_', ' ', (string) $satker->satkernama),
                'id_kejati' => (string) $satker->id_kejati,
                'kejati_name' => $kejatiNames[(string) $satker->id_kejati] ?? (string) $satker->id_kejati,
                'history' => $history,
                'missing_documents' => $missingDocuments,
            ];
        })->values()->all();

        $documents = $sources->map(function ($source) use ($documentCoverage, $year, $years, $satkers) {
            $previousYear = $years[count($years) - 2];
            $total = max($satkers->count(), 1);
            $current = $documentCoverage[$year][$source['key']] ?? 0;
            $previous = $documentCoverage[$previousYear][$source['key']] ?? 0;

            return [
                'key' => $source['key'],
                'label' => $source['label'],
                'category' => $source['category'],
                'coverage' => round(($current / $total) * 100, 1),
                'change' => round((($current - $previous) / $total) * 100, 1),
                'missing_satkers' => max($satkers->count() - $current, 0),
            ];
        })->values()->all();

        return [
            'selected_year' => (string) $year,
            'years' => array_map('strval', $years),
            'document_count' => $sources->count(),
            'satkers' => $satkerRows,
            'documents' => $documents,
        ];
    }

    private function sourceIsReady(array $source): bool
    {
        return Schema::hasTable($source['table'])
            && Schema::hasColumn($source['table'], 'id_satker')
            && Schema::hasColumn($source['table'], $source['file_column'])
            && Schema::hasColumn($source['table'], $source['period_column']);
    }

    private function uploadedSatkerIds(array $source, string $year, array $satkerIds): array
    {
        if ($satkerIds === []) {
            return [];
        }

        $period = $source['period_type'] === 'renstra'
            ? ((int) $year <= 2024 ? 'P1' : 'P2')
            : $year;

        return DB::table($source['table'])
            ->whereIn('id_satker', $satkerIds)
            ->where($source['period_column'], $period)
            ->whereNotNull($source['file_column'])
            ->where($source['file_column'], '<>', '')
            ->distinct()
            ->pluck('id_satker')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function documentSources(): array
    {
        return [
            ['key' => 'kep', 'label' => 'Keputusan Tim SAKIP', 'category' => 'Fondasi', 'table' => 'sinori_sakip_keputusan', 'file_column' => 'id_filesurat', 'period_column' => 'id_tahun', 'period_type' => 'year'],
            ['key' => 'renstra', 'label' => 'Renstra', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_renstra', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'renstra'],
            ['key' => 'iku', 'label' => 'IKU', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_iku', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'renja', 'label' => 'Renja', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_renja', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'rkakl', 'label' => 'RKAKL', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_rkakl', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'dipa', 'label' => 'DIPA', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_dipa', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'renaksi', 'label' => 'Rencana Aksi', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_renaksi', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'pk', 'label' => 'Perjanjian Kinerja', 'category' => 'Perencanaan', 'table' => 'pk', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'lkjip', 'label' => 'LKJiP', 'category' => 'Pelaporan', 'table' => 'sinori_sakip_lakip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'monev_renaksi', 'label' => 'Monev Renaksi', 'category' => 'Pelaporan', 'table' => 'sinori_sakip_renaksieval', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'lhe', 'label' => 'LHE AKIP', 'category' => 'Evaluasi', 'table' => 'lhe', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
            ['key' => 'tl_lhe', 'label' => 'TL LHE AKIP', 'category' => 'Evaluasi', 'table' => 'tl_lhe_akip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_type' => 'year'],
        ];
    }
}
