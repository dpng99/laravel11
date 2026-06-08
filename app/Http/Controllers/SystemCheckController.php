<?php

namespace App\Http\Controllers;

use App\Services\SatkerAccessService;
use App\Services\SystemCheckService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemCheckController extends Controller
{
    public function index(SystemCheckService $service)
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        return Inertia::render('Admin/SystemCheck', [
            'tahun' => session('tahun_terpilih', date('Y')),
            'satkers' => $service->satkers(),
        ]);
    }

    public function apiCheck(SystemCheckService $service)
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        return response()->json($service->apiHealth());
    }

    public function searchSatkers(Request $request, SystemCheckService $service)
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        return response()->json($service->satkers($request->query('q', '')));
    }

    public function documentCheck(Request $request, SystemCheckService $service)
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        $validated = $request->validate([
            'id_satker' => ['required', 'string'],
            'tahun' => ['nullable', 'string'],
        ]);

        return response()->json(
            $service->documentCheck(
                $validated['id_satker'],
                $validated['tahun'] ?? session('tahun_terpilih', date('Y'))
            )
        );
    }
}
