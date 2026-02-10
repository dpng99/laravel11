@extends('layouts.app')

@section('title', 'Perencanaan')

@section('content')
    @php
        $levelSakip = session('id_sakip_level', 0);
    @endphp
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card" style="width: 100%;">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>Perencanaan</b></h2>
                    </center>
                </div>
                <div class="card-body">
                    @php
                        $activeTab = session('active_tab', 'renstra'); // Default ke renstra
                    @endphp

                    @if (session('success-update'))
                        <div class="alert alert-success">{{ session('success-update') }}</div>
                    @endif
                    @if (session('success-delete'))
                        <div class="alert alert-success">{{ session('success-delete') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-orange-600 {{ $activeTab == 'renstra' ? 'active' : '' }}"
                                id="renstra-tab" data-bs-toggle="tab" href="#renstra" role="tab" aria-controls="renstra"
                                aria-selected="{{ $activeTab == 'renstra' ? 'true' : 'false' }}">Renstra</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'iku' ? 'active' : '' }}" id="iku-tab"
                                data-bs-toggle="tab" href="#iku" role="tab" aria-controls="iku"
                                aria-selected="{{ $activeTab == 'iku' ? 'true' : 'false' }}">IKU (Penetapan Target
                                Kinerja)</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'renja' ? 'active' : '' }}" id="renja-tab"
                                data-bs-toggle="tab" href="#renja" role="tab" aria-controls="renja"
                                aria-selected="{{ $activeTab == 'renja' ? 'true' : 'false' }}">Renja</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'rkakl' ? 'active' : '' }}" id="rkakl-tab"
                                data-bs-toggle="tab" href="#rkakl" role="tab" aria-controls="rkakl"
                                aria-selected="{{ $activeTab == 'rkakl' ? 'true' : 'false' }}">RKAKL</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'dipa' ? 'active' : '' }}" id="dipa-tab"
                                data-bs-toggle="tab" href="#dipa" role="tab" aria-controls="dipa"
                                aria-selected="{{ $activeTab == 'dipa' ? 'true' : 'false' }}">DIPA</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'renaksi' ? 'active' : '' }}" id="renaksi-tab"
                                data-bs-toggle="tab" href="#renaksi" role="tab"
                                aria-controls="{{ $activeTab == 'renaksi' ? 'true' : 'false' }}"
                                aria-selected="false">Rencana Aksi</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab == 'perjanjian-kinerja' ? 'active' : '' }}"
                                id="perjanjian-kinerja-tab" data-bs-toggle="tab" href="#perjanjian-kinerja"
                                role="tab"
                                aria-controls="{{ $activeTab == 'perjanjian-kinerja' ? 'true' : 'false' }}"
                                aria-selected="false">Perjanjian Kinerja</a>
                        </li>
                        {{-- @endif --}}
                        @if ($tahun != 2024)
                            @if ($levelSakip == 99)
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="cetak-pk-tab" data-bs-toggle="tab" href="#cetak-pk"
                                        role="tab" aria-controls="cetak-pk" aria-selected="false">Cetak PK</a>
                                </li>
                            @endif
                        @endif
                    </ul>

                    <div class="tab-content mt-3" id="myTabContent">
                        <div class="tab-pane fade {{ $activeTab == 'renstra' ? 'show active' : '' }}" id="renstra"
                            role="tabpanel" aria-labelledby="renstra-tab">
                            <div class="renstra-content">
                                @php
                                    if ($tahun == '2024') {
                                        $id_tahun = '2019 - 2024';
                                    } else {
                                        $id_tahun = '2025 - 2029';
                                    }
                                @endphp
                                <h3><strong>Rencana Strategis (Renstra) Tahun {{ $id_tahun }}</strong></h3>
                                <p class="card-title p-2" style="background-color: #f1e022; color: black;">Rencana Strategis
                                    (Renstra) merupakan dokumen perencanaan yang menetapkan tujuan, sasaran, strategi,
                                    kebijakan, program, dan kegiatan pembangunan dalam jangka waktu lima tahun.</p>

                                <div>
                                    <div class="card shadow-sm">
                                        <div class="card-header text-white" style="background-color: #e6bf3e;">
                                            <h4 class="mb-0">Upload File Renstra</h4>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('upload.renstra') }}" method="POST"
                                                enctype="multipart/form-data" class="mb-4">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="renstra_file" class="form-label">Upload File PDF
                                                        Renstra (Max: 2MB)</label>
                                                    <input type="file" class="form-control" id="renstra_file"
                                                        name="renstra_file" accept=".pdf" required>
                                                </div>
                                                <button type="submit" class="btn btn-warning btn-block">Upload
                                                    File</button>
                                            </form>
                                            @if (session('success-renstra'))
                                                <div class="alert alert-success" id="success-alert">
                                                    {{ session('success-renstra') }}
                                                </div>
                                            @endif

                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="table-warning">
                                                        <tr>
                                                            <th>No</th>
                                                            <th>File Renstra</th>
                                                            <th>Versi</th>
                                                            <th>Tanggal Upload</th>
                                                            <th>Aksi</th> </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($renstra as $index => $item)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>
                                                                    <a href="{{ asset('uploads/repository/' . $item->id_satker . '/renstra_' . $tahun . '_' . $item->id_perubahan . '.pdf') }}"
                                                                        target="_blank"
                                                                        style="text-decoration: none; color: inherit;">
                                                                        {{ $item->id_periode == 'P1' ? 'Periode 2020 - 2024' : 'Periode 2025 - 2029' }}
                                                                    </a>
                                                                </td>

                                                                <td>{{ $item->id_perubahan }}</td>
                                                                <td>{{ $item->id_tglupload }}</td>
                                                                
                                                                <td class="d-flex gap-2">
                                                                   <!--  <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#editFileModal"
                                                                            data-id="{{ $item->id }}"
                                                                            data-type="renstra"
                                                                            data-action-url="{{ route('perencanaan.update', ['type' => 'renstra', 'id' => $item->id]) }}">
                                                                        Edit
                                                                    </button> -->

                                                                    <form action="{{ route('perencanaan.delete', ['type' => 'renstra', 'id' => $item->id]) }}" 
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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab == 'iku' ? 'show active' : '' }}" id="iku"
                            role="tabpanel" aria-labelledby="iku-tab">
                            <div class="iku-content">
                                <h3><strong>Indikator Kinerja Utama (IKU)</strong></h3>
                                <p class="card-title p-2" style="background-color: #f1e022; color: black;">Indikator
                                    Kinerja Utama (IKU) Kejaksaan adalah ukuran keberhasilan dalam mencapai tujuan dan
                                    sasaran strategis Kejaksaan, yang digunakan sebagai acuan untuk menyusun rencana
                                    kinerja, kerja, anggaran, dan evaluasi kinerja</p>

                                <div>
                                    <div class="card shadow-sm">
                                        <div class="card-header text-white" style="background-color: #e6bf3e;">
                                            <h4 class="mb-0">Upload File IKU</h4>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('upload.iku') }}" method="POST"
                                                enctype="multipart/form-data" class="mb-4">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="iku_file" class="form-label">Upload File PDF Iku (Max:
                                                        2MB)</label>
                                                    <input type="file" class="form-control" id="iku_file"
                                                        name="iku_file" accept=".pdf" required>
                                                </div>
                                                <button type="submit" class="btn btn-warning btn-block">Upload
                                                    File</button>
                                            </form>
                                            @if (session('success-iku'))
                                                <div class="alert alert-success" id="success-alert">
                                                    {{ session('success-iku') }}
                                                </div>
                                            @endif

                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="table-warning">
                                                        <tr>
                                                            <th>No</th>
                                                            <th>File Iku</th>
                                                            <th>Versi</th>
                                                            <th>Tanggal Upload</th>
                                                            <th>Aksi</th> </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($iku as $index => $item)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>
                                                                    <a href="{{ asset('uploads/repository/' . $item->id_satker . '/IKU_' . $tahun . '_' . $item->id_perubahan . '.pdf') }}"
                                                                        target="_blank"
                                                                        style="text-decoration: none; color: inherit;">
                                                                        {{ $item->id_periode }}
                                                                    </a>
                                                                </td>
                                                                <td>{{ $item->id_perubahan }}</td>
                                                                <td>{{ $item->id_tglupload }}</td>
                                                                
                                                                <td class="d-flex gap-2">
                                                                   <!--  <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#editFileModal"
                                                                            data-id="{{ $item->id }}"
                                                                            data-type="iku"
                                                                            data-action-url="{{ route('perencanaan.update', ['type' => 'iku', 'id' => $item->id]) }}">
                                                                        Edit
                                                                    </button> -->
                                                                    <form action="{{ route('perencanaan.delete', ['type' => 'iku', 'id' => $item->id]) }}" 
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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab == 'renja' ? 'show active' : '' }}" id="renja"
                            role="tabpanel" aria-labelledby="renja-tab">
                            <div class="renja-content">
                                <h3><strong>Rencana Kerja Tahunan</strong></h3>
                                <p class="card-title p-2" style="background-color: #f1e022; color: black;">Rencana Kinerja
                                    Tahunan (RKT) merupakan penjabaran dari sasaran dan program yang telah
                                    ditetapkan dalam Iku, dan akan dilaksanakan oleh satuan organisasi/kerja melalui
                                    berbagai kegiatan tahunan. <br> ... (deskripsi lengkap) ... </p>

                                <div>
                                    <div class="card shadow-sm">
                                        <div class="card-header text-white" style="background-color: #e6bf3e;">
                                            <h4 class="mb-0">Upload File Renja</h4>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('upload.renja') }}" method="POST"
                                                enctype="multipart/form-data" class="mb-4">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="renja_file" class="form-label">Upload File PDF
                                                        Renja (Max: 2MB)</label>
                                                    <input type="file" class="form-control" id="renja_file"
                                                        name="renja_file" accept=".pdf" required>
                                                </div>
                                                <button type="submit" class="btn btn-warning btn-block">Upload
                                                    File</button>
                                            </form>
                                            @if (session('success-renja'))
                                                <div class="alert alert-success" id="success-alert">
                                                    {{ session('success-renja') }}
                                                </div>
                                            @endif

                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="table-warning">
                                                        <tr>
                                                            <th>No</th>
                                                            <th>File Renja</th>
                                                            <th>Versi</th>
                                                            <th>Tanggal Upload</th>
                                                            <th>Aksi</th> </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($renja as $index => $item)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>
                                                                    <a href="{{ asset('uploads/repository/' . $item->id_satker . '/renja_' . $tahun . '_' . $item->id_perubahan . '.pdf') }}"
                                                                        target="_blank"
                                                                        style="text-decoration: none; color: inherit;">
                                                                        {{ $item->id_periode }}
                                                                    </a>
                                                                </td>
                                                                <td>{{ $item->id_perubahan }}</td>
                                                                <td>{{ $item->id_tglupload }}</td>
                                                                
                                                              <!--   <td class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#editFileModal"
                                                                            data-id="{{ $item->id }}"
                                                                            data-type="renja"
                                                                            data-action-url="{{ route('perencanaan.update', ['type' => 'renja', 'id' => $item->id]) }}">
                                                                        Edit
                                                                    </button> -->
                                                                    <form action="{{ route('perencanaan.delete', ['type' => 'renja', 'id' => $item->id]) }}" 
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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ $activeTab == 'rkakl' ? 'show active' : '' }}" id="rkakl"
                            role="tabpanel" aria-labelledby="rkakl-tab">
                            <div class="rkakl-content">
                                <h3><strong>Rencana Kerja Anggaran Kementerian atau Lembaga</strong></h3>
                                <p class="card-title p-2" style="background-color: #f1e022; color: black;">Data Kebutuhan
                                    Riil ... (deskripsi lengkap) ... </p>

                                <div>
                                    <div class="card shadow-sm">
                                        <div class="card-header text-white" style="background-color: #e6bf3e;">
                                            <h4 class="mb-0">UPLOAD File RKAKL</h4>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('upload.rkakl') }}" method="POST"
                                                enctype="multipart/form-data" class="mb-4">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="rkakl_file" class="form-label">Upload File PDF
                                                        RKAKL (Max: 2MB)</label>
                                                    <input type="file" class="form-control" id="rkakl_file"
                                                        name="rkakl_file" accept=".pdf" required>
                                                </div>
                                                <button type="submit" class="btn btn-warning btn-block">Upload
                                                    File</button>
                                            </form>
                                            @if (session('success-rkakl'))
                                                <div class="alert alert-success" id="success-alert">
                                                    {{ session('success-rkakl') }}
                                                </div>
                                            @endif

                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="table-warning">
                                                        <tr>
                                                            <th>No</th>
                                                            <th>File RKAKL</th>
                                                            <th>Versi</th>
                                                            <th>Tanggal Upload</th>
                                                            <th>Aksi</th> </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($rkakl as $index => $item)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>
                                                                    <a href="{{ asset('uploads/repository/' . $item->id_satker . '/rkakl_' . $tahun . '_' . $item->id_perubahan . '.pdf') }}"
                                                                        target="_blank"
                                                                        style="text-decoration: none; color: inherit;">
                                                                        {{ $item->id_periode }}
                                                                    </a>
                                                                </td>
                                                                <td>{{ $item->id_perubahan }}</td>
                                                                <td>{{ $item->id_tglupload }}</td>
                                                                
                                                               <!--  <td class="d-flex gap-2">
                                                                    <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#editFileModal"
                                                                            data-id="{{ $item->id }}"
                                                                            data-type="rkakl"
                                                                            data-action-url="{{ route('perencanaan.update', ['type' => 'rkakl', 'id' => $item->id]) }}">
                                                                        Edit
                                                                    </button> -->
                                                                    <form action="{{ route('perencanaan.delete', ['type' => 'rkakl', 'id' => $item->id]) }}" 
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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade {{ session('active_tab') == 'dipa' ? 'show active' : '' }}"
                            id="dipa" role="tabpanel" aria-labelledby="dipa-tab">
                            <div class="dipa-content">
                                <h3><strong>Daftar Isian Pelaksanaan Anggaran (DIPA)</strong></h3>
                                <p class="card-title p-2" style="background-color: #f1e022; color: black;">
                                    Daftar Isian Pelaksanaan Anggaran (DIPA) Kejaksaan menjadi dasar bagi Satuan Kerja
                                    (Satker) Kejaksaan untuk melaksanakan kegiatan yang telah direncanakan.
                                </p>

                                @if (session('success-dipa'))
                                    <div class="alert alert-success">{{ session('success-dipa') }}</div>
                                @endif
                                <div class="card shadow-sm">
                                    <div class="card-header text-white" style="background-color: #e6bf3e;">
                                        <h4 class="mb-0">UPLOAD DIPA SATKER ANDA</h4>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('upload.dipa') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="dipa_file" class="form-label">Upload File PDF DIPA (Max:
                                                    2MB)</label>
                                                <input type="file" class="form-control" id="dipa_file"
                                                    name="dipa_file" accept=".pdf" required>
                                                @error('dipa_file')
                                                    <div class="text-danger">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="id_pagu" class="form-label">Total Pagu</label>
                                                <input type="text" class="form-control format-number"
                                                    id="id_pagu" name="id_pagu_formatted" required>
                                                <input type="hidden" id="id_pagu_hidden" name="id_pagu">
                                            </div>

                                            <div class="mb-3">
                                                <label for="id_gakyankum" class="form-label">Program Penegakan dan
                                                    Pelayanan Hukum</label>
                                                <input type="text" class="form-control format-number"
                                                    id="id_gakyankum" name="id_gakyankum_formatted" required>
                                                <input type="hidden" id="id_gakyankum_hidden" name="id_gakyankum">
                                            </div>

                                            <div class="mb-3">
                                                <label for="id_dukman" class="form-label">Program Dukungan
                                                    Manajemen</label>
                                                <input type="text" class="form-control format-number"
                                                    id="id_dukman" name="id_dukman_formatted" required>
                                                <input type="hidden" id="id_dukman_hidden" name="id_dukman">
                                            </div>

                                            <button type="submit" class="btn btn-warning btn-block">Upload
                                                File</button>
                                        </form>
                                    </div>

                                    <div class="card-body">
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered table-striped">
                                                <thead class="table-warning">
                                                    <tr>
                                                        <th>No</th>
                                                        <th>File DIPA</th>
                                                        <th>Total Pagu</th>
                                                        <th>Program Penegakan dan Pelayanan Hukum</th>
                                                        <th>Program Dukungan Manajemen</th>
                                                        <th>Versi</th>
                                                        <th>Tanggal Upload</th>
                                                        <th>Aksi</th> </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($dipa as $index => $item)
                                                        <tr>
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>
                                                                <a href="{{ asset('uploads/repository/' . $item->id_satker . '/dipa_' . $tahun . '_' . $item->id_perubahan . '.pdf') }}"
                                                                    target="_blank"
                                                                    style="text-decoration: none; color: inherit;">
                                                                    {{ $item->id_periode }}
                                                                </a>
                                                            </td>
                                                            <td>
                                                                Rp.
                                                                @if (strpos($item->id_pagu, '.') === false)
                                                                    {{ number_format((float) $item->id_pagu, 0, ',', '.') }}
                                                                @else
                                                                    {{ $item->id_pagu }}
                                                                @endif
                                                            </td>

                                                            <td>
                                                                Rp.
                                                                @if (strpos($item->id_gakyankum, '.') === false)
                                                                    {{ number_format((float) $item->id_gakyankum, 0, ',', '.') }}
                                                                @else
                                                                    {{ $item->id_gakyankum }}
                                                                @endif
                                                            </td>

                                                            <td>
                                                                Rp.
                                                                @if (strpos($item->id_dukman, '.') === false)
                                                                    {{ number_format((float) $item->id_dukman, 0, ',', '.') }}
                                                                @else
                                                                    {{ $item->id_dukman }}
                                                                @endif
                                                            </td>

                                                            <td>{{ $item->id_perubahan }}</td>
                                                            <td>{{ $item->id_tglupload }}</td>
                                                            
                                                           <!--  <td class="d-flex gap-2">
                                                                <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#editFileModal"
                                                                        data-id="{{ $item->id }}"
                                                                        data-type="dipa"
                                                                        data-action-url="{{ route('perencanaan.update', ['type' => 'dipa', 'id' => $item->id]) }}"
                                                                        data-pagu="{{ $item->id_pagu }}"
                                                                        data-gakyankum="{{ $item->id_gakyankum }}"
                                                                        data-dukman="{{ $item->id_dukman }}">
                                                                    Edit
                                                                </button> -->
                                                                
                                                                <form action="{{ route('perencanaan.delete', ['type' => 'dipa', 'id' => $item->id]) }}" 
                                                                      method="POST" 
                                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="8" class="text-center">Belum ada data DIPA
                                                                yang
                                                                diunggah.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                let inputs = document.querySelectorAll('.format-number');

                                inputs.forEach(function(input) {
                                    input.addEventListener('input', function() {
                                        let value = this.value.replace(/\D/g, ''); // Hapus semua karakter non-angka
                                        let formattedValue = new Intl.NumberFormat('id-ID').format(value);

                                        this.value = formattedValue; // Tampilkan format dengan titik
                                        let hiddenInput = document.getElementById(this.id + "_hidden");
                                        if (hiddenInput) {
                                            hiddenInput.value = value; // Simpan nilai asli tanpa titik
                                        }
                                    });
                                });
                            });
                        </script>


                        <div class="tab-pane fade {{ $activeTab == 'renaksi' ? 'show active' : '' }}" id="renaksi"
                            role="tabpanel" aria-labelledby="renaksi-tab">
                            <div class="renaksi-content">
                                <h3><strong>Rencana Kerja Anggaran Kementerian atau Lembaga</strong></h3>
                                <p class="card-title p-2" style="background-color: #f1e022; color: black;">Data Kebutuhan
                                    Riil ... (deskripsi lengkap) ... </p>

                                <div>
                                    <div class="card shadow-sm">
                                        <div class="card-header text-white" style="background-color: #e6bf3e;">
                                            <h4 class="mb-0">UPLOAD File RENCANA AKSI</h4>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('upload.renaksi') }}" method="POST"
                                                enctype="multipart/form-data" class="mb-4">
                                                @csrf
                                                <div class="mb-3">
                                                    <label for="renaksi_file" class="form-label">Upload File PDF
                                                        Rencana Aksi (Max: 2MB)</label>
                                                    <input type="file" class="form-control" id="renaksi_file"
                                                        name="renaksi_file" accept=".pdf" required>
                                                </div>
                                                <button type="submit" class="btn btn-warning btn-block">Upload
                                                    File</button>
                                            </form>
                                            @if (session('success-renaksi'))
                                                <div class="alert alert-success" id="success-alert">
                                                    {{ session('success-renaksi') }}
                                                </div>
                                            @endif

                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="table-warning">
                                                        <tr>
                                                            <th>No</th>
                                                            <th>File Rencana Aksi</th>
                                                            <th>Versi</th>
                                                            <th>Tanggal Upload</th>
                                                            <th>Aksi</th> </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($renaksi as $index => $item)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>
                                                                    <a href="{{ asset('uploads/repository/' . $item->id_satker . '/renaksi_' . $tahun . '_' . $item->id_perubahan . '.pdf') }}"
                                                                        target="_blank"
                                                                        style="text-decoration: none; color: inherit;">
                                                                        Renaksi Tahun {{ $item->id_periode }}
                                                                    </a>
                                                                </td>
                                                                <td>{{ $item->id_perubahan }}</td>
                                                                <td>{{ $item->id_tglupload }}</td>
                                                                
                                                                <td class="d-flex gap-2">
                                                                  <!--   <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#editFileModal"
                                                                            data-id="{{ $item->id }}"
                                                                            data-type="renaksi"
                                                                            data-action-url="{{ route('perencanaan.update', ['type' => 'renaksi', 'id' => $item->id]) }}">
                                                                        Edit
                                                                    </button> -->
                                                                    <form action="{{ route('perencanaan.delete', ['type' => 'renaksi', 'id' => $item->id]) }}" 
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
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="container mt-4"></div>

                        <div class="tab-pane  fade {{ $activeTab == 'perjanjian-kinerja' ? 'show active' : '' }}"
                            id="perjanjian-kinerja" role="tabpanel" aria-labelledby="perjanjian-kinerja-tab">
                            @if (session('success-pk'))
                                <div class="alert alert-success" id="success-alert">
                                    {{ session('success-pk') }}
                                </div>
                            @endif
                            <h3><strong>Perjanjian Kinerja</strong></h3>
                            <p class="card-title p-2" style="background-color: #f1e022; color: black;">Pengisian Target
                                Perjanjian Kinerja</p>
                            
                            <div class="card shadow-sm">
                                <div class="card-header text-white" style="background-color: #e6bf3e;">
                                    <h4 class="mb-0">UPLOAD File Perjanjian Kinerja</h4>
                                </div>
                                <div class="card-body">
                                    @if ($tahun != 2024)
                                        <form action="{{ route('upload.pk') }}" method="POST" enctype="multipart/form-data"
                                            class="mb-4">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="pk_file" class="form-label">Upload File PDF Perjanjian Kinerja
                                                    (Max: 5MB) <br> Cukup 1 File saja yang memuat PK seluruh pejabat</label>
                                                <input type="file" class="form-control" id="pk_file" name="pk_file"
                                                    accept=".pdf" required>
                                            </div>
                                            <button type="submit" class="btn btn-warning btn-block">Upload File</button>
                                        </form>
                                    @endif

                                    @if (session('success-pk-file'))
                                        <div class="alert alert-success" id="success-alert">
                                            {{ session('success-pk-file') }}
                                        </div>
                                    @endif

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-warning">
                                                <tr>
                                                    <th>No</th>
                                                    <th>File Perjanjian Kinerja</th>
                                                    <th>Versi</th>
                                                    {{-- <th>Nama File</th> --}}
                                                    <th>Tanggal Upload</th>
                                                    <th>Aksi</th> </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($pk as $index => $item)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <a href="{{ asset('uploads/repository/' . $item->id_satker . '/' . $item->id_filename) }}"
                                                                target="_blank"
                                                                style="text-decoration: none; color: inherit;">
                                                                PK Tahun {{ $item->id_periode }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $item->id_perubahan }}</td>
                                                        {{-- <td>{{ $item->id_filename }}</td> --}}
                                                        <td>{{ $item->id_tglupload }}</td>
                                                        
                                                        <td class="d-flex gap-2">
                                                            <!-- <button type="button" class="btn btn-warning btn-sm btn-edit" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editFileModal"
                                                                    data-id="{{ $item->id }}"
                                                                    data-type="pk"
                                                                    data-action-url="{{ route('perencanaan.update', ['type' => 'pk', 'id' => $item->id]) }}">
                                                                Edit
                                                            </button> -->
                                                            <form action="{{ route('perencanaan.delete', ['type' => 'pk', 'id' => $item->id]) }}" 
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
                            </div>

                            @php
                                $level = session('id_sakip_level');
                                $satkernama = session('satkernama') ?? '';
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
                                        ->whereRaw("LOWER(REPLACE(bidang_nama, '_', ' ')) LIKE ?", [
                                            '%' . strtolower(trim($kataTerakhir)),
                                        ])
                                        ->whereNotNull('bidang_level')
                                        ->orderBy('bidang_level', 'asc')
                                        ->get();
                                } elseif (str_starts_with(strtoupper($satkernama), 'CABJARI')) {
                                    $bidangs = \App\Models\Bidang::where('bidang_lokasi', $level)
                                        ->whereNotNull('bidang_level')
                                        ->orderBy('bidang_level', 'asc')
                                        ->get();

                                    if ($bidangs->isNotEmpty() && stripos($bidangs[0]->bidang_nama, 'kepala') === 0) {
                                        $bidangs[0]->bidang_nama = 'Kepala Cabang Kejaksaan Negeri';
                                    }
                                } elseif ($level > 1) {
                                    $bidangs = \App\Models\Bidang::where('bidang_lokasi', $level)
                                        ->whereNotNull('bidang_level')
                                        ->orderBy('bidang_level', 'asc')
                                        ->get();
                                }
                            @endphp

                            @foreach ($bidangs as $index => $bidang)
                                @php
                                    $indikators = \App\Models\Indikator::where('link', $bidang->rumpun)
                                        ->where(function ($query) use ($tahun) {
                                            $query->where('tahun', 'LIKE', "%$tahun%"); // cocokkan sebagian tahun
                                        })
                                        ->where(function ($query) use ($level) {
                                            if ($level == 1) {
                                                $query->whereIn('lingkup', [0, 1]);
                                            } elseif ($level == 2) {
                                                $query->whereIn('lingkup', [0, 2, 5, 7]);
                                            } elseif ($level == 3) {
                                                $query->whereIn('lingkup', [0, 3, 5, 6, 7]);
                                            } elseif ($level == 4) {
                                                $query->whereIn('lingkup', [0, 4, 6, 7]);
                                            }
                                        })
                                        ->get();
                                @endphp


                                @if ($tahun != 2024)
                                <div class="card mb-2">
                                    <div class="card-header d-flex justify-content-between align-items-center"
                                        style="background-color: #e6bf3e; color: white;">
                                        {{ $bidang->bidang_nama }}
                                        <a data-bs-toggle="collapse" href="#collapseBidang{{ $index }}"
                                            role="button" aria-expanded="false"
                                            aria-controls="collapseBidang{{ $index }}"
                                            class="collapse-toggle d-flex align-items-center">
                                            <i class="bi bi-chevron-down text-white rotate-icon"></i>
                                        </a>
                                    </div>

                                    <div class="collapse" id="collapseBidang{{ $index }}">
                                        <div class="card-body">
                                            @if ($indikators->isNotEmpty())
                                                <div class="row">
                                                    @foreach ($indikators as $key => $indikator)
                                                        <div class="col-md-6">
                                                            <div class="card mb-2">
                                                                <div class="card-body">
                                                                    <h5 class="text-center"
                                                                        style="font-weight: bold; color: black;">
                                                                        {{ $indikator->indikator_nama }}
                                                                    </h5>

                                                                    @if ($tahun != 2024)
                                                                        <form method="POST"
                                                                            action="{{ route('target.store') }}">
                                                                            @csrf
                                                                            <input type="hidden" name="indikator_id"
                                                                                value="{{ $indikator->id }}">

                                                                            <div class="mb-2">
                                                                                <label class="form-label">Target Pertahun
                                                                                    (%)
                                                                                </label>
                                                                                <input type="number" class="form-control"
                                                                                    name="target_tahun"
                                                                                    value="{{ $target[$indikator->id]->target_tahun ?? '' }}">
                                                                            </div>
                                                                            {{-- @if ($tahun != 2025)
                                                                        ... (target triwulan) ...
                                                                            @endif --}}
                                                                            <br>
                                                                            <button type="submit"
                                                                                class="btn btn-success w-100">Simpan</button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @if (($key + 1) % 2 == 0 && !$loop->last)
                                                </div>
                                                <div class="row">
                                            @endif
                            @endforeach
                        </div>
                    @else
                        <p><i>Tidak ada indikator terkait</i></p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    </div>
    </div>
    </div>
    </div>

<div class="modal fade" id="editFileModal" tabindex="-1" aria-labelledby="editFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFileModalLabel">Edit Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editFileForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_file" class="form-label">
                            Upload File Baru (PDF, Max 5MB)
                        </label>
                        <input type="file" class="form-control" id="edit_file" name="file" accept=".pdf">
                        <small class="text-muted">Kosongkan jika hanya ingin update data Pagu (khusus DIPA).</small>
                    </div>

                    <div id="dipaFieldsContainer" style="display: none;">
                        <hr>
                        <p><strong>Data DIPA:</strong></p>
                        <div class="mb-3">
                            <label for="edit_id_pagu" class="form-label">Total Pagu</label>
                            <input type="number" class="form-control" id="edit_id_pagu" name="id_pagu">
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_gakyankum" class="form-label">Program Penegakan dan Pelayanan Hukum</label>
                            <input type="number" class="form-control" id="edit_id_gakyankum" name="id_gakyankum">
                        </div>
                        <div class="mb-3">
                            <label for="edit_id_dukman" class="form-label">Program Dukungan Manajemen</label>
                            <input type="number" class="form-control" id="edit_id_dukman" name="id_dukman">
                        </div>
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
        /* ... (style Anda yang sudah ada) ... */
        .container {
            max-width: 100%;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        .nav-tabs .nav-link {
            width: 12.5%;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
        }
        .nav-tabs .nav-link.active {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .nav-tabs .nav-link:hover {
            border-color: #007bff;
        }
        .tab-content {
            border-top: 1px solid #ddd;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #fff;
        }
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
        }
        .table-bordered {
            border: 1px solid #dee2e6;
        }
        .table thead th {
            border-bottom: 2px solid #dee2e6;
        }
        .table td,
        .table th {
            vertical-align: middle;
        }
    </style>
    <style>
        .rotate-icon {
            transition: transform 0.3s ease;
        }

        .rotate-icon.rotate {
            transform: rotate(180deg);
        }
    </style>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Menghilangkan alert setelah 5 detik
        setTimeout(function() {
            let successAlerts = document.querySelectorAll('.alert-success'); // Ambil semua alert sukses
            successAlerts.forEach(function(successAlert) {
                if (successAlert) {
                    successAlert.style.transition = 'opacity 0.5s ease';
                    successAlert.style.opacity = '0';
                    setTimeout(() => successAlert.remove(), 500); // Hapus elemen setelah transisi selesai
                }
            });
        }, 5000); // 5 detik
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Event ketika collapse dibuka
            document.querySelectorAll('.collapse').forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function() {
                    const icon = this.previousElementSibling.querySelector('.rotate-icon');
                    if (icon) icon.classList.add('rotate');
                });

                // Event ketika collapse ditutup
                collapse.addEventListener('hide.bs.collapse', function() {
                    const icon = this.previousElementSibling.querySelector('.rotate-icon');
                    if (icon) icon.classList.remove('rotate');
                });
            });

            // ========================================================
            // JAVASCRIPT UNTUK MODAL EDIT
            // ========================================================
            var editFileModal = document.getElementById('editFileModal');
            if (editFileModal) {
                var form = document.getElementById('editFileForm');
                var dipaFieldsContainer = document.getElementById('dipaFieldsContainer');
                
                // Input DIPA
                var paguInput = document.getElementById('edit_id_pagu');
                var gakyankumInput = document.getElementById('edit_id_gakyankum');
                var dukmanInput = document.getElementById('edit_id_dukman');
                
                // Saat modal akan ditampilkan
                editFileModal.addEventListener('show.bs.modal', function (event) {
                    // Tombol yang memicu modal
                    var button = event.relatedTarget; 
                    
                    // Ambil data dari tombol
                    var actionUrl = button.getAttribute('data-action-url');
                    var type = button.getAttribute('data-type');
                    
                    // Set action form
                    form.setAttribute('action', actionUrl);
                    
                    // Cek apakah tipenya DIPA
                    if (type === 'dipa') {
                        // Tampilkan field DIPA
                        dipaFieldsContainer.style.display = 'block';
                        
                        // Isi nilainya
                        paguInput.value = button.getAttribute('data-pagu');
                        gakyankumInput.value = button.getAttribute('data-gakyankum');
                        dukmanInput.value = button.getAttribute('data-dukman');

                        // Buat input DIPA 'required'
                        paguInput.required = true;
                        gakyankumInput.required = true;
                        dukmanInput.required = true;

                    } else {
                        // Sembunyikan field DIPA
                        dipaFieldsContainer.style.display = 'none';

                        // Kosongkan nilainya (penting)
                        paguInput.value = '';
                        gakyankumInput.value = '';
                        dukmanInput.value = '';

                        // Hapus 'required'
                        paguInput.required = false;
                        gakyankumInput.required = false;
                        dukmanInput.required = false;
                    }
                });

                // Saat modal ditutup, reset form
                editFileModal.addEventListener('hidden.bs.modal', function (event) {
                    form.reset();
                    form.setAttribute('action', '');
                    dipaFieldsContainer.style.display = 'none';
                    
                    paguInput.required = false;
                    gakyankumInput.required = false;
                    dukmanInput.required = false;
                });
            }
            // ========================================================
            // AKHIR JAVASCRIPT MODAL
            // ========================================================
        });
    </script>
@endpush