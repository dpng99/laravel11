<?php

namespace App\Http\Controllers;

use App\Services\SpipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SpipController extends Controller
{
    public function index(Request $request, SpipService $spip): Response
    {
        $session = $spip->sessionForAppUser($request->user(), $this->tahun());

        return Inertia::render('Spip/Index', [
            'tahun' => $this->tahun(),
            'spipSession' => is_array($session) ? $session : null,
            'spipAccessError' => is_string($session) ? $session : null,
        ]);
    }

    public function session(Request $request, SpipService $spip): JsonResponse
    {
        $data = $request->validate([
            'tahapan' => ['nullable', 'string', 'in:Penilaian Mandiri,Penjaminan Kualitas'],
        ]);

        $session = $spip->sessionForAppUser($request->user(), $this->tahun(), $data['tahapan'] ?? null);

        if (is_string($session)) {
            return response()->json(['status' => 'error', 'pesan' => $session], 422);
        }

        return response()->json($session);
    }

    public function subUnsur(Request $request, SpipService $spip): JsonResponse
    {
        $session = $this->currentSpipSession($request, $spip);
        if (is_string($session)) {
            return response()->json(['status' => 'error', 'pesan' => $session], 422);
        }

        return response()->json([
            'status' => 'sukses',
            'data' => $spip->subUnsurData($session['userId'], $this->tahun()),
        ]);
    }

    public function detail(Request $request, SpipService $spip): JsonResponse
    {
        $data = $request->validate([
            'kode_sub' => ['required', 'string', 'max:50'],
        ]);
        $session = $this->currentSpipSession($request, $spip);
        if (is_string($session)) {
            return response()->json(['status' => 'error', 'pesan' => $session], 422);
        }

        return response()->json([
            'status' => 'sukses',
            'data' => $spip->detailKriteria($session['userId'], $data['kode_sub'], $this->tahun()),
        ]);
    }

    public function klusters(SpipService $spip): JsonResponse
    {
        return response()->json([
            'status' => 'sukses',
            'data' => $spip->klusterOptions($this->tahun()),
        ]);
    }

    public function store(Request $request, SpipService $spip): JsonResponse
    {
        $data = $request->validate([
            'kodeSub' => ['required', 'string', 'max:50'],
            'tahapan' => ['required', 'string', 'in:Penilaian Mandiri,Penjaminan Kualitas'],
            'gradeTerpilih' => ['required', 'string', 'in:A,B,C,D,E,a,b,c,d,e'],
            'uraianMap' => ['nullable', 'array'],
            'uraianMap.*' => ['nullable', 'string'],
            'aoiData' => ['nullable', 'array'],
            'aoiData.kAoI' => ['nullable', 'string'],
            'aoiData.uAoI' => ['nullable', 'string'],
            'aoiData.kSebab' => ['nullable', 'string'],
            'aoiData.uSebab' => ['nullable', 'string'],
        ]);
        $session = $this->currentSpipSession($request, $spip, $data['tahapan']);
        if (is_string($session)) {
            return response()->json(['status' => 'error', 'pesan' => $session], 422);
        }

        try {
            return response()->json([
                'status' => 'sukses',
                'pesan' => $spip->saveKertasKerja([
                    ...$data,
                    'userId' => $session['userId'],
                    'spipUserId' => $session['spipUserId'],
                ], $this->tahun()),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['status' => 'error', 'pesan' => $exception->getMessage()], 422);
        }
    }

    public function updateStatus(Request $request, SpipService $spip): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:Menunggu Approve PK,Selesai'],
            'tahapan' => ['nullable', 'string', 'in:Penilaian Mandiri,Penjaminan Kualitas'],
        ]);
        $session = $this->currentSpipSession($request, $spip, $data['tahapan'] ?? null);
        if (is_string($session)) {
            return response()->json(['status' => 'error', 'pesan' => $session], 422);
        }

        $message = $spip->updateStatusPk($session['spipUserId'], $data['status'], $this->tahun());
        $ok = str_starts_with($message, 'Berhasil');

        return response()->json([
            'status' => $ok ? 'sukses' : 'error',
            'pesan' => $message,
        ], $ok ? 200 : 422);
    }

    public function download(Request $request, SpipService $spip): JsonResponse
    {
        $session = $this->currentSpipSession($request, $spip);
        if (is_string($session)) {
            return response()->json(['status' => 'error', 'pesan' => $session], 422);
        }

        $result = $spip->downloadLink($session['spipUserId'], $this->tahun());

        return response()->json($result, $result['status'] === 'sukses' ? 200 : 422);
    }

    private function tahun(): int
    {
        return (int) session('tahun_terpilih', 2026);
    }

    private function currentSpipSession(Request $request, SpipService $spip, ?string $tahapan = null): array|string
    {
        return $spip->sessionForAppUser($request->user(), $this->tahun(), $tahapan);
    }
}
