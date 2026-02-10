@extends('layouts.app')

@section('title', 'Evaluasi')

@section('content')
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>Evaluasi</b></h2>
                    </center>
                </div>
                <div class="card-body">
                    @php
                        $activeTab = session('active_tab', 'lke'); // Default ke renstra
                        $levelSakip = session('id_sakip_level', 0);
                    @endphp
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'lke' ? 'active' : '' }}" id="lke-tab" data-bs-toggle="tab" href="#lke"
                                role="tab" aria-controls="{{ $activeTab == 'lke' ? 'true' : 'false' }}" aria-selected="false">Bukti Dukung LKE</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'lhe-akip' ? 'active' : '' }}" id="lhe-akip-tab"
                                data-bs-toggle="tab" href="#lhe-akip" role="tab"
                                aria-controls="{{ $activeTab == 'lhe-akip' ? 'true' : 'false' }}" aria-selected="false">LHE
                                AKIP</a>
                        </li>

                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'tl-lhe-akip' ? 'active' : '' }}" id="tl-lhe-akip-tab"
                                data-bs-toggle="tab" href="#tl-lhe-akip" role="tab"
                                aria-controls="{{ $activeTab == 'tl-lhe-akip' ? 'true' : 'false' }}"
                                aria-selected="false">TL LHE AKIP</a>
                        </li>

                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'monev-renaksi' ? 'active' : '' }}" id="monev-renaksi-tab"
                                data-bs-toggle="tab" href="#monev-renaksi" role="tab"
                                aria-controls="{{ $activeTab == 'monev-renaksi' ? 'true' : 'false' }}"
                                aria-selected="false">Laporan Monev Renaksi</a>
                        </li>
                        @if ($levelSakip == 99)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'evaluasi-internal' ? 'active' : '' }}" id="evaluasi-internal-tab" data-bs-toggle="tab"
                                href="#evaluasi-internal" role="tab" aria-controls="{{ $activeTab == 'tevaluasi-internal' ? 'true' : 'false' }}"
                                aria-selected="true">Evaluasi Internal</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'evaluasi-rencana' ? 'active' : '' }}" id="evaluasi-rencana-tab" data-bs-toggle="tab" href="#evaluasi-rencana"
                                role="tab" aria-controls="{{ $activeTab == 'evaluasi-rencana' ? 'true' : 'false' }}" aria-selected="false">Evaluasi Rencana
                                Aksi</a>
                        </li>
                        @endif
                    </ul>
 
                    <!-- Tabs Content -->
                    <div class="tab-content mt-3" id="myTabContent">
                        <!-- LKE Section -->
                        <div class="tab-pane fade {{ $activeTab == 'lke' ? 'show active' : '' }}"
                        id="lke" role="tabpanel" aria-labelledby="lke-tab">
                            <h5>Dokumen/Bukti Dukung Lembar Kerja Evaluasi AKIP Internal Kejaksaan Tahun 2025</h5>
                           <p>
                                Halaman ini digunakan untuk melihat dokumen/bukti dukung sebagaimana tercantum pada Lembar Kerja Evaluasi (LKE) AKIP Tahun 2025 yang terdiri dari:
                                <br>1. Dokumen versi terakhir yang sudah diupload pada menu "Perencanaan", "Pelaporan" dan "Evaluasi".
                                <br>2. Dokumen baru sebagaimana ditentukan pada LKE yang perlu diupload melalui halaman ini (sesuai dengan kode kriterianya).
                                <br>
                                Adapun untuk memberikan nilai baik di tahap Penilaian Mandiri maupun tahap Evaluasi tetap menggunakan LKE dengan format excel yang dapat diunduh melalui tautan:
                                <a href="https://linktr.ee/ev_akip25" target="_blank">https://linktr.ee/ev_akip25</a>
                            </p>
                            <p class="text-danger">*upload dokumen maks. 4 MB</p>
                            @include('kelola.evaluasi.lke')
                        </div>
                        <!-- LHE AKIP Section -->
                        <div class="tab-pane fade {{ $activeTab == 'lhe-akip' ? 'show active' : '' }}" id="lhe-akip"
                            role="tabpanel" aria-labelledby="lhe-akip-tab">
                            <h5>Laporan Hasil Evaluasi AKIP</h5>

                            <!-- Alert for success -->
                            @if (session('success-lhe'))
                                <div class="alert alert-success" id="success-alert">
                                    {{ session('success-lhe') }}
                                </div>
                            @endif

                            <!-- Form Upload LHE AKIP -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header text-white" style="background-color: #e6bf3e;">
                                    <h6 class="mb-0">Upload Dokumen TL LHE AKIP</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('upload.lhe_akip') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="lhe_akip_file" class="form-label">Upload File PDF (Max:
                                                4MB)</label>
                                            <input type="file" class="form-control" id="lhe_akip_file"
                                                name="lhe_akip_file" accept=".pdf" required>
                                        </div>
                                        <button type="submit" class="btn btn-block"
                                            style="background-color: #e6bf3e;">Upload File</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Tabel Data LHE AKIP -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama File</th>
                                            <th>Versi</th>
                                            <th>Tanggal Upload</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lheAkipFiles as $index => $file)
                                            <tr class="bg-warning bg-opacity-25">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                   <a href="{{ asset('uploads/repository/' . $file->id_satker . '/' . $file->id_filename) }}"
                                                        target="_blank">
                                                        LHE AKIP
                                                        ({{ $tahun }})
                                                    </a>
                                                </td>
                                                <td>{{ $file->id_perubahan }}</td>
                                                <td>{{ $file->id_tglupload }}</td>

                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TL LHE AKIP Section -->
                        <div class="tab-pane fade {{ $activeTab == 'tl-lhe-akip' ? 'show active' : '' }}"
                            id="tl-lhe-akip" role="tabpanel" aria-labelledby="tl-lhe-akip-tab">
                            <h5>Tindak Lanjut Laporan Hasil Evaluasi AKIP</h5>

                            <!-- Alert for success -->
                            @if (session('success-tllhe'))
                                <div class="alert alert-success" id="success-alert">
                                    {{ session('success-tllhe') }}
                                </div>
                            @endif

                            <!-- Form Upload TL LHE AKIP -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header text-white" style="background-color: #e6bf3e;">
                                    <h6 class="mb-0">Upload Dokumen TL LHE AKIP</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('upload.tl_lhe_akip') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="tllhe_file" class="form-label">Upload File PDF (Max:
                                                4MB)</label>
                                            <input type="file" class="form-control" id="tllhe_file"
                                                name="tllhe_file" accept=".pdf" required>
                                        </div>
                                        <button type="submit" class="btn btn-block"
                                            style="background-color: #e6bf3e;">Upload File</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Tabel Data TL LHE AKIP -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama File</th>
                                            <th>Versi</th>
                                            <th>Tanggal Upload</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tlLheAkipFiles as $index => $file)
                                            <tr class="bg-warning bg-opacity-25">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <a href="{{ asset('uploads/repository/' . $file->id_satker . '/' . $file->id_filename) }}"
                                                        target="_blank">
                                                        TL LHE AKIP
                                                        ({{ $tahun }})
                                                    </a>
                                                </td>
                                                <td>{{ $file->id_perubahan }}</td>
                                                <td>{{ $file->id_tglupload }}</td>

                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Monev Renaksi Section -->
                        <div class="tab-pane fade {{ $activeTab == 'monev-renaksi' ? 'show active' : '' }}"
                            id="monev-renaksi" role="tabpanel" aria-labelledby="monev-renaksi-tab">
                            <h5>Laporan Monev Renaksi</h5>

                            <!-- Alert for success -->
                            @if (session('success-monev'))
                                <div class="alert alert-success" id="success-alert">
                                    {{ session('success-monev') }}
                                </div>
                            @endif

                            <!-- Form Upload File -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header text-white" style="background-color: #e6bf3e;">
                                    <h6 class="mb-0">Upload Dokumen Monev Renaksi</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('upload.monev_renaksi') }}" method="POST"
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
                                            <label for="monev_file" class="form-label">Upload File PDF (Max: 4MB)</label>
                                            <input type="file" class="form-control" id="monev_file" name="monev_file"
                                                accept=".pdf" required>
                                        </div>
                                        <button type="submit" class="btn btn-block"
                                            style="background-color: #e6bf3e;">Upload File</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Tabel Data Monev Renaksi -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama File</th>
                                            <th>Triwulan</th>
                                            <th>Versi</th>
                                            <th>Tanggal Upload</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($monevRenaksiFiles as $index => $file)
                                            <tr class="bg-warning bg-opacity-25">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <a href="{{ asset('uploads/repository/' . $file->id_satker . '/' . $file->id_filename) }}"
                                                        target="_blank">
                                                        Monev Renaksi
                                                        ({{ $tahun }})
                                                        -
                                                        {{ $file->id_triwulan }}
                                                    </a>
                                                </td>
                                                <td>{{ $file->id_triwulan }}</td>
                                                <td>{{ $file->id_perubahan }}</td>
                                                <td>{{ $file->id_tglupload }}</td>

                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade {{ $activeTab == 'evaluasi-internal' ? 'show active' : '' }}"
                        id="evaluasi-internal" role="tabpanel" aria-labelledby="evaluasi-internal-tab">
                            <h5>Evaluasi Internal</h5>
                            <p>Content for Evaluasi Internal goes here.</p>
                        </div>
                        <div class="tab-pane fade {{ $activeTab == 'evaluasi-rencana' ? 'show active' : '' }}"
                        id="evaluasi-rencana" role="tabpanel" aria-labelledby="evaluasi-rencana-tab">
                            <h5>Evaluasi Rencana Aksi</h5>
                            <p>Content for Evaluasi Rencana Aksi goes here.</p>
                        </div>
                    </div>
                </div>
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

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
