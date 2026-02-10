@extends('layouts.app')

@section('title', 'Kelola Data')

@section('content')
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>Kelola Data</b></h2>
                    </center>
                </div>
                <div class="card-body">

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs" id="kelolaDataTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="data-bidang-tab" data-bs-toggle="tab" href="#data-bidang"
                                role="tab" aria-controls="data-bidang" aria-selected="true">Data Bidang</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="data-saspro-tab" data-bs-toggle="tab" href="#data-saspro" role="tab"
                                aria-controls="data-saspro" aria-selected="false">Data Saspro</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="data-indikator-tab" data-bs-toggle="tab" href="#data-indikator"
                                role="tab" aria-controls="data-indikator" aria-selected="false">Data Indikator</a>
                        </li>
                    </ul>


                    <br>
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <!-- Tabs Content -->
                    <div class="tab-content" id="kelolaDataTabContent">
                        <!-- Data Bidang -->
                        <div class="tab-pane fade show active" id="data-bidang" role="tabpanel"
                            aria-labelledby="data-bidang-tab">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center"
                                    style="background-color: #ffcc00; color: black;">
                                    <b>Input Data Bidang</b>
                                    <a data-bs-toggle="collapse" href="#collapseBidang" role="button" aria-expanded="false"
                                        aria-controls="collapseBidang">
                                        <i class="bi bi-chevron-down text-white" id="iconCollapseBidang"></i>
                                        <!-- Ikon Bootstrap -->
                                    </a>
                                </div>
                                <div class="collapse hide" id="collapseBidang">
                                    <div class="card-body">
                                        {{-- Form Input/Create/Update --}}
                                        <form action="{{ route('bidang.storeOrUpdateBidang') }}" method="POST"
                                            class="mb-4">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $bidang->id ?? '' }}">

                                            <div class="mb-3">
                                                <label for="bidang_nama" class="form-label">Nama Bidang</label>
                                                <input type="text" class="form-control" id="bidang_nama"
                                                    name="bidang_nama" value="{{ $bidang->bidang_nama ?? '' }}"
                                                    placeholder="Masukkan nama bidang" required>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="bidang_level" class="form-label">Bidang Level</label>
                                                    <input type="number" class="form-control" id="bidang_level"
                                                        name="bidang_level" value="{{ $bidang->bidang_level ?? '' }}"
                                                        placeholder="Masukkan level bidang" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="bidang_lokasi" class="form-label">Bidang Lokasi</label>
                                                    <select class="form-control" id="bidang_lokasi" name="bidang_lokasi"
                                                        required>
                                                        <option value="1"
                                                            {{ isset($bidang->bidang_lokasi) && $bidang->bidang_lokasi == 1 ? 'selected' : '' }}>
                                                            Pusat</option>
                                                        <option value="2"
                                                            {{ isset($bidang->bidang_lokasi) && $bidang->bidang_lokasi == 2 ? 'selected' : '' }}>
                                                            Kejati</option>
                                                        <option value="3"
                                                            {{ isset($bidang->bidang_lokasi) && $bidang->bidang_lokasi == 3 ? 'selected' : '' }}>
                                                            Kejari</option>
                                                        <option value="4"
                                                            {{ isset($bidang->bidang_lokasi) && $bidang->bidang_lokasi == 4 ? 'selected' : '' }}>
                                                            Cabjari</option>
                                                    </select>
                                                </div>

                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="rumpun" class="form-label">Rumpun</label>
                                                    <input type="number" class="form-control" id="rumpun" name="rumpun"
                                                        value="{{ $bidang->rumpun ?? '' }}" placeholder="Masukkan rumpun"
                                                        required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="hide" class="form-label">Hide</label>
                                                    <select class="form-control" id="hide" name="hide" required>
                                                        <option value="0"
                                                            {{ isset($bidang->hide) && $bidang->hide == 0 ? 'selected' : '' }}>
                                                            Tampil</option>
                                                        <option value="1"
                                                            {{ isset($bidang->hide) && $bidang->hide == 1 ? 'selected' : '' }}>
                                                            Sembunyikan</option>
                                                    </select>
                                                </div>

                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                {{ isset($bidang) ? 'Update' : 'Simpan' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <center>
                                <h4><b>Data Bidang</b></h4>
                            </center>

                            {{-- Tabel Data Bidang --}}
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Bidang</th>
                                        <th>Level</th>
                                        <th>Lokasi</th>
                                        <th>Rumpun</th>
                                        <th>Hide</th>
                                        <th style="width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bidangs as $index => $data)
                                        <tr>
                                            <td>{{ $loop->iteration + ($bidangs->currentPage() - 1) * $bidangs->perPage() }}
                                            </td>
                                            <td>{{ $data->bidang_nama }}</td>
                                            <td>{{ $data->bidang_level }}</td>
                                            <td>{{ $data->bidang_lokasi }}</td>
                                            <td>{{ $data->rumpun }}</td>
                                            <td>{{ $data->hide }}</td>
                                            <td>
                                                <button class="btn btn-warning btn-sm edit-button"
                                                    data-id="{{ $data->id }}" data-nama="{{ $data->bidang_nama }}"
                                                    data-level="{{ $data->bidang_level }}"
                                                    data-lokasi="{{ $data->bidang_lokasi }}"
                                                    data-rumpun="{{ $data->rumpun }}" data-hide="{{ $data->hide }}">
                                                    Edit
                                                </button>
                                                <form action="{{ route('bidang.destroy', $data->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {{-- Tambahkan Pagination --}}
                            <div id="bidang-pagination" class="d-flex justify-content-center">
                                {{ $bidangs->links() }}
                            </div>



                        </div>
                        <!-- Data Saspro -->
                        <div class="tab-pane fade" id="data-saspro" role="tabpanel" aria-labelledby="data-saspro-tab">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center"
                                    style="background-color: #ffcc00; color: black;">
                                    <b>Input Data Saspro</b>
                                    <a data-bs-toggle="collapse" href="#collapseSaspro" role="button"
                                        aria-expanded="false" aria-controls="collapseSaspro">
                                        <i class="bi bi-chevron-down text-white" id="iconCollapseSaspro"></i>
                                        <!-- Ikon Bootstrap -->
                                    </a>
                                </div>
                                <div class="collapse hide" id="collapseSaspro">
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('saspro.store') }}">
                                            @csrf

                                            <!-- Link -->
                                            {{-- <div class="form-group">
                                                <label for="link">Bidang</label>
                                                <input type="number" class="form-control" id="link" name="link"
                                                    placeholder="Masukkan Link" required>
                                            </div> --}}
                                            <!-- Bidang -->
                                            <div class="form-group">
                                                <label for="link">Bidang</label>
                                                <select class="form-control" id="link" name="link" required>
                                                    <option value="">Pilih Bidang</option>
                                                    @foreach ($bidangall as $bidang)
                                                        <option value="{{ $bidang->id }}"
                                                            data-rumpun="{{ $bidang->rumpun }}">
                                                            {{ $bidang->bidang_nama }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Nama Saspro -->
                                            <div class="form-group">
                                                <label for="saspro_nama">Nama Saspro</label>
                                                <input type="text" class="form-control" id="saspro_nama"
                                                    name="saspro_nama" placeholder="Masukkan Nama Saspro" required>
                                            </div>

                                            <!-- Penjelasan Saspro -->
                                            <div class="form-group">
                                                <label for="penjelasan_saspro">Penjelasan Saspro</label>
                                                <textarea class="form-control" id="penjelasan_saspro" name="penjelasan_saspro" rows="3"
                                                    placeholder="Masukkan Penjelasan Saspro" required></textarea>
                                            </div>

                                            <div class="row">
                                                <!-- Tahun -->
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="tahun">Tahun</label>
                                                        <input type="text" class="form-control" id="tahun"
                                                            name="tahun" placeholder="Masukkan Tahun" required>
                                                    </div>
                                                </div>

                                                <!-- Hide -->
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="hide">Hide</label>
                                                        <select class="form-control" id="hide" name="hide"
                                                            required>
                                                            <option value="0">Tampil</option>
                                                            <option value="1">Sembunyikan</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <br>
                                            <!-- Submit Button -->
                                            <button type="submit" class="btn btn-success">Submit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <br>
                            <center>
                                <h4><b>Data Saspro</b></h4>
                            </center>

                            <!-- Tabel Data Saspro -->
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Lingkup</th>
                                        <th>Nama Saspro</th>
                                        <th>Penjelasan Saspro</th>
                                        <th>tahun</th>
                                        <th>hide</th>
                                        <th style="width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="saspro-table-body">
                                    @foreach ($saspros as $key => $saspro)
                                        <tr>
                                            <td>{{ ($saspros->currentPage() - 1) * $saspros->perPage() + $loop->iteration }}
                                            </td>
                                            <td>{{ $saspro->bidang->bidang_nama ?? '-' }}</td>

                                            <td>{{ $saspro->saspro_nama }}</td>
                                            <td>{{ $saspro->saspro_penjelasan }}</td>
                                            <td>{{ $saspro->tahun }}</td>
                                            <td>{{ $saspro->hide }}</td>
                                            <td>
                                                <!-- Tombol Edit -->
                                                <button class="btn btn-warning btn-sm edit-saspro-button"
                                                    data-bs-toggle="modal" data-bs-target="#editSasproModal"
                                                    data-id="{{ $saspro->id }}" data-rumpun="{{ $saspro->link }}"
                                                    data-nama="{{ $saspro->saspro_nama }}"
                                                    data-penjelasan="{{ $saspro->saspro_penjelasan }}"
                                                    data-tahun="{{ $saspro->tahun }}" data-hide="{{ $saspro->hide }}">
                                                    Edit
                                                </button>

                                                <!-- Tombol Hapus -->
                                                <form action="{{ route('saspro.destroy', $saspro->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div id="saspro-pagination" class="d-flex justify-content-center">
                                {{ $saspros->links() }}
                            </div>
                        </div>

                        <!-- Data Indikator -->
                        <div class="tab-pane fade" id="data-indikator" role="tabpanel"
                            aria-labelledby="data-indikator-tab">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center"
                                    style="background-color: #ffcc00; color: black;">
                                    <b>Input Data Indikator</b>
                                    <a data-bs-toggle="collapse" href="#collapseIndikator" role="button"
                                        aria-expanded="false" aria-controls="collapseIndikator">
                                        <i class="bi bi-chevron-down text-white" id="iconCollapseIndikator"></i>
                                        <!-- Ikon Bootstrap -->
                                    </a>
                                </div>
                                <div class="collapse hide" id="collapseIndikator">
                                    <div class="card-body">
                                        <form method="POST" action="{{ route('indikator.store') }}">
                                            @csrf

                                            <!-- Bidang -->
                                            <div class="form-group">
                                                <label>Bidang</label>
                                                <select class="form-control" id="bidang" name="bidang" required>
                                                    <option value="">Pilih Bidang</option>
                                                    @foreach ($bidangall as $bidang)
                                                        <option value="{{ $bidang->id }}"
                                                            data-rumpun="{{ $bidang->rumpun }}">
                                                            {{ $bidang->bidang_nama }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <!-- Link (Hidden Input) -->
                                            {{-- <div class="form-group">
                                                <label for="link">Link</label>
                                                <input type="number" class="form-control" id="link" name="link"
                                                    required>
                                            </div> --}}

                                            <!-- Lingkup -->
                                            <div class="form-group">
                                                <label for="lingkup"> Lingkup</label>
                                                <select class="form-control" id="lingkup" name="lingkup" required>
                                                    <option value="0">Semua Satker</option>
                                                    <option value="1">Pusat</option>
                                                    <option value="2">Kejati</option>
                                                    <option value="3">Kejari</option>
                                                    <option value="4">Cabjari</option>
                                                    <option value="5">Kejati, Kejari</option>
                                                    <option value="6">Kejari, Cabjari</option>
                                                    <option value="7">Kejati, Kejari, Cabjari</option>
                                                </select>
                                            </div>
                                            <!-- Sasaran Program -->
                                            <div class="form-group">
                                                <label for="id_saspro">Sasaran Program</label>
                                                <select class="form-control" id="id_saspro" name="id_saspro" required>
                                                    <option value="">Pilih Sasaran Program</option>
                                                    @foreach ($saspro1 as $saspro)
                                                        <option value="{{ $saspro->id }}">
                                                            {{ $saspro->saspro_nama }} ({{ $saspro->tahun }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <!-- Indikator Nama -->
                                            <div class="form-group">
                                                <label for="indikator_nama">Indikator Nama</label>
                                                <input type="text" class="form-control" id="indikator_nama"
                                                    name="indikator_nama" required>
                                            </div>

                                            <!-- Indikator Pembilang -->
                                            <div class="form-group">
                                                <label for="indikator_pembilang">Indikator Pembilang</label>
                                                <input type="text" class="form-control" id="indikator_pembilang"
                                                    name="indikator_pembilang" required>
                                            </div>

                                            <!-- Indikator Penyebut -->
                                            <div class="form-group">
                                                <label for="indikator_penyebut">Indikator Penyebut</label>
                                                <input type="text" class="form-control" id="indikator_penyebut"
                                                    name="indikator_penyebut" required>
                                            </div>

                                            <!-- Indikator Penjelasan -->
                                            <div class="form-group">
                                                <label for="indikator_penjelasan">Indikator Penjelasan</label>
                                                <textarea class="form-control" id="indikator_penjelasan" name="indikator_penjelasan" rows="3" required></textarea>
                                            </div>

                                            <!-- Sub Indikator -->
                                            <div class="form-group">
                                                <label for="sub_indikator">Sub Indikator</label>
                                                <input type="text" class="form-control" name="sub_indikator"
                                                    id="sub_indikator"
                                                    placeholder="Penyelamatan Aset Negara,Pemulihan Aset Negara">
                                                <p style="color: red">"Pisahkan dengan koma jika lebih dari satu"</p>
                                            </div>

                                            <!-- Indikator Penghitungan -->
                                            <div class="form-group">
                                                <label for="indikator_penghitungan">Indikator Penghitungan</label>
                                                <input type="text" class="form-control" name="indikator_penghitungan"
                                                    id="indikator_penghitungan" placeholder="Ditangani, Diselesaikan">
                                                <p style="color: red">*Pisahkan dengan koma jika lebih dari
                                                    satu<br>*default jika kosong: "ditangani, diselesaikan"</p>
                                            </div>

                                            <!-- Tahun -->
                                            <div class="form-group">
                                                <label for="tahun">Tahun</label>
                                                <input type="text" class="form-control" id="tahun" name="tahun"
                                                    required>
                                            </div>

                                            <!-- Tren -->
                                            <div class="form-group">
                                                <label for="tren">Tren</label>
                                                <select class="form-select" id="tren" name="tren" required>
                                                    <option value="">Pilih</option>
                                                    <option value="Naik">Naik</option>
                                                    <option value="Turun">Turun</option>
                                                </select>
                                            </div>
                                            <br>
                                            <!-- Submit Button -->
                                            <button type="submit" class="btn btn-success">Submit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <center>
                                <h4><b>Data Indikator</b></h4>
                            </center>

                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Bidang</th>
                                        <th>Lingkup</th>
                                        <th>Sasaran Program</th>
                                        <th>Nama Indikator</th>
                                        <th>Indikator Pembilang</th>
                                        <th>Indikator Penyebut</th>
                                        <th>Indikator Penjelasan</th>
                                        <th>Sub Indikator</th>
                                        <th>Indikator Penghitungan</th>
                                        <th>Tahun</th>
                                        <th>Tren</th>
                                        <th style="width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($indikators as $index => $indikator)
                                        <tr>
                                            <td>{{ ($indikators->currentPage() - 1) * $indikators->perPage() + $loop->iteration }}
                                            </td>
                                            <td>{{ $indikator->bidangByLink->bidang_nama ?? '-' }}</td>
                                            {{-- <td>{{ $indikator->link }}</td> --}}
                                            @php
                                                $mapping = [
                                                    0 => 'Semua Satker',
                                                    1 => 'Pusat',
                                                    2 => 'Kejati',
                                                    3 => 'Kejari',
                                                    4 => 'Cabjari',
                                                    5 => 'Kejati, Kejari',
                                                    6 => 'Kejari, Cabjari',
                                                    7 => 'Kejati, Kejari, Cabjari',
                                                ];

                                                $lingkup = $indikator->lingkup;

                                                // Jika lingkup berupa angka tunggal (0–4), tampilkan langsung
                                                // Tapi jika 5 atau 6, kita konversi ke bentuk teks dengan pemisah koma
                                                $lingkupLabel = $mapping[$lingkup] ?? 'Tidak diketahui';
                                            @endphp

                                            <td>{{ $lingkupLabel }}</td>
                                            <td>{{ $indikator->saspro->saspro_nama ?? '-' }}</td>
                                            <td>{{ $indikator->indikator_nama }}</td>
                                            <td>{{ $indikator->indikator_pembilang }}</td>
                                            <td>{{ $indikator->indikator_penyebut }}</td>
                                            <td>{{ $indikator->indikator_penjelasan }}</td>
                                            <td>{{ $indikator->sub_indikator }}</td>
                                            <td>
                                                {{ $indikator->indikator_penghitungan ?: 'Ditangani, Diselesaikan' }}
                                            </td>

                                            {{-- <td>{{ $indikator->bidangById->rumpun ?? '-' }}</td> --}}
                                            <td>{{ $indikator->tahun }}</td>
                                            <td>{{ $indikator->tren }}</td>
                                            <td>
                                                <button class="btn btn-warning btn-sm edit-indikator"
                                                    data-id="{{ $indikator->id }}"
                                                    data-bidang="{{ $indikator->id_bidang }}"
                                                    data-lingkup="{{ $indikator->lingkup }}"
                                                    data-nama="{{ $indikator->indikator_nama }}"
                                                    data-pembilang="{{ $indikator->indikator_pembilang }}"
                                                    data-penyebut="{{ $indikator->indikator_penyebut }}"
                                                    data-penjelasan="{{ $indikator->indikator_penjelasan }}"
                                                    data-sub-indikator="{{ $indikator->sub_indikator }}"
                                                    data-penghitungan="{{ $indikator->indikator_penghitungan }}"
                                                    data-tahun1="{{ $indikator->tahun }}"
                                                    data-tren="{{ $indikator->tren }}">
                                                    Edit
                                                </button>
                                                <form action="{{ route('indikator.delete', $indikator->id) }}"
                                                    method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                                                </form>

                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Tombol Navigasi Paginasi -->
                            <div class="d-flex justify-content-center">
                                {{ $indikators->links() }}
                            </div>

                        </div>


                    </div>
                </div>
            </div>

            {{-- Modal bidang --}}
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true"
                data-bs-backdrop="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('bidang.storeOrUpdateBidang') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" id="modal_id">

                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Edit Data Bidang</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="modal_nama_bidang" class="form-label">Nama Bidang</label>
                                    <input type="text" class="form-control" id="modal_nama_bidang" name="bidang_nama"
                                        required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_bidang_level" class="form-label">Bidang
                                            Level</label>
                                        <input type="number" class="form-control" id="modal_bidang_level"
                                            name="bidang_level" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_bidang_lokasi" class="form-label">Bidang
                                            Lokasi</label>
                                        <input type="number" class="form-control" id="modal_bidang_lokasi"
                                            name="bidang_lokasi" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_rumpun" class="form-label">Rumpun</label>
                                        <input type="number" class="form-control" id="modal_rumpun" name="rumpun"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_hide" class="form-label">Hide</label>
                                        <input type="number" class="form-control" id="modal_hide" name="hide"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                {{-- <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Tutup</button> --}}
                                <button type="submit" class="btn btn-success">Simpan Perubahan</button>

                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Latar belakang semi-transparan -->
            <div class="custom-opacity d-none" id="modalBackground"></div>

            <!-- Modal Edit Saspro -->
            <div class="modal fade" id="editSasproModal" tabindex="-1" role="dialog"
                aria-labelledby="editSasproModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editSasproModalLabel">Edit Data Saspro</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>

                        </div>
                        <div class="modal-body">
                            <form id="editSasproForm" method="POST">
                                @csrf
                                <input type="hidden" name="_method" value="POST">
                                <input type="hidden" id="edit_saspro_id" name="id">

                                <!-- Link -->
                                <div class="form-group">
                                    <label for="edit_link">Link</label>
                                    <input type="text" class="form-control" id="edit_link" name="link" required>
                                </div>

                                <!-- Nama Saspro -->
                                <div class="form-group">
                                    <label for="edit_saspro_nama">Nama Saspro</label>
                                    <input type="text" class="form-control" id="edit_saspro_nama" name="saspro_nama"
                                        required>
                                </div>

                                <!-- Penjelasan Saspro -->
                                <div class="form-group">
                                    <label for="edit_penjelasan_saspro">Penjelasan Saspro</label>
                                    <textarea class="form-control" id="edit_penjelasan_saspro" name="penjelasan_saspro" rows="3" required></textarea>
                                </div>

                                <!-- Tahun -->
                                <div class="form-group">
                                    <label for="edit_tahun">Tahun</label>
                                    <input type="text" class="form-control" id="edit_tahun" name="tahun" required>
                                </div>

                                <!-- Hide -->
                                <div class="form-group">
                                    <label for="edit_hide">Hide</label>
                                    <input type="text" class="form-control" id="edit_hide" name="hide" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            {{-- <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">Batal</button> --}}
                            <button type="submit" class="btn btn-success" id="saveEditSaspro">Simpan
                                Perubahan</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Edit Indikator -->
            <div class="modal fade" id="editIndikatorModal" tabindex="-1" aria-labelledby="editIndikatorLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editIndikatorLabel">Edit Data Indikator</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editIndikatorForm" method="POST" action="">
                                @csrf
                                <input type="hidden" name="_method" value="POST">

                                <!-- ID Indikator (Hidden) -->
                                <input type="hidden" id="indikator_id" name="indikator_id">

                                <!-- Bidang -->
                                <div class="form-group">
                                    <label for="edit_bidang">Bidang</label>
                                    <select class="form-control" id="edit_bidang" name="bidang" required>
                                        <option value="">Pilih Bidang</option>
                                        @foreach ($bidangall as $bidang)
                                            <option value="{{ $bidang->id }}" data-rumpun="{{ $bidang->rumpun }}">
                                                {{ $bidang->bidang_nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Lingkup -->
                                <div class="form-group">
                                    <label for="edit_lingkup">Lingkup</label>
                                    <select class="form-control" id="edit_lingkup" name="lingkup" required>
                                        <option value="">Pilih Lingkup</option>
                                        <option value="0">Semua Satker</option>
                                        <option value="1">Pusat</option>
                                        <option value="2">Kejati</option>
                                        <option value="3">Kejari</option>
                                        <option value="4">Cabjari</option>
                                        <option value="5">Kejati, Kejari</option>
                                        <option value="6">Kejari, Cabjari</option>
                                        <option value="7">Kejati, Kejari, Cabjari</option>
                                    </select>
                                </div>
                    
                                <!-- Sasaran Program -->
                                <div class="form-group mb-2">
                                    <label for="edit_saspro">Sasaran Program</label>
                                    <select class="form-control" id="edit_saspro" name="id_saspro" required>
                                        <option value="">Pilih Sasaran Program</option>
                                        @foreach ($saspro1 as $saspro)
                                            <option value="{{ $saspro->id }}">{{ $saspro->saspro_nama }}  ({{ $saspro->tahun }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Indikator Nama -->
                                <div class="form-group">
                                    <label for="edit_indikator_nama">Indikator Nama</label>
                                    <input type="text" class="form-control" id="edit_indikator_nama"
                                        name="indikator_nama" required>
                                </div>

                                <!-- Indikator Pembilang -->
                                <div class="form-group">
                                    <label for="edit_indikator_pembilang">Indikator Pembilang</label>
                                    <input type="text" class="form-control" id="edit_indikator_pembilang"
                                        name="indikator_pembilang" required>
                                </div>

                                <!-- Indikator Penyebut -->
                                <div class="form-group">
                                    <label for="edit_indikator_penyebut">Indikator Penyebut</label>
                                    <input type="text" class="form-control" id="edit_indikator_penyebut"
                                        name="indikator_penyebut" required>
                                </div>

                                <!-- Indikator Penjelasan -->
                                <div class="form-group">
                                    <label for="edit_indikator_penjelasan">Indikator Penjelasan</label>
                                    <textarea class="form-control" id="edit_indikator_penjelasan" name="indikator_penjelasan" rows="3" required></textarea>
                                </div>

                                <!-- Sub Indikator  -->
                                <div class="form-group">
                                    <label for="edit_sub_indikator">Sub Indikator</label>
                                    <input type="text" class="form-control" name="sub_indikator"
                                        id="edit_sub_indikator">
                                    <p style="color: red">"Pisahkan dengan koma jika lebih dari satu"</p>
                                </div>

                                <!-- Indikator Penghitungan -->
                                <div class="form-group">
                                    <label for="edit_indikator_penghitungan">Indikator Penghitungan</label>
                                    <input type="text" class="form-control" name="indikator_penghitungan"
                                        id="edit_indikator_penghitungan" rows="3">
                                    <p style="color: red">"Pisahkan dengan koma jika lebih dari satu"<br>"Maks 1 koma"</p>
                                </div>

                                <!-- Tahun -->
                                <div class="form-group">
                                    <label for="edit_tahun1">Tahun</label>
                                    <input type="text" class="form-control" id="edit_tahun1" name="tahun1" required>
                                </div>

                                <!-- Tren -->
                                <div class="form-group">
                                    <label for="edit_tren">Tren</label>
                                    <select class="form-select" id="edit_tren" name="tren" required>
                                        <option value="">Pilih</option>
                                        <option value="Naik">Naik</option>
                                        <option value="Turun">Turun</option>
                                    </select>
                                </div>

                                <!-- Submit Button -->
                                <div class="modal-footer">
                                    {{-- <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Batal</button> --}}
                                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                                </div>


                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

{{-- script modal dan tombol --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModalEl = document.getElementById('editModal');
        const editSasproModalEl = document.getElementById('editSasproModal');

        const editModal = new bootstrap.Modal(editModalEl);
        const editSasproModal = new bootstrap.Modal(editSasproModalEl);

        // Event delegation untuk tombol Edit Bidang
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('edit-button')) {
                const button = event.target;
                document.getElementById('modal_id').value = button.dataset.id;
                document.getElementById('modal_nama_bidang').value = button.dataset.nama;
                document.getElementById('modal_bidang_level').value = button.dataset.level;
                document.getElementById('modal_bidang_lokasi').value = button.dataset.lokasi;
                document.getElementById('modal_rumpun').value = button.dataset.rumpun;
                document.getElementById('modal_hide').value = button.dataset.hide;
                editModal.show();
            }
        });

        // Event delegation untuk tombol Edit Saspro
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('edit-saspro-button')) {
                const button = event.target;
                let sasproId = button.dataset.id;

                document.getElementById('edit_saspro_id').value = sasproId;
                document.getElementById('edit_link').value = button.dataset.rumpun;
                document.getElementById('edit_saspro_nama').value = button.dataset.nama;
                document.getElementById('edit_penjelasan_saspro').value = button.dataset.penjelasan;
                document.getElementById('edit_tahun').value = button.dataset.tahun;
                document.getElementById('edit_hide').value = button.dataset.hide;

                // Set action form dengan metode POST
                document.getElementById('editSasproForm').action = `/keloladata/update/${sasproId}`;

                editSasproModal.show();
            }
        });

        // Menghilangkan overlay yang menghalangi tombol modal Saspro
        editSasproModalEl.addEventListener('shown.bs.modal', function() {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        });

        // Event delegation untuk tombol tutup modal (Close, Batal, Simpan)
        document.body.addEventListener('click', function(event) {
            if (event.target.dataset.dismiss === 'modal') {
                editSasproModal.hide();
                editModal.hide();
            }
        });

        // Kirim form dan tutup modal saat tombol Simpan di modal Saspro ditekan
        document.getElementById('saveEditSaspro').addEventListener('click', function() {
            document.getElementById('editSasproForm').submit();
            editSasproModal.hide();
        });

        // AJAX Paginasi Saspro
        $(document).on('click', '#saspro-pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');

            $.get(url, function(data) {
                let newTableBody = $(data).find('#saspro-table-body').html();
                let newPagination = $(data).find('#saspro-pagination').html();

                $('#saspro-table-body').html(newTableBody);
                $('#saspro-pagination').html(newPagination);
            });
        });
    });

    // document.addEventListener("DOMContentLoaded", function() {
    //     let editModal = document.getElementById("editIndikatorModal");
    //     let editForm = document.getElementById("editIndikatorForm");

    //     document.querySelectorAll(".btn-edit-indikator").forEach(button => {
    //         button.addEventListener("click", function() {
    //             let indikatorId = this.getAttribute("data-id");
    //             let bidangId = this.getAttribute("data-bidang");
    //             let lingkup = this.getAttribute("data-lingkup");
    //             let indikatorNama = this.getAttribute("data-indikator-nama");
    //             let indikatorPembilang = this.getAttribute("data-indikator-pembilang");
    //             let indikatorPenyebut = this.getAttribute("data-indikator-penyebut");
    //             let indikatorPenjelasan = this.getAttribute("data-indikator-penjelasan");
    //             let subIndikator = this.getAttribute("data-sub-indikator");

    //             // Set data ke dalam form modal
    //             document.getElementById("indikator_id").value = indikatorId;
    //             document.getElementById("edit_bidang").value = bidangId;
    //             document.getElementById("edit_lingkup").value = lingkup;
    //             document.getElementById("edit_indikator_nama").value = indikatorNama;
    //             document.getElementById("edit_indikator_pembilang").value = indikatorPembilang;
    //             document.getElementById("edit_indikator_penyebut").value = indikatorPenyebut;
    //             document.getElementById("edit_indikator_penjelasan").value = indikatorPenjelasan;
    //             document.getElementById("edit_sub_indikator").value = subIndikator;

    //             // Set action form untuk update
    //             editForm.action = `/indikator/update/${indikatorId}`;

    //             // Tampilkan modal
    //             let modalInstance = new bootstrap.Modal(editModal);
    //             modalInstance.show();
    //         });
    //     });
    // });
</script>

<script>
    //modal edit indikator
    document.addEventListener("DOMContentLoaded", function() {
        let editModal = document.getElementById("editIndikatorModal");
        let editForm = document.getElementById("editIndikatorForm");

        // Event untuk tombol edit indikator
        document.querySelectorAll(".edit-indikator").forEach(button => {
            button.addEventListener("click", function() {
                let indikatorId = this.getAttribute("data-id");
                let bidangId = this.getAttribute("data-bidang");
                let link = this.getAttribute("data-link");
                let lingkup = this.getAttribute("data-lingkup");
                let indikatorNama = this.getAttribute("data-nama");
                let indikatorPembilang = this.getAttribute("data-pembilang");
                let indikatorPenyebut = this.getAttribute("data-penyebut");
                let indikatorPenjelasan = this.getAttribute("data-penjelasan");
                let subIndikator = this.getAttribute("data-sub-indikator");
                let indikatorPenghitungan = this.getAttribute("data-penghitungan");
                let tahun1 = this.getAttribute("data-tahun1");
                let tren = this.getAttribute("data-tren");
                console.log(tahun1);
                // Isi form modal dengan data yang diambil dari tombol edit
                document.getElementById("indikator_id").value = indikatorId;
                // document.getElementById("edit_link").value = link;
                document.getElementById('edit_lingkup').value = button.dataset.lingkup;
                document.getElementById("edit_indikator_nama").value = indikatorNama;
                document.getElementById("edit_indikator_pembilang").value = indikatorPembilang;
                document.getElementById("edit_indikator_penyebut").value = indikatorPenyebut;
                document.getElementById("edit_indikator_penjelasan").value =
                    indikatorPenjelasan;
                document.getElementById("edit_sub_indikator").value = subIndikator;
                document.getElementById("edit_indikator_penghitungan").value =
                    indikatorPenghitungan;
                document.getElementById("edit_tahun").value = tahun1;
                document.getElementById("edit_tren").value = tren;

                // Set dropdown bidang dengan bidang yang sesuai
                let bidangSelect = document.getElementById("edit_bidang");
                bidangSelect.value = bidangId;

                // Pastikan option terpilih jika nilainya tidak ditemukan secara langsung
                let found = false;
                for (let option of bidangSelect.options) {
                    if (option.value == bidangId) {
                        option.selected = true;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    bidangSelect.value = "";
                }

                // Set form action untuk update
                editForm.action = `/indikator/update/${indikatorId}`;

                // Tampilkan modal
                let modalInstance = new bootstrap.Modal(editModal);
                modalInstance.show();
            });
        });
    });
</script>

{{-- script tab navigasi --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('#kelolaDataTabs a');

        // **Cek apakah ada tab aktif di localStorage, jika ada, buka tab tersebut**
        let activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            let activeTabElement = document.querySelector(`#kelolaDataTabs a[href="${activeTab}"]`);
            if (activeTabElement) {
                new bootstrap.Tab(activeTabElement).show();
            }
        }

        // **Simpan tab aktif ke localStorage saat diklik**
        tabs.forEach(tab => {
            tab.addEventListener('click', function(event) {
                let tabId = this.getAttribute('href'); // Simpan href tab yang diklik
                localStorage.setItem('activeTab', tabId);
            });
        });

        // **Paginasi AJAX Saspro agar tetap di tab yang benar**
        $(document).on('click', '#saspro-pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');

            $.get(url, function(data) {
                $('#saspro-table-body').html($(data).find('#saspro-table-body').html());
                $('#saspro-pagination').html($(data).find('#saspro-pagination').html());
            });

            // **Pastikan tab Saspro tetap aktif**
            new bootstrap.Tab(document.querySelector('#data-saspro-tab')).show();
        });

        // **Paginasi AJAX Indikator agar tetap di tab yang benar**
        $(document).on('click', '#indikator-pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');

            $.get(url, function(data) {
                $('#indikator-table-body').html($(data).find('#indikator-table-body').html());
                $('#indikator-pagination').html($(data).find('#indikator-pagination').html());
            });

            // **Pastikan tab Indikator tetap aktif**
            new bootstrap.Tab(document.querySelector('#data-indikator-tab')).show();
        });

    });
</script>

<!-- JavaScript untuk Mengubah Ikon Collapse -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var collapseBidang = document.getElementById("collapseBidang");
        var iconCollapseBidang = document.getElementById("iconCollapseBidang");

        collapseBidang.addEventListener("shown.bs.collapse", function() {
            iconCollapseBidang.classList.remove("bi-chevron-down");
            iconCollapseBidang.classList.add("bi-chevron-up");
        });

        collapseBidang.addEventListener("hidden.bs.collapse", function() {
            iconCollapseBidang.classList.remove("bi-chevron-up");
            iconCollapseBidang.classList.add("bi-chevron-down");
        });
    });
</script>

<!-- JavaScript untuk Mengubah Ikon Collapse -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var collapseIndikator = document.getElementById("collapseIndikator");
        var iconCollapseIndikator = document.getElementById("iconCollapseIndikator");

        collapseIndikator.addEventListener("shown.bs.collapse", function() {
            iconCollapseIndikator.classList.remove("bi-chevron-down");
            iconCollapseIndikator.classList.add("bi-chevron-up");
        });

        collapseIndikator.addEventListener("hidden.bs.collapse", function() {
            iconCollapseIndikator.classList.remove("bi-chevron-up");
            iconCollapseIndikator.classList.add("bi-chevron-down");
        });
    });
</script>

{{-- script dropdown indikator --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ambil elemen dropdown bidang dan input link
        const bidangSelect = document.getElementById('bidang');
        const linkInput = document.getElementById('link');

        // Tambahkan event listener saat bidang dipilih
        bidangSelect.addEventListener('change', function() {
            const selectedOption = bidangSelect.options[bidangSelect.selectedIndex];
            const rumpunValue = selectedOption.getAttribute('data-rumpun');

            // Set nilai link berdasarkan rumpun dari bidang yang dipilih
            linkInput.value = rumpunValue ? rumpunValue : '';
        });
    });
</script>

{{-- script untuk link --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let bidangSelect = document.getElementById("bidang"); // Dropdown bidang
        let rumpunHidden = document.getElementById("rumpun_hidden"); // Input hidden

        bidangSelect.addEventListener("change", function() {
            let selectedOption = bidangSelect.options[bidangSelect
                .selectedIndex]; // Ambil opsi yang dipilih
            let rumpunValue = selectedOption.getAttribute(
                "data-rumpun"); // Ambil nilai rumpun dari atribut data-rumpun

            // Masukkan nilai rumpun ke input hidden
            rumpunHidden.value = rumpunValue || '';

            // Debugging (cek di console)
            console.log("Bidang Dipilih:", selectedOption.textContent);
            console.log("Rumpun Ditemukan:", rumpunValue);
        });
    });
</script>

<script>
    $(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<style>
    /* CSS untuk modal */
    body {
        overflow-x: hidden;
        /* Pastikan hanya scroll vertikal yang diperbolehkan */
    }

    body.modal-open {
        overflow: auto !important;
        padding-right: 0px !important;
        /* Mencegah pergeseran konten akibat scrollbar hilang */
    }

    /* Latar belakang dengan efek opacity */
    .custom-opacity {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        /* Warna latar belakang semi-transparan */
        z-index: 1040;
        /* Pastikan di atas konten halaman */
        pointer-events: none;
        /* Agar latar belakang tidak menghalangi interaksi dengan modal */
    }

    /* Pastikan modal berada di atas latar belakang dan konten lain */
    .modal {
        /* z-index: 1050; */
        background: none !important;
    }

    /* Pastikan modal Saspro selalu di atas */
    #editSasproModal {
        z-index: 1060 !important;
    }

    /* Perbaiki pointer-events agar modal bisa diklik */
    .custom-opacity {
        pointer-events: auto;
    }

    /* Hapus latar belakang transparan jika modal ditutup */
    .modal-backdrop {
        /* z-index: 1045 !important; */
        display: none !important;
    }
</style>
