<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use App\Services\SatkerAccessService;

class DashboardAnalytic extends Controller
{
    public function index(Request $request)
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        $tahun = (string) session('tahun_terpilih', date('Y'));
        session(['tahun_terpilih' => $tahun]);

        $satkers = app(SatkerAccessService::class)
            ->baseSatkerQuery()
            ->select('id_satker', 'satkernama', 'id_kejati', 'id_kejari', 'id_sakip_level')
            ->orderBy('id_kejati')
            ->orderBy('id_kejari')
            ->orderBy('id_satker')
            ->get()
            ->map(function ($satker) {
                return [
                    'id_satker' => (string) $satker->id_satker,
                    'satkernama' => str_replace('_', ' ', (string) $satker->satkernama),
                    'id_kejati' => (string) $satker->id_kejati,
                    'id_kejari' => (string) $satker->id_kejari,
                    'id_sakip_level' => (int) $satker->id_sakip_level,
                ];
            });

        $satkerIds = $satkers->pluck('id_satker')->values()->all();
        $kejatiNames = $this->kejatiNames($satkers);
        $sources = $this->documentSources($tahun);

        $coverage = collect($satkerIds)->mapWithKeys(fn ($id) => [$id => collect()]);
        $documentStats = collect($sources)->map(function ($source) use ($satkerIds, $coverage) {
            $result = $this->documentCoverage($source, $satkerIds);

            foreach ($result['uploaded_satker_ids'] as $satkerId) {
                if ($coverage->has($satkerId)) {
                    $coverage[$satkerId]->push($source['label']);
                }
            }

            $totalSatkers = count($satkerIds);
            $uploadedSatkers = count($result['uploaded_satker_ids']);

            return [
                'key' => $source['key'],
                'label' => $source['label'],
                'category' => $source['category'],
                'uploaded_satkers' => $uploadedSatkers,
                'missing_satkers' => max($totalSatkers - $uploadedSatkers, 0),
                'percentage' => $this->percentage($uploadedSatkers, $totalSatkers),
                'total_uploads' => $result['total_uploads'],
                'status' => $result['status'],
                'note' => $result['note'],
            ];
        })->values();

        $documentCount = $documentStats->where('status', 'ready')->count();
        $satkerCompletion = $satkers->map(function ($satker) use ($coverage, $documentStats, $documentCount, $kejatiNames) {
            $uploadedDocuments = $coverage->get($satker['id_satker'], collect())->unique()->values();
            $missingDocuments = $documentStats
                ->where('status', 'ready')
                ->pluck('label')
                ->diff($uploadedDocuments)
                ->values();

            return [
                ...$satker,
                'kejati_name' => $kejatiNames[$satker['id_kejati']] ?? $satker['id_kejati'],
                'uploaded_documents' => $uploadedDocuments->count(),
                'missing_documents_count' => $missingDocuments->count(),
                'completion_percentage' => $this->percentage($uploadedDocuments->count(), $documentCount),
                'missing_documents' => $missingDocuments->all(),
            ];
        });

        $wilayahStats = $satkerCompletion
            ->groupBy('id_kejati')
            ->map(function ($rows, $idKejati) use ($kejatiNames, $documentCount) {
                $totalSatkers = $rows->count();
                $uploadedPoints = $rows->sum('uploaded_documents');
                $possiblePoints = $totalSatkers * max($documentCount, 1);
                $completeSatkers = $rows->where('missing_documents_count', 0)->count();
                $criticalSatkers = $rows->filter(fn ($row) => $row['completion_percentage'] < 50)->count();

                return [
                    'id_kejati' => (string) $idKejati,
                    'kejati_name' => $kejatiNames[(string) $idKejati] ?? (string) $idKejati,
                    'total_satkers' => $totalSatkers,
                    'complete_satkers' => $completeSatkers,
                    'critical_satkers' => $criticalSatkers,
                    'completion_percentage' => $this->percentage($uploadedPoints, $possiblePoints),
                    'uploaded_points' => $uploadedPoints,
                    'possible_points' => $possiblePoints,
                ];
            })
            ->values()
            ->sortByDesc('completion_percentage')
            ->values();

        $prioritySatkers = $satkerCompletion
            ->sortBy([
                ['completion_percentage', 'asc'],
                ['missing_documents_count', 'desc'],
                ['id_kejati', 'asc'],
                ['id_satker', 'asc'],
            ])
            ->take(15)
            ->values();

        $readyDocumentStats = $documentStats->where('status', 'ready');
        $totalSatkers = $satkers->count();
        $uploadedPoints = $readyDocumentStats->sum('uploaded_satkers');
        $possiblePoints = $totalSatkers * max($documentCount, 1);
        $completeSatkers = $satkerCompletion->where('missing_documents_count', 0)->count();
        $criticalSatkers = $satkerCompletion->filter(fn ($row) => $row['completion_percentage'] < 50)->count();

        return Inertia::render('Admin/DashboardAnalytic', [
            'tahun' => $tahun,
            'summary' => [
                'total_satkers' => $totalSatkers,
                'document_types' => $documentCount,
                'average_completion' => $this->percentage($uploadedPoints, $possiblePoints),
                'complete_satkers' => $completeSatkers,
                'critical_satkers' => $criticalSatkers,
                'incomplete_satkers' => max($totalSatkers - $completeSatkers, 0),
            ],
            'documentStats' => $documentStats,
            'categoryStats' => $this->categoryStats($documentStats, $totalSatkers),
            'wilayahStats' => $wilayahStats,
            'prioritySatkers' => $prioritySatkers,
        ]);
    }

    private function documentSources(string $tahun): array
    {
        $renstraPeriod = $tahun === '2024' ? 'P1' : 'P2';

        return [
            ['key' => 'kep', 'label' => 'Keputusan Tim SAKIP', 'category' => 'Fondasi', 'table' => 'sinori_sakip_keputusan', 'file_column' => 'id_filesurat', 'period_column' => 'id_tahun', 'period_value' => $tahun],
            ['key' => 'renstra', 'label' => 'Renstra', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_renstra', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $renstraPeriod],
            ['key' => 'iku', 'label' => 'IKU', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_iku', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'renja', 'label' => 'Renja', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_renja', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'rkakl', 'label' => 'RKAKL', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_rkakl', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'dipa', 'label' => 'DIPA', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_dipa', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'renaksi', 'label' => 'Rencana Aksi', 'category' => 'Perencanaan', 'table' => 'sinori_sakip_renaksi', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'pk', 'label' => 'Perjanjian Kinerja', 'category' => 'Perencanaan', 'table' => 'pk', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'lkjip', 'label' => 'LKJiP', 'category' => 'Pelaporan', 'table' => 'sinori_sakip_lakip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'monev_renaksi', 'label' => 'Monev Renaksi', 'category' => 'Pelaporan', 'table' => 'sinori_sakip_renaksieval', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'lhe', 'label' => 'LHE AKIP', 'category' => 'Evaluasi', 'table' => 'lhe', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
            ['key' => 'tl_lhe', 'label' => 'TL LHE AKIP', 'category' => 'Evaluasi', 'table' => 'tl_lhe_akip', 'file_column' => 'id_filename', 'period_column' => 'id_periode', 'period_value' => $tahun],
        ];
    }

    private function documentCoverage(array $source, array $satkerIds): array
    {
        if (empty($satkerIds)) {
            return ['uploaded_satker_ids' => [], 'total_uploads' => 0, 'status' => 'ready', 'note' => null];
        }

        if (! Schema::hasTable($source['table'])) {
            return ['uploaded_satker_ids' => [], 'total_uploads' => 0, 'status' => 'skipped', 'note' => 'Tabel tidak tersedia'];
        }

        if (! Schema::hasColumn($source['table'], $source['file_column'])) {
            return ['uploaded_satker_ids' => [], 'total_uploads' => 0, 'status' => 'skipped', 'note' => 'Kolom file tidak tersedia'];
        }

        if (! Schema::hasColumn($source['table'], 'id_satker')) {
            return ['uploaded_satker_ids' => [], 'total_uploads' => 0, 'status' => 'skipped', 'note' => 'Kolom satker tidak tersedia'];
        }

        $query = DB::table($source['table'])
            ->whereIn('id_satker', $satkerIds)
            ->whereNotNull($source['file_column'])
            ->where($source['file_column'], '<>', '');

        if (
            ! empty($source['period_column'])
            && Schema::hasColumn($source['table'], $source['period_column'])
        ) {
            $query->where($source['period_column'], $source['period_value']);
        }

        $rows = $query->select('id_satker')->get();

        return [
            'uploaded_satker_ids' => $rows->pluck('id_satker')->map(fn ($id) => (string) $id)->unique()->values()->all(),
            'total_uploads' => $rows->count(),
            'status' => 'ready',
            'note' => null,
        ];
    }

    private function categoryStats($documentStats, int $totalSatkers)
    {
        return $documentStats
            ->where('status', 'ready')
            ->groupBy('category')
            ->map(function ($rows, $category) use ($totalSatkers) {
                $possible = $totalSatkers * max($rows->count(), 1);
                $uploaded = $rows->sum('uploaded_satkers');

                return [
                    'category' => $category,
                    'document_types' => $rows->count(),
                    'percentage' => $this->percentage($uploaded, $possible),
                    'uploaded_points' => $uploaded,
                    'possible_points' => $possible,
                ];
            })
            ->values();
    }

    private function kejatiNames($satkers): array
    {
        return $satkers
            ->filter(fn ($satker) => $satker['id_satker'] === $satker['id_kejati'])
            ->mapWithKeys(fn ($satker) => [$satker['id_kejati'] => $satker['satkernama']])
            ->all();
    }

    private function percentage(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 1);
    }
}
