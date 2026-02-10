@extends('layouts.app')

@section('title', 'Kep Tim SAKIP')

@section('content')
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                <center>
                    <h2><b>Keputusan</b></h2>
                </center></div>
                <div class="card-body">
                    <center>
                        <h2><b>Sinergi Continuous Improvement - AKIP Kejaksaan RI</b></h2>
                    </center><br>
                    <div class="text mb-4">
                        <center>
                            <h5><b>Keputusan Tim Pelaksana AKIP Tahun {{ $tahun }}</b></h5>
                            <p>Hai sobat adhyaksa, untuk memulai mengisikan pelaksanaan AKIP anda harus upload terlebih
                                dahulu Keputusan Tim Pelaksana AKIP. Anda pasti tahu bahwa komponen indikator dalam AKIP
                                adalah seluruh bidang atau bagian yang ada pada satker anda yang dapat menuangkan
                                perencanaan dan target sasaran yang hendak dilakukan. Klik upload file untuk memulai. File
                                yang diijinkan untuk diupload adalah file PDF. oleh karena proses upload ini tidak bisa
                                diulang (untuk mengubah anda harus mengirimkan surat secara berjenjang oleh karena kelalaian
                                anda) dan mulailah belajar untuk bertanggung jawab atas apa yang diupload. Sudah dicek dan
                                cek ulang terlebih dahulu. Salam Perubahan.
                                Risiko Kebijakan, Risiko Reputasi, Risiko Hukum, Risiko Keuangan, Risiko Operasional, Risiko
                                Pelaporan, Risiko Kepatuhan</p>
                    </div>
                    </center>

                    @if (session('success'))
                        <center><div class="alert alert-success">{{ session('success') }}</div></center>
                    @elseif (session('error'))
                        <center><div class="alert alert-danger">{{ session('error') }}</div></center>
                    @endif

                    @if (!$kep)
                        <!-- File Upload Form -->
                        <form action="{{ route('kep.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="nomor_surat" class="form-label">Nomor Surat:</label>
                                <input type="text" class="form-control form-control-sm" id="nomor_surat"
                                    name="nomor_surat" required>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_surat" class="form-label">Tanggal Surat (dd/mm/yyyy):</label>
                                <input type="text" class="form-control" id="tanggal_surat" name="tanggal_surat"
                                    placeholder="dd/mm/yyyy" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Surat Keputusan Tim SAKIP yang sudah di TTD KASATKER:</label>
                                <input type="file" class="form-control" id="file" name="file" accept=".pdf"
                                    required>
                                    <div class="form-text text-left">Maximum size: 2MB</div>
                            </div>

                            <button type="submit" class="btn btn-primary">Upload</button>
                           
                        </form><br>


                        <center>
                            <p style="color: red;">TIDAK ADA METODE PERBAIKAN UPLOAD. PASTIKAN YANG DIUPLOAD SUDAH BENAR
                                FILE DAN ISINYA </p>
                            <p>Spirit of responsibility - Semangat bertanggungjawab </p>
                            <b>
                                <p>#beraniuntukberubah #beboldandmakechanges</p>
                            </b>
                        </center>
                    @else
                        <center>
                            <div class="alert alert-success">
                                <a href="{{ asset('uploads/kep/' . $idSatker . '_' . $tahun . '.pdf') }}"
                                                                        target="_blank"
                                                                        style="text-decoration: none; color: inherit;">
                                                                         Anda sudah mengupload Surat Keputusan TIM SAKIP Tahun {{ $tahun }} untuk satker ini.
                                                                    </a>
                            </div>
                        </center>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
