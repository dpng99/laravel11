<?php

namespace App\Http\Controllers;

use App\Services\BusinessIntelligenceService;
use App\Services\SatkerAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class BusinessIntelligenceController extends Controller
{
    public function index(Request $request, BusinessIntelligenceService $bi)
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        $year = (string) session('tahun_terpilih', date('Y'));

        try {
            $analysis = $bi->analyze($year);
            $error = null;
        } catch (Throwable $exception) {
            report($exception);
            $analysis = null;
            $error = $exception->getMessage();
        }

        return Inertia::render('Admin/BusinessIntelligence', [
            'tahun' => $year,
            'analysis' => $analysis,
            'analysisError' => $error,
        ]);
    }
}
