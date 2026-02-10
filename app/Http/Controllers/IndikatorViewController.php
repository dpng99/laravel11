<?php

namespace App\Http\Controllers;

use App\Models\SakipViewModel;
use App\Models\SasproViewModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndikatorViewController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // 1. Ambil Data Indikator Sastra
        $sastraData = SakipViewModel::query()
            ->when($search, function ($query, $search) {
                $query->where('nama_indikator', 'like', "%{$search}%")
                        ->orWhere('id_sastra', 'like', "%{$search}%")
                      ->orWhere('nama_sastra', 'like', "%{$search}%");
            })
            ->orderBy('id_sastra')
            ->paginate(10, ['*'], 'sastra_page'); // Gunakan nama page custom agar pagination tidak bentrok

        // 2. Ambil Data Indikator Saspro
        $sasproData = SasproViewModel::query()
            ->when($search, function ($query, $search) {
                $query->where('nama_indikator', 'like', "%{$search}%")
                    ->orWhere('id_saspro', 'like', "%{$search}%")
                      ->orWhere('nama_saspro', 'like', "%{$search}%")
                      ->orWhere('nama_sastra', 'like', "%{$search}%");
            })
            ->orderBy('id_sastra')
            ->orderBy('id_saspro')
            ->paginate(10, ['*'], 'saspro_page');

        return Inertia::render('Indikator/Index', [
            'dataSastra' => $sastraData,
            'dataSaspro' => $sasproData,
            'filters' => $request->only(['search'])
        ]);
    }
}