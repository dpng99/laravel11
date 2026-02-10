@extends('layouts.app')

@section('title', 'Pelaporan')

@section('content')
    @php
        $levelSakip = session('id_sakip_level', 0);
    @endphp
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>Pelaporan</b></h2>
                    </center>
                </div>
                <div class="card-body">
                    @php
                        $activeTab = session('active_tab', 'lkjip');
                    @endphp

                    @if (session('success-update'))
                        <div class="alert alert-success" id="success-alert-update">{{ session('success-update') }}</div>
                    @endif
                    @if (session('success-delete'))
                        <div class="alert alert-success" id="success-alert-delete">{{ session('success-delete') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger" id="success-alert-error">{{ session('error') }}</div>
                    @endif
                    <ul class="nav nav-tabs" id="myTab" role="tablist">

                        @if ($levelSakip == 99)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab == 'triwulan2' ? 'active' : '' }}" id="triwulan2-tab"
                                    data-bs-toggle="tab" href="#triwulan2" role="tab"
                                    aria-controls="{{ $activeTab == 'triwulan2' ? 'true' : 'false' }}"
                                    aria-selected="false">Capaian Triwulan II</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab == 'triwulan3' ? 'active' : '' }}" id="triwulan3-tab"
                                    data-bs-toggle="tab" href="#triwulan3" role="tab"
                                    aria-controls="{{ $activeTab == 'triwulan3' ? 'true' : 'false' }}"
                                    aria-selected="false">Capaian Triwulan III</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab == 'triwulan4' ? 'active' : '' }}" id="triwulan4-tab"
                                    data-bs-toggle="tab" href="#triwulan4" role="tab"
                                    aria-controls="{{ $activeTab == 'triwulan4' ? 'true' : 'false' }}"
                                    aria-selected="false">Capaian Triwulan IV</a>
                            </li>
                        @endif

                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'lkjip' ? 'active' : '' }}" id="lkjip-tab"
                                data-bs-toggle="tab" href="#lkjip" role="tab"
                                aria-controls="{{ $activeTab == 'lkjip' ? 'true' : 'false' }}" aria-selected="false">Laporan
                                Kinerja (LKJiP)</a>
                        </li>

                        @if ($tahun != 2024)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab == 'capaian' ? 'active' : '' }}" id="capaian-tab"
                                    data-bs-toggle="tab" href="#capaian" role="tab"
                                    aria-controls="{{ $activeTab == 'capaian' ? 'true' : 'false' }}"
                                    aria-selected="true">Capaian Kinerja</a>
                            </li>
                        @endif

                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'rapat-staff-eka' ? 'active' : '' }}"
                                id="rapat-staff-eka-tab" data-bs-toggle="tab" href="#rapat-staff-eka" role="tab"
                                aria-controls="{{ $activeTab == 'rapat-staff-eka' ? 'true' : 'false' }}"
                                aria-selected="false">Rapat Staff EKA</a>
                        </li>


                        @if ($levelSakip == 99)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $activeTab == 'validasi-apip' ? 'active' : '' }}"
                                    id="validasi-apip-tab" data-bs-toggle="tab" href="#validasi-apip" role="tab"
                                    aria-controls="{{ $activeTab == 'validasi-apip' ? 'true' : 'false' }}"
                                    aria-selected="false">Validasi APIP</a>
                            </li>
                        @endif
                    </ul>

                    @php
                        use App\Models\Bidang;
                    @endphp
                    <div class="tab-content mt-3" id="myTabContent">
                        
                        <div class="tab-pane fade {{ $activeTab == 'capaian' ? 'show active' : '' }}" id="capaian"
                            role="tabpanel" aria-labelledby="capaian-tab">
                            <h5>Capaian Kinerja</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-header bg-warning">
                                            <strong>Daftar Bidang</strong>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $level = session('id_sakip_level');
                                                $satkernama = session('satkernama') ?? '';
                                                // $bidangs = [];
                                                $kataTerakhir = strtolower(strrchr(' ' . $satkernama, ' '));
                                                if ($level == 0) {
                                                    // Admin atau superuser: ambil semua bidang
                                                    $bidangs = \App\Models\Bidang::whereNotNull('bidang_level')
                                                        ->where('hide', 0)
                                                        ->orderBy('bidang_lokasi', 'asc')
                                                        ->orderBy('bidang_level', 'asc')
                                                        ->get();
                                                } elseif ($level == 1) {
                                                    $bidangs = \App\Models\Bidang::where('bidang_lokasi', $level)
                                                        ->where('hide', 0)
                                                        ->where('bidang_nama', 'LIKE', '%' . trim($kataTerakhir))
                                                        ->whereNotNull('bidang_level')
                                                        ->orderBy('bidang_level', 'asc')
                                                        ->get();
                                                } elseif (str_starts_with(strtoupper($satkernama), 'CABJARI')) {
                                                    $bidangs = \App\Models\Bidang::where('bidang_lokasi', $level)
                                                        ->whereNotNull('bidang_level')
                                                        ->orderBy('bidang_level', 'asc')
                                                        ->get();

                                                    if (
                                                        $bidangs->isNotEmpty() &&
                                                        stripos($bidangs[0]->bidang_nama, 'kepala') === 0
                                                    ) {
                                                        $bidangs[0]->bidang_nama = 'Kepala Cabang Kejaksaan Negeri';
                                                    }
                                                } elseif ($level > 1) {
                                                    $bidangs = \App\Models\Bidang::where('bidang_lokasi', $level)
                                                        ->whereNotNull('bidang_level')
                                                        ->orderBy('bidang_level', 'asc')
                                                        ->get();
                                                }
                                            @endphp
                                            @foreach ($bidangs as $bidang)
                                                <button class="btn btn-outline-success text-black w-100 mb-2 bidang-item"
                                                    data-rumpun="{{ $bidang->rumpun }}">
                                                    {{ $bidang->bidang_nama }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-9">
                                    <div class="card" id="controls-wrapper" class="mb-3" style="display:none;">
                                        <div class="card-header d-flex align-items-center gap-2">
                                            <span>📋 Indikator</span>
                                            <select id="triwulan" class="form-select w-auto">
                                                <option value="1" selected>Triwulan 1</option>
                                                <option value="2">Triwulan 2</option>
                                                <option value="3">Triwulan 3</option>
                                                <option value="4">Triwulan 4</option>
                                            </select>
                                            <button id="reloadBtn" class="btn btn-success">Pilih</button>
                                        </div>
                                        <div class="card-body" id="subindikator-wrapper">
                                            <form method="POST" action="#">
                                                <strong>(Sub Indikator)</strong>
                                                <div class="subindikator-wrapper">Pilih Bidang Terlebih dahulu</div>

                                                <div class="text-end mt-4">
                                                    <button type="submit" class="btn btn-success" id="btn-simpan"
                                                        style="background-color: #198754; color: white; display: none;">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if ($levelSakip == 99)
                            <div class="tab-pane fade {{ $activeTab == 'triwulan2' ? 'show active' : '' }}"
                                id="triwulan2" role="tabpanel" aria-labelledby="triwulan2-tab">
                                <h5>Capaian Triwulan II</h5>
                                <p>Content for Capaian Triwulan II goes here.</p>
                            </div>
                            <div class="tab-pane fade {{ $activeTab == 'triwulan3' ? 'show active' : '' }}"
                                id="triwulan3" role="tabpanel" aria-labelledby="triwulan3-tab">
                                <h5>Capaian Triwulan III</h5>
                                <p>Content for Capaian Triwulan III goes here.</p>
                            </div>
                            <div class="tab-pane fade {{ $activeTab == 'triwulan4' ? 'show active' : '' }}"
                                id="triwulan4" role="tabpanel" aria-labelledby="triwulan4-tab">
                                <h5>Capaian Triwulan IV</h5>
                                <p>Content for Capaian Triwulan IV goes here.</p>
                            </div>
                        @endif

                        <div class="tab-pane fade {{ $activeTab == 'lkjip' ? 'show active' : '' }}" id="lkjip"
                            role="tabpanel" aria-labelledby="lkjip-tab">
                            <h5>Laporan Kinerja (LKJiP)</h5>

                            @if (session('success-lkjip'))
                                <div class="alert alert-success" id="success-alert">
                                    {{ session('success-lkjip') }}
                                </div>
                            @endif

                            <div class="card shadow-sm mb-3">
                                <div class="card-header text-white" style="background-color: #e6bf3e;">
                                    <h6 class="mb-0">Upload Dokumen LKJiP</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('upload.lkjip') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="id_triwulan" class="form-label">Pilih Triwulan</label>
                                            <select class="form-control" id="id_triwulan" name="id_triwulan" required>
                                                <option value="" disabled selected>Pilih Triwulan</option>
                                                <option value="TW 1">Triwulan 1</option>
                                                <option value="TW 2">Triwulan 2</option>
                                                <option value="TW 3">Triwulan 3</option>
                                                <option value="TW 4">Triwulan 4</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="lkjip_file" class="form-label">Upload File PDF (Max: 4MB)</label>
                                            <input type="file" class="form-control" id="lkjip_file" name="lkjip_file"
                                                accept=".pdf" required>
                                        </div>
                                        <button type="submit" class="btn btn-block"
                                            style="background-color: #e6bf3e;">Upload File</button>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-warning"> 
                                        <tr>
                                            <th>No</th>
                                            <th>Nama File</th>
                                            <th>Triwulan</th>
                                            <th>Versi</th>
                                            <th>Tanggal Upload</th>
                                            <th>Aksi</th> </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lkjipFiles as $index => $file)
                                            <tr class="bg-warning bg-opacity-25">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    @php
                                                        // Logika path file Anda yang sudah ada
                                                        if ($tahun == 2024) {
                                                            if (!empty($file->id_triwulan)) {
                                                                $finalPath = 'uploads/repository/' . $file->id_satker . '/lkjip_' . $tahun . '_' . $file->id_perubahan . '_' . $file->id_triwulan . '.pdf';
                                                            } else {
                                                                $finalPath = 'uploads/repository/' . $file->id_satker . '/lkjip_' . $tahun . '_' . $file->id_perubahan . '.pdf';
                                                            }
                                                        } else {
                                                             // Ambil dari id_filename (lebih aman)
                                                            $finalPath = 'uploads/repository/' . $file->id_satker . '/' . $file->id_filename;
                                                        }
                                                    @endphp

                                                    <a href="{{ asset($finalPath) }}" target="_blank">
                                                        LKJIP ({{ $tahun }})
                                                        @if($file->id_triwulan)
                                                        - {{ $file->id_triwulan }}
                                                        @endif
                                                    </a>

                                                </td>
                                                <td>{{ $file->id_triwulan ?? '-' }}</td>
                                                <td>{{ $file->id_perubahan }}</td>
                                                <td>{{ $file->id_tglupload }}</td>
                                                
                                                <td class="d-flex gap-2">
                                                    <!-- <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editPelaporanModal"
                                                            data-id="{{ $file->id }}"
                                                            data-type="lkjip"
                                                            data-action-url="{{ route('pelaporan.update', ['type' => 'lkjip', 'id' => $file->id]) }}"
                                                            data-triwulan="{{ $file->id_triwulan }}">
                                                        Edit
                                                    </button> -->
                                                    <form action="{{ route('pelaporan.delete', ['type' => 'lkjip', 'id' => $file->id]) }}" 
                                                          method="POST" 
                                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                    </form>
                                                </td>
                                                </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        <div class="tab-pane fade {{ $activeTab == 'rapat-staff-eka' ? 'show active' : '' }}"
                            id="rapat-staff-eka" role="tabpanel" aria-labelledby="rapat-staff-eka-tab">
                            <h5>Rapat Staff EKA</h5>

                            @if (session('success-rastaff'))
                                <div class="alert alert-success" id="success-alert">
                                    {{ session('success-rastaff') }}
                                </div>
                            @endif

                            <div class="card shadow-sm mb-3">
                                <div class="card-header text-white" style="background-color: #e6bf3e;">
                                    <h6 class="mb-0">Upload Dokumen Rapat Staff EKA</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('upload.rapat_staff_eka') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="id_triwulan_rapat" class="form-label">Pilih Triwulan</label>
                                            <select class="form-control" id="id_triwulan_rapat" name="id_triwulan" required>
                                                <option value="" disabled selected>Pilih Triwulan</option>
                                                <option value="TW 1">Triwulan 1</option>
                                                <option value="TW 2">Triwulan 2</option>
                                                <option value="TW 3">Triwulan 3</option>
                                                <option value="TW 4">Triwulan 4</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="rapat_file" class="form-label">Upload File PDF (Max: 4MB)</label>
                                            <input type="file" class="form-control" id="rapat_file" name="rapat_file"
                                                accept=".pdf" required>
                                        </div>
                                        <button type="submit" class="btn btn-block"
                                            style="background-color: #e6bf3e;">Upload File</button>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama File</th>
                                            <th>Triwulan</th>
                                            <th>Versi</th>
                                            <th>Tanggal Upload</th>
                                            <th>Aksi</th> </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rapatStaffEkaFiles as $index => $file)
                                            <tr class="bg-warning bg-opacity-25">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <a href="{{ asset('uploads/repository/' . $file->id_satker . '/' . $file->id_filename) }}"
                                                        target="_blank">
                                                        Rapat Staff EKA
                                                        ({{ $tahun }})
                                                        -
                                                        {{ $file->id_triwulan }}
                                                    </a>
                                                </td>
                                                <td>{{ $file->id_triwulan }}</td>
                                                <td>{{ $file->id_perubahan }}</td>
                                                <td>{{ $file->id_tglupload }}</td>

                                                <td class="d-flex gap-2">
                                                   <!--  <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editPelaporanModal"
                                                            data-id="{{ $file->id }}"
                                                            data-type="rapat-staff-eka"
                                                            data-action-url="{{ route('pelaporan.update', ['type' => 'rapat-staff-eka', 'id' => $file->id]) }}"
                                                            data-triwulan="{{ $file->id_triwulan }}">
                                                        Edit
                                                    </button> -->
                                                    <form action="{{ route('pelaporan.delete', ['type' => 'rapat-staff-eka', 'id' => $file->id]) }}" 
                                                          method="POST" 
                                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                    </form>
                                                </td>
                                                </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($levelSakip == 99)
                            <div class="tab-pane fade {{ $activeTab == 'validasi-apip' ? 'show active' : '' }}"
                                id="validasi-apip" role="tabpanel" aria-labelledby="validasi-apip-tab">
                                <h2>Validasi APIP</h2>

                                @if (session('success-validasi'))
                                    <div class="alert alert-success" id="success-alert">
                                        {{ session('success-validasi') }}
                                    </div>
                                @endif

                                <p>Content for IKU goes here...</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title" id="notifModalLabel">Peringatan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p id="notifModalMessage" class="fs-5"></p>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="editPelaporanModal" tabindex="-1" aria-labelledby="editPelaporanModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPelaporanModalLabel">Edit Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="editPelaporanForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_file_pelaporan" class="form-label">
                                Upload File Baru (PDF, Max 5MB)
                            </label>
                            <input type="file" class="form-control" id="edit_file_pelaporan" name="file" accept=".pdf">
                            <small class="text-muted">Kosongkan jika hanya ingin mengubah Triwulan.</small>
                        </div>

                        <div class="mb-3">
                            <label for="edit_id_triwulan" class="form-label">Pilih Triwulan</label>
                            <select class="form-control" id="edit_id_triwulan" name="id_triwulan" required>
                                <option value="" disabled>Pilih Triwulan</option>
                                <option value="TW 1">Triwulan 1</option>
                                <option value="TW 2">Triwulan 2</option>
                                <option value="TW 3">Triwulan 3</option>
                                <option value="TW 4">Triwulan 4</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection

@section('styles')
    <style>
        .nav-tabs .nav-link {
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            margin-right: -1px;
        }

        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .nav-tabs {
            border-bottom: 1px solid #ddd;
        }

        .tab-content {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            background-color: #f8f9fa;
        }

        .tab-pane {
            min-height: 200px;
            /* Adjust as needed */
        }
    </style>
@endsection

{{-- [MODIFIKASI] Mengoreksi struktur @section('scripts') menjadi @push('scripts') --}}
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        setTimeout(function() {
            // Ambil semua alert sukses (termasuk yg baru)
            document.querySelectorAll('.alert-success').forEach(function(alert) {
                if (alert) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500); 
                }
            });
            // Ambil semua alert error
            document.querySelectorAll('.alert-danger').forEach(function(alert) {
                 if (alert) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500); 
                }
            });
        }, 3000); // Alert hilang setelah 3 detik
    </script>
    <script>
        $(document).ready(function() {
            let selectedRumpun = null;
            let indikator = {
                // indikator_penghitungan: "{{ $indikator->indikator_penghitungan ?? '' }}" 
                // [CATATAN]: $indikator tidak terdefinisi di sini, jadi saya akan komen.
                // Logika 'capaian' Anda sudah menanganinya di dalam AJAX.
            };
            let labelPenghitungan = ['Ditangani', 'Diselesaikan'];
            // ... (Kode JS Anda untuk tab 'capaian' sudah ada di sini dan tidak diubah) ...
            
            function loadSubIndikator(rumpun, triwulan) {
                $('#subindikator-wrapper').html('<p>Loading...</p>');

                $.ajax({
                    url: `/pelaporan/subindikator/${rumpun}`,
                    data: {
                        triwulan
                    },
                    success: function(data) {
                        let html = '';

                        if (!data || data.length === 0) {
                            html = '<p class="text-muted">Tidak ada indikator ditemukan.</p>';
                        }

                        data.forEach((item) => {
                            if (!item.indikator_nama) return; // skip data kosong

                            let labelPenghitungan = ['Ditangani', 'Diselesaikan'];
                            if (item.indikator_penghitungan) {
                                let labels = item.indikator_penghitungan.split(',').map(s => s
                                    .trim());
                                if (labels.length === 2 && labels[0] && labels[1]) {
                                    labelPenghitungan = labels;
                                }
                            }

                            html += `
                        <div class="mb-4 p-3 border rounded shadow-sm">
                            <strong>${item.indikator_nama}</strong>
                            <table class="table table-bordered align-middle mt-3">
                                <thead class="text-center bg-warning">
                                    <tr>
                                        <th>Persentase Penyelesaian</th>
                                        <th>Target PK Tahunan</th>
                                        <th>Capaian Target PK Tahunan</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <tr>
                                        <td>${item.persentase || 0}%</td>
                                        <td>${item.target_pk || 0}%</td>
                                        <td>${item.capaian_pk || 0}%</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mb-2">
                                <label><strong>Faktor-Faktor:</strong></label>
                                <textarea class="form-control faktor-input" rows="2"
                                    data-indikator-id="${item.indikator_id}">${item.faktor || ''}</textarea>
                            </div>

                            <div class="mb-2">
                                <label><strong>Upaya Optimalisasi:</strong></label>
                                <textarea class="form-control langkah-input" rows="2"
                                    data-indikator-id="${item.indikator_id}">${item.langkah || ''}</textarea>
                            </div>

                            <button class="btn btn-sm btn-primary btn-simpan-faktor"
                                data-indikator-id="${item.indikator_id}">Simpan</button>
                        </div>
                        `;
                        });

                        $('#subindikator-wrapper').html(html);
                    },
                    error: function() {
                        $('#subindikator-wrapper').html(
                        '<p class="text-danger">Gagal memuat data.</p>');
                    }
                });
            }


            // Ketika klik bidang
            $('.bidang-item').on('click', function() {
                $(".bidang-item").removeClass("active");
                $(this).addClass("active");

                selectedRumpun = $(this).data('rumpun');
                $("#controls-wrapper").show();
                let triwulan = $('#triwulan').val();
                loadSubIndikator(selectedRumpun, triwulan);
            });

            // Ketika ganti triwulan
            $('#triwulan').on('change', function() {
                let triwulan = $(this).val();
                console.log('Triwulan ganti:', triwulan, 'Rumpun:', selectedRumpun);
                if (selectedRumpun) {
                    loadSubIndikator(selectedRumpun, triwulan);
                }
            });

            // Klik tombol reload
            $(document).on('click', '#reloadBtn', function() {
                $(this).addClass("active");

                let triwulan = $('#triwulan').val();
                loadSubIndikator(selectedRumpun, triwulan);
            });

            $(document).on('click', '.btn-simpan-faktor', function() {
                const indikatorId = $(this).data('indikator-id');
                // console.log('Indikator ID:', indikatorId); // Pastikan indikatorId tidak undefined

                const faktor = $(`.faktor-input[data-indikator-id='${indikatorId}']`).val();
                const langkah = $(`.langkah-input[data-indikator-id='${indikatorId}']`).val();
                const triwulan = $('#triwulan').val();
                const btn = $(this);

                if (!indikatorId) {
                    alert('Indikator ID tidak ditemukan.');
                    return;
                }

                btn.prop('disabled', true).text('Menyimpan...');

                // console.log('ID:', indikatorId, 'Faktor:', faktor, 'Langkah:', langkah, 'Triwulan:',
                //     triwulan);

                $.ajax({
                    url: '/pelaporan/simpan-keterangan',
                    method: 'POST',
                    data: {
                        indikator_id: indikatorId,
                        faktor: faktor,
                        langkah: langkah,
                        triwulan: triwulan,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        showNotifModal('Berhasil disimpan!', 'Sukses');

                        btn.prop('disabled', false).text('Simpan');
                    },

                    error: function(err) {
                        console.log(err);
                        if (err.status === 404 && err.responseJSON?.message) {
                            showNotifModal(err.responseJSON.message ??
                                'Terjadi kesalahan saat menyimpan data.', 'Peringatan');

                        } else {
                            showNotifModal('Terjadi kesalahan saat menyimpan data.');
                        }
                        btn.prop('disabled', false).text('Simpan');
                    }


                });
            });

            function showNotifModal(pesan, judul = 'Peringatan') {
                $('#notifModalLabel').text(judul);
                $('#notifModalMessage').text(pesan);
                const notifModal = new bootstrap.Modal(document.getElementById('notifModal'));
                notifModal.show();
            }

            // [MODIFIKASI] 4. TAMBAH JAVASCRIPT MODAL
            // ========================================================
            // JAVASCRIPT UNTUK MODAL EDIT PELAPORAN
            // ========================================================
            var editPelaporanModal = document.getElementById('editPelaporanModal');
            if (editPelaporanModal) {
                var form = document.getElementById('editPelaporanForm');
                var triwulanSelect = document.getElementById('edit_id_triwulan');

                editPelaporanModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget; 
                    
                    var actionUrl = button.getAttribute('data-action-url');
                    var triwulan = button.getAttribute('data-triwulan');
                    
                    // Set action form
                    form.setAttribute('action', actionUrl);
                    
                    // Set triwulan
                    if (triwulan) {
                        triwulanSelect.value = triwulan;
                    } else {
                        triwulanSelect.value = ""; // Reset jika tidak ada data triwulan
                    }
                });

                editPelaporanModal.addEventListener('hidden.bs.modal', function (event) {
                    form.reset();
                    form.setAttribute('action', '');
                    triwulanSelect.value = "";
                });
            }
            // ========================================================
            // AKHIR JAVASCRIPT MODAL
            // ========================================================
        });
    </script>
@endpush