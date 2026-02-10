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
                $activeTab = session('active_tab', 'capaian'); // Default ke renstra
                @endphp
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">

                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab == 'capaian' ? 'active' : '' }}" id="capaian-tab"
                            data-bs-toggle="tab" href="#capaian" role="tab"
                            aria-controls="{{ $activeTab == 'capaian' ? 'true' : 'false' }}"
                            aria-selected="true">Capaian Kinerja</a>
                    </li>
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

                <!-- Tabs Content -->
                <div class="tab-content mt-3" id="myTabContent">
                    <div class="tab-pane fade {{ $activeTab == 'capaian' ? 'show active' : '' }}" id="capaian"
                        role="tabpanel" aria-labelledby="capaian-tab">
                        <h5>Capaian Kinerja</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card">
                                   
                                    
                                      


                                </div>
                            </div>
                        </div>

                        <div class="col-md-9">
                            <div class="card">
                                <div class="card-header bg-warning text-black">📋 Indikator</div>
                                <div class="card-body" id="subindikator-wrapper">

                                    <form action="{{ route('pengukuran.store') }}" method="POST">
                                        @csrf
                                        <div id="indikator-section">
                                            @if (session('success'))
                                            <div class="alert alert-success">{{ session('success') }}</div>
                                            @endif
                                            <p>Pilih bidang untuk melihat indikator.</p>
                                        </div>
                                        <div class="text-end mt-4">
                                            <button type="submit" class="btn btn-success" id="btn-simpan" style="display: none;">Simpan</button>

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

                    <!-- Alert for success -->
                    @if (session('success-lkjip'))
                    <div class="alert alert-success" id="success-alert">
                        {{ session('success-lkjip') }}
                    </div>
                    @endif

                    <!-- Form Upload File -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header text-white" style="background-color: #e6bf3e;">
                            <h6 class="mb-0">Upload Dokumen LKJiP</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('upload.lkjip') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="triwulan" class="form-label">Pilih Triwulan</label>
                                    <select class="form-control" id="triwulan" name="triwulan" required>
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

                    <!-- Tabel Data LKJiP -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-warning"> <!-- Mengubah warna header tabel menjadi kuning -->
                                <tr>
                                    <th>No</th>
                                    <th>Nama File</th>
                                    <th>Triwulan</th>
                                    <th>Versi</th>
                                    <th>Tanggal Upload</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lkjipFiles as $index => $file)
                                <tr class="bg-warning bg-opacity-25">
                                    <!-- Mengubah warna isi tabel menjadi kuning muda -->
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ asset('uploads/lkjip/' . $file->id_filename) }}"
                                            target="_blank">
                                            LKJIP
                                            ({{ $tahun }})
                                            -
                                            Triwulan {{ $file->triwulan }}
                                        </a>
                                    </td>
                                    <td>Triwulan {{ $file->triwulan }}</td>
                                    <td>{{ $file->id_perubahan }}</td>
                                    <td>{{ $file->id_tglupload }}</td>

                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- Rapat Staff EKA Section -->
                <div class="tab-pane fade {{ $activeTab == 'rapat-staff-eka' ? 'show active' : '' }}"
                    id="rapat-staff-eka" role="tabpanel" aria-labelledby="rapat-staff-eka-tab">
                    <h5>Rapat Staff EKA</h5>

                    <!-- Alert for success -->
                    @if (session('success-rastaff'))
                    <div class="alert alert-success" id="success-alert">
                        {{ session('success-rastaff') }}
                    </div>
                    @endif

                    <!-- Form Upload File -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header text-white" style="background-color: #e6bf3e;">
                            <h6 class="mb-0">Upload Dokumen Rapat Staff EKA</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('upload.rapat_staff_eka') }}" method="POST"
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
                                    <label for="rapat_file" class="form-label">Upload File PDF (Max: 4MB)</label>
                                    <input type="file" class="form-control" id="rapat_file" name="rapat_file"
                                        accept=".pdf" required>
                                </div>
                                <button type="submit" class="btn btn-block"
                                    style="background-color: #e6bf3e;">Upload File</button>
                            </form>
                        </div>
                    </div>

                    <!-- Tabel Data Rapat Staff EKA -->
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
                                @foreach ($rapatStaffEkaFiles as $index => $file)
                                <tr class="bg-warning bg-opacity-25">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ asset('uploads/rapat_staff_eka/' . $file->id_filename) }}"
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

                    <!-- Alert for success -->
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
@push('script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript untuk auto-hide alert -->
<script>
    setTimeout(function() {
        document.getElementById('success-alert')?.remove();
    }, 3000); // Alert hilang setelah 3 detik
</script>

<script>
    const level = "{{ session('id_sakip_level') }}";
    $(document).ready(function() {
        $('.bidang-item').on('click', function() {
            var rumpun = $(this).data('rumpun');
            var level = "{{ session('id_sakip_level') }}";
            $.ajax({
                url: '/get-subindikator/' + rumpun + '?level=' + level,
                method: 'GET',
                success: function(response) {

                    let indikatorSection = $('#indikator-section');
                    indikatorSection.empty();

                    if (response.length === 0) {
                        indikatorSection.html(
                            '<p>Tidak ada Indikator untuk bidang ini.</p>');
                        return;
                    }

                    response.forEach(indikator => {
                        if (!indikator.sub_indikator) return;

                        let subIndikators = indikator.sub_indikator.split(',').map(
                            s => s.trim());


                        subIndikators.forEach(sub => {
                            let table = `
                        <div class="table-responsive mb-4">
                            <strong>${sub}</strong>
                            <input type="hidden" name="indikator_id[${sub}]" value="${indikator.id}" />
                            <input type="hidden" name="sub_indikator_list[]" value="${sub}" />
                            <div class="mb-2 d-flex align-items-center">
                    <label class="me-2 mb-0" style="white-space: nowrap;">Sisa tahun lalu:</label>
                    <input type="text" class="form-control angka-format mb-2" 
                    name="sisa_tahun_lalu[${sub}]" style="width:200px;" />

                </div>

            <table class="table table-bordered text-center mt-2">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        ${bulanList.map(bulan => `<th>${bulan}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Ditangani</td>
                        ${bulanList.map(bulan => `
                                        <td>
                                            <input type="text" style="width:120px" class="form-control angka-format"
                                                name="ditangani[${sub}][${bulan}]" />
                                        </td>`).join('')}
                    </tr>
                    <tr>
                        <td>Diselesaikan</td>
                        ${bulanList.map(bulan => `
                                        <td>
                                            <input type="text" style="width:120px" class="form-control angka-format"
                                                name="diselesaikan[${sub}][${bulan}]" />
                                        </td>`).join('')}
                    </tr>
                </tbody>
            </table>
        </div>
    `;

                            indikatorSection.append(table);
                        });


                        // Ambil data pengukuran dari DB
                        $.ajax({
                            url: '/get-pengukuran/' + indikator.id,
                            method: 'GET',
                            success: function(pengukuranData) {
                                const bulanIndex = [
                                    '', 'JANUARI', 'FEBRUARI',
                                    'MARET',
                                    'APRIL', 'MEI', 'JUNI',
                                    'JULI', 'AGUSTUS', 'SEPTEMBER',
                                    'OKTOBER', 'NOVEMBER',
                                    'DESEMBER'
                                ];

                                pengukuranData.forEach(item => {
                                    let bulanNama = bulanIndex[
                                        item.bulan];

                                    // Tampilkan Ditangani
                                    let d =
                                        `input[name="ditangani[${item.sub_indikator}][${bulanNama}]"]`;
                                    if ($(d).length && item
                                        .ditangani !== null) {
                                        $(d).val(formatRibuan(
                                            item
                                            .ditangani
                                            .toString()
                                        ));
                                    }

                                    // Tampilkan Diselesaikan
                                    let s =
                                        `input[name="diselesaikan[${item.sub_indikator}][${bulanNama}]"]`;
                                    if ($(s).length && item
                                        .diselesaikan !== null
                                    ) {
                                        $(s).val(formatRibuan(
                                            item
                                            .diselesaikan
                                            .toString()
                                        ));
                                    }

                                    // Tampilkan Sisa Tahun Lalu hanya untuk bulan Januari
                                    if (item.bulan === 1 && item
                                        .sisa_tahun_lalu !==
                                        null) {
                                        let sisaInput = $(
                                            `input[name="sisa_tahun_lalu[${item.sub_indikator}]"]`
                                        );
                                        if (sisaInput.length) {
                                            sisaInput.val(
                                                formatRibuan(
                                                    item
                                                    .sisa_tahun_lalu
                                                    .toString()
                                                ));
                                        }
                                    }
                                });
                            }

                        });
                    });
                },
                error: function() {
                    $('#indikator-section').html('<p>Gagal memuat data indikator.</p>');
                }
            });
        });
    });
</script>

@endpush

@endsection