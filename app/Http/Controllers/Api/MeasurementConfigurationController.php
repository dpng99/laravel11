<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MeasurementPrecisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasurementConfigurationController extends Controller
{
    public function __construct(private MeasurementPrecisionService $service) {}

    /**
     * GET /api/measurement/thresholds/{level}
     * Ambil konfigurasi threshold untuk suatu level
     */
    public function getThresholds($level)
    {
        $thresholds = DB::table('measurement_thresholds')
            ->where('level_code', $level)
            ->orWhereNull('level_code')
            ->where('active', true)
            ->orderBy('level_code', 'desc')
            ->get();

        return response()->json([
            'level' => $level,
            'thresholds' => $thresholds,
            'count' => $thresholds->count()
        ]);
    }

    /**
     * POST /api/measurement/thresholds/{level}
     * Update atau create konfigurasi threshold
     */
    public function updateThresholds(Request $request, $level)
    {
        $validated = $request->validate([
            'indikator_id' => 'required|string',
            'satker_id' => 'nullable|string',
            'indikator_type' => 'required|in:kinerja,kepatuhan,kelembagaan',
            'excellent_min' => 'required|numeric',
            'good_min' => 'required|numeric',
            'fair_min' => 'required|numeric',
            'poor_max' => 'required|numeric',
            'weight' => 'required|numeric|min:0.1',
            'status_excellent' => 'required|string',
            'status_good' => 'required|string',
            'status_fair' => 'required|string',
            'status_poor' => 'required|string',
            'effective_date' => 'required|date'
        ]);

        // Validasi configuration
        $validation = $this->service->validateConfiguration($validated);
        if (!$validation['valid']) {
            return response()->json(['errors' => $validation['errors']], 422);
        }

        // Insert atau update
        $result = DB::table('measurement_thresholds')->updateOrInsert(
            [
                'level_code' => $level,
                'satker_id' => $validated['satker_id'] ?? null,
                'indikator_id' => $validated['indikator_id'],
                'effective_date' => $validated['effective_date']
            ],
            [
                ...$validated,
                'created_by' => auth()->id() ?? 'api',
                'active' => true
            ]
        );

        return response()->json([
            'message' => 'Konfigurasi threshold berhasil diperbarui',
            'success' => $result
        ]);
    }

    /**
     * GET /api/measurement/indicators/{level}
     * Ambil daftar indikator metadata untuk level tertentu
     */
    public function getIndicators($level)
    {
        $indicators = DB::table('indikator_metadata')
            ->where('level', $level)
            ->orWhereNull('level')
            ->where('deprecated_date', null)
            ->orWhere('deprecated_date', '>', now())
            ->orderBy('nama')
            ->get();

        return response()->json([
            'level' => $level,
            'indicators' => $indicators,
            'count' => $indicators->count()
        ]);
    }

    /**
     * POST /api/measurement/indicators
     * Create indikator metadata baru
     */
    public function createIndicator(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:indikator_metadata',
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
            'indikator_type' => 'required|in:kinerja,kepatuhan,kelembagaan',
            'measurement_unit' => 'nullable|string',
            'calculation_method' => 'nullable|string',
            'is_critical' => 'boolean',
            'weight' => 'numeric|min:0.1',
            'level' => 'nullable|integer'
        ]);

        DB::table('indikator_metadata')->insert([
            ...$validated,
            'effective_date' => now(),
            'created_by' => auth()->id() ?? 'api',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Indikator berhasil dibuat',
            'id' => $validated['id']
        ], 201);
    }

    /**
     * GET /api/measurement/frameworks
     * Daftar measurement framework versions
     */
    public function getFrameworks()
    {
        $frameworks = DB::table('measurement_frameworks')
            ->orderByDesc('effective_date')
            ->get();

        return response()->json([
            'frameworks' => $frameworks,
            'count' => $frameworks->count()
        ]);
    }

    /**
     * GET /api/measurement/frameworks/{version}
     * Detail framework version
     */
    public function getFrameworkDetail($version)
    {
        $framework = DB::table('measurement_frameworks')
            ->where('version', $version)
            ->first();

        if (!$framework) {
            return response()->json(['error' => 'Framework tidak ditemukan'], 404);
        }

        $templates = DB::table('measurement_template_versions')
            ->where('framework_version', $version)
            ->get();

        return response()->json([
            'framework' => $framework,
            'templates' => $templates,
            'config' => json_decode($framework->config_json, true)
        ]);
    }
}
