<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HapusData extends Controller
{
    public function hapus (Request $request)
    {
        $dataId = $request->input('id');

        // Logika untuk menghapus data berdasarkan ID
        // Misalnya, jika menggunakan Eloquent:
        // Model::destroy($dataId);

        return response()->json(['message' => 'Data berhasil dihapus', 'id' => $dataId]);
    }
}
