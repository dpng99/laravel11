<?php

namespace App\Http\Controllers;

use App\Services\SatkerAccessService;
use App\Services\SpipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSpipController extends Controller
{
    public function index(): Response
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        return Inertia::render('Admin/Spip', [
            'tahun' => $this->tahun(),
        ]);
    }

    public function dashboard(SpipService $spip): JsonResponse
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        return response()->json([
            'status' => 'sukses',
            'data' => $spip->adminDashboard($this->tahun()),
        ]);
    }

    public function intip(string $userId, SpipService $spip): JsonResponse
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        return response()->json([
            'status' => 'sukses',
            'data' => $spip->adminIntip($userId, $this->tahun()),
        ]);
    }

    public function approve(string $userId, SpipService $spip): JsonResponse
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        $result = $spip->approvePm($userId, $this->tahun());

        return response()->json($result, $result['status'] === 'sukses' ? 200 : 422);
    }

    public function resetStatus(string $userId, SpipService $spip): JsonResponse
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        $result = $spip->resetStatus($userId, $this->tahun());

        return response()->json($result, $result['status'] === 'sukses' ? 200 : 422);
    }

    public function download(Request $request, SpipService $spip): JsonResponse
    {
        app(SatkerAccessService::class)->abortUnlessAdmin();

        $data = $request->validate([
            'user_id' => ['required', 'string', 'max:100'],
        ]);

        $result = $spip->downloadLink($data['user_id'], $this->tahun());

        return response()->json($result, $result['status'] === 'sukses' ? 200 : 422);
    }

    private function tahun(): int
    {
        return (int) session('tahun_terpilih', 2026);
    }
}
