<?php

namespace App\Http\Controllers;

use App\Models\Aturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File; // Pastikan import Facade File
use Illuminate\Support\Str;
use Inertia\Inertia;

class AturanController extends Controller
{
    public function index()
    {
        // Gunakan pagination untuk performa lebih baik
        $aturan = Aturan::orderBy('id_tahun', 'desc')->paginate(10);

        return Inertia::render('Aturan', [
            'aturan' => $aturan,
            'flash' => session('flash'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_namaproduk' => 'required|string|max:255',
            'id_tahun'      => 'required|numeric|digits:4',
            'file'          => 'required|file|mimes:pdf,doc,docx|max:10240', // Max 10MB
        ]);

        $data = [
            'id_namaproduk' => $request->id_namaproduk,
            'id_tahun'      => $request->id_tahun,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // Format nama file: slug-nama_timestamp.ext
            $filename = Str::slug($request->id_namaproduk) . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Simpan ke folder public/uploads/peraturan agar bisa diakses public
            $file->move(public_path('uploads/peraturan'), $filename);
            $data['id_filename'] = $filename;
        }

        Aturan::create($data);

        return redirect()->back()->with('success', 'Peraturan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $aturan = Aturan::findOrFail($id);

        $request->validate([
            'id_namaproduk' => 'required|string|max:255',
            'id_tahun'      => 'required|numeric|digits:4',
            'file'          => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = [
            'id_namaproduk' => $request->id_namaproduk,
            'id_tahun'      => $request->id_tahun,
        ];

        if ($request->hasFile('file')) {
            // Hapus file lama jika ada
            if ($aturan->id_filename && file_exists(public_path('uploads/peraturan/' . $aturan->id_filename))) {
                unlink(public_path('uploads/peraturan/' . $aturan->id_filename));
            }

            // Upload file baru
            $file = $request->file('file');
            $filename = Str::slug($request->id_namaproduk) . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/peraturan'), $filename);
            $data['id_filename'] = $filename;
        }

        $aturan->update($data);

        return redirect()->back()->with('success', 'Peraturan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $aturan = Aturan::findOrFail($id);

        // Hapus file fisik
        if ($aturan->id_filename && file_exists(public_path('uploads/peraturan/' . $aturan->id_filename))) {
            unlink(public_path('uploads/peraturan/' . $aturan->id_filename));
        }

        $aturan->delete();

        return redirect()->back()->with('success', 'Peraturan berhasil dihapus.');
    }
}