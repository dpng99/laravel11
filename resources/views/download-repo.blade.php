<div>
@extends('layouts.app') @section('content')
<div class="container" style="padding: 40px 20px;">
    <div class="card shadow" style="max-width: 600px; margin: 0 auto; border-radius: 10px; border: 1px solid #ddd;">
        
        <div class="card-header" style="background-color: #e6bf3e; color: white; border-radius: 10px 10px 0 0;">
            <h4 style="margin: 0; padding: 15px; font-weight: bold; text-align: center; font-size: 1.2rem;">
                📦 Panel Admin: Backup Data Repository
            </h4>
        </div>
        
        <div class="card-body" style="padding: 30px; background-color: #fcfcfc;">

            @if (session('error'))
                <div class="alert alert-danger" style="background-color: #f8d7da; color: #842029; padding: 12px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c2c7;">
                    <strong>❌ Gagal:</strong> {{ session('error') }}
                </div>
            @endif

            <p style="color: #555; margin-bottom: 25px; line-height: 1.6;">
                Silakan pilih Satuan Kerja (Satker) pada menu di bawah ini. Sistem akan merangkum seluruh dokumen PDF milik Satker tersebut ke dalam 1 file ZIP secara otomatis.
            </p>

            <div class="form-group" style="margin-bottom: 25px;">
                <label for="satkerSelect" style="font-weight: bold; margin-bottom: 10px; display: block; color: #333;">
                    Pilih Satuan Kerja:
                </label>
                <select id="satkerSelect" class="form-control" style="width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem; cursor: pointer;">
                    <option value="">-- Klik untuk Pilih Satker --</option>
                    @foreach($daftarSatker as $satker)
                        <option value="{{ $satker->id_satker }}">
                            [{{ $satker->id_satker }}] - {{ $satker->nama_satker }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button onclick="prosesDownload()" id="btnDownload"
                    style="background-color: #198754; color: white; padding: 14px 20px; text-decoration: none; border: none; border-radius: 5px; font-weight: bold; width: 100%; cursor: pointer; font-size: 1.1rem; transition: background-color 0.3s;">
                ⬇️ Mulai Kompres & Download ZIP
            </button>

        </div>
    </div>
</div>

<script>
    function prosesDownload() {
        var select = document.getElementById('satkerSelect');
        var satkerId = select.value;

        // Validasi jika belum memilih satker
        if (!satkerId) {
            alert('⚠️ Mohon pilih Satker dari daftar terlebih dahulu!');
            return;
        }

        // Ubah tampilan tombol menjadi status Loading
        var btn = document.getElementById('btnDownload');
        btn.innerHTML = '⏳ Sedang mengompres data... Mohon tunggu!';
        btn.style.backgroundColor = '#6c757d'; // Warna Abu-abu
        btn.style.cursor = 'wait';
        btn.disabled = true;

        // Arahkan browser ke rute eksekusi download ZIP
        window.location.href = '/migrasi/download-zip/' + satkerId;

        // Kembalikan tombol ke bentuk semula setelah 7 detik 
        // (Asumsi dialog "Save As" browser sudah muncul di layar)
        setTimeout(function() {
            btn.innerHTML = '⬇️ Mulai Kompres & Download ZIP';
            btn.style.backgroundColor = '#198754'; // Warna Hijau kembali
            btn.style.cursor = 'pointer';
            btn.disabled = false;
        }, 7000);
    }
</script>
@endsection
</div>
