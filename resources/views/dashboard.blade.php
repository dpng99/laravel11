@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Main Content -->
    <div class="content" id="content">
        <div class="container-fluid">
            {{-- <p class="lead">Overview of your account and activities.</p> --}}

            <!-- Dashboard Cards -->
            <div class="row">
                <!-- Card Pesan Masuk (1:1) -->

                <div class="col-md-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                            <center>
                                <h3><b>Pengumuman</b></h3>
                            </center>
                        </div>
                        <div class="card-body">
                            @foreach ($pengumuman as $item)
                                <div class="card shadow-sm mb-4">
                                    <div class="card-body">
                                        <p class="card-text" style="color: red;">
                                            <b>{{ $item->judul }}</b>
                                        </p>
                                        <p> {{ $item->isi }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @php
        $satkernama = session('satkernama', 'Nama Satker');
        $idSatker = session('id_satker', 'ID Satker');
        $levelSakip = session('id_sakip_level', 0);

    @endphp
@if ($levelSakip != 0)
                {{-- Chart --}}
                <div class="col-md-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                                <center>
                                    <h3><b>Kepatuhan</b></h3>
                                </center>
                            </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Keputusan Tim SAKIP -->
                                <!--<div class="col-md-12">-->
                                <!--    <div class="card shadow-sm mb-4"-->
                                <!--        style="background-color: {{ $keputusanTimSakipTerisi ? '#28a745' : '#dc3545' }}; color: white;">-->
                                <!--        <div class="card-body">-->
                                <!--            <center>-->
                                <!--                <h5 class="card-title"><b>Keputusan Tim SAKIP</b></h5>-->
                                <!--                <p class="card-text">-->
                                <!--                    {{ $keputusanTimSakipTerisi ? 'Keputusan Tim SAKIP sudah diupload' : 'Keputusan Tim SAKIP belum diupload' }}-->
                                <!--                </p>-->
                                <!--            </center>-->
                                <!--        </div>-->
                                <!--    </div>-->
                                <!--</div>-->

                                <!-- Renstra -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4"
                                        style="background-color: {{ $renstraTerisi ? '#28a745' : '#dc3545' }}; color: white;">
                                        <div class="card-body">
                                            <h5 class="card-title"><b>Pengisian Renstra</b></h5>
                                            <p class="card-text">
                                                {{ $renstraTerisi ? 'Pengisian Renstra sudah dilakukan' : 'Pengisian Renstra belum dilakukan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- IKU -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4"
                                        style="background-color: {{ $ikuTerisi ? '#28a745' : '#dc3545' }}; color: white;">
                                        <div class="card-body">
                                            <h5 class="card-title"><b>Pengisian IKU</b></h5>
                                            <p class="card-text">
                                                {{ $ikuTerisi ? 'Pengisian IKU sudah dilakukan' : 'Pengisian IKU belum dilakukan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Renja -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4"
                                        style="background-color: {{ $renjaTerisi ? '#28a745' : '#dc3545' }}; color: white;">
                                        <div class="card-body">
                                            <h5 class="card-title"><b>Pengisian Renja</b></h5>
                                            <p class="card-text">
                                                {{ $renjaTerisi ? 'Pengisian Renja sudah dilakukan' : 'Pengisian Renja belum dilakukan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- RKAKL -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4"
                                        style="background-color: {{ $rkaklTerisi ? '#28a745' : '#dc3545' }}; color: white;">
                                        <div class="card-body">
                                            <h5 class="card-title"><b>Pengisian RKAKL</b></h5>
                                            <p class="card-text">
                                                {{ $rkaklTerisi ? 'Pengisian RKAKL sudah dilakukan' : 'Pengisian RKAKL belum dilakukan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- DIPA -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4"
                                        style="background-color: {{ $dipaTerisi ? '#28a745' : '#dc3545' }}; color: white;">
                                        <div class="card-body">
                                            <h5 class="card-title"><b>Pengisian DIPA</b></h5>
                                            <p class="card-text">
                                                {{ $dipaTerisi ? 'Pengisian DIPA sudah dilakukan' : 'Pengisian DIPA belum dilakukan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rencana Aksi -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4"
                                        style="background-color: {{ $rencanaAksiTerisi ? '#28a745' : '#dc3545' }}; color: white;">
                                        <div class="card-body">
                                            <h5 class="card-title"><b>Pengisian Rencana Aksi</b></h5>
                                            <p class="card-text">
                                                {{ $rencanaAksiTerisi ? 'Pengisian Rencana Aksi sudah dilakukan' : 'Pengisian Rencana Aksi belum dilakukan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
@endif
                <!-- Card Sumber Aturan (1:3) -->

                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><b>Sumber Aturan</b></h5>
                            <p class="card-text">Lihat sumber aturan dan referensi hukum yang relevan.</p>
                            {{-- <p class="card-text"><b>Jumlah Aturan:</b> {{ $jumlahAturan }} Dokumen</p> --}}
                            <!-- Menampilkan jumlah aturan -->
                            <a href="{{ route('aturan') }}" class="btn btn-yellow">Lihat Sumber Aturan</a>
                        </div>
                    </div>
                </div>

                {{-- <!-- Card Sumber Literasi (1:3) -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><b>Sumber Literasi</b></h5>
                        <p class="card-text">Jelajahi sumber literasi dan referensi tambahan.</p>
                        <a href="{{ route('literasi') }}" class="btn btn-yellow">Lihat Sumber Literasi</a>
                    </div>
                </div>
            </div> --}}

                <!-- Card FAQ (1:3) -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><b>FAQ</b></h5>
                            <p class="card-text">Lihat pertanyaan yang sering diajukan tentang sistem ini.</p>
                            <a href="{{ route('faq') }}" class="btn btn-yellow">Lihat FAQ</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Cards Below -->
            <div class="row">
                <!-- Card untuk Gambar 1:1 -->
                <div class="col-md-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                            <center>
                                <h3><b>Gambaran Alur SAKIP</b></h3>
                            </center>
                        </div>
                        <div class="card-body">
                            <img src="{{ asset('gambar/sakip.png') }}" class="img-fluid" alt="sakip">
                        </div>
                    </div>
                </div>

                <!-- Card untuk Gambar 1:2 -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                            <center>
                                <h3><b>Gambar SMART Goals for Project Management</b></h3>
                            </center>
                        </div><br>
                        <div class="card-body">
                            <img src="{{ asset('gambar/smart.png') }}" class="img-fluid" alt="smart">
                        </div>
                    </div>
                </div>

                <!-- Card untuk Teks 1:2 -->
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                            <center>
                                <h3><b>SMART Goals for Project Management</b></h3>
                            </center>
                        </div><br>
                        <div class="card-body">
                            {{-- <h5 class="card-title"><b>Teks 1:2</b></h5> --}}
                            <p class="card-text">

                            <h4>1. Specific</h4>
                            <p>Ketika menetapkan tujuan untuk proyek yang akan kamu lakukan, tujuan tersebut harus jelas
                                dan spesifik. Jika tidak, kamu akan kesulitan untuk tetap fokus pada proyek tersebut.
                            </p>
                            <p>Kamu bisa mempertimbangkan beberapa hal berikut ketika menentukan proyek yang akan
                                dibuat:</p>
                            <ul>
                                <li>Tujuan apa yang ingin dicapai.</li>
                                <li>Apa alasan tujuan tersebut dan mengapa tujuan tersebut penting.</li>
                                <li>Tentukan siapa saja yang akan terlibat untuk mencapai tujuan tersebut.</li>
                                <li>Jika membutuhkan lokasi, tentukan lokasi yang relevan dengan tujuan.</li>
                                <li>Identifikasi persyaratan atau hambatan yang dapat menjadi masalah dalam proses
                                    pelaksanaan.</li>
                            </ul>

                            <h4>2. Measurable</h4>
                            <p>Saat menentukan tujuan proyek, kamu harus memastikan bahwa tujuan tersebut dapat diukur.
                                Dengan begitu, kamu dapat melacak progresnya.</p>
                            <p>Untuk itu, tentukan tugas yang spesifik. Tetapkan apa saja yang harus diselesaikan dan
                                kapan tugas tersebut harus selesai. Ini akan memudahkanmu mengawasi jalannya proyek.</p>

                            <h4>3. Achievable</h4>
                            <p>Agar tujuan proyekmu dapat tercapai, tujuan tersebut harus realistis. Kamu boleh membuat
                                proyek yang menantang tetapi tetap memungkinkan.</p>
                            <p>Perhatikan baik-baik peluang yang sebelumnya terlewatkan. Pikirkan juga sumber daya yang
                                diperlukan untuk menyelesaikan tujuan tersebut.</p>
                            <p>Kamu bisa melibatkan anggota tim dalam menetapkan tujuan proyek. Dengan begitu, mereka
                                dapat memilih area proyek yang akan dikerjakan sesuai dengan keahlian dan kemampuan
                                mereka.</p>

                            <h4>4. Relevant</h4>
                            <p>Tujuan proyek haruslah relevan dengan misi perusahaan. Paling tidak, tujuan tersebut
                                mencerminkan satu atau lebih dari nilai inti perusahaan.</p>
                            <p>Untuk memastikan proyek memberikan hasil yang diharapkan, kamu harus memastikan bahwa
                                setiap tujuan proyek konsisten dengan tujuan perusahaan secara keseluruhan.</p>

                            <h4>5. Time-bound</h4>
                            <p>Kamu perlu memiliki tenggat waktu yang jelas untuk benar-benar fokus dalam mencapai
                                tujuanmu. Tanpa tenggat waktu yang jelas, kamu tidak akan tahu di mana dan kapan harus
                                memulai.</p>
                            <p>Buatlah kerangka waktu yang realistis untuk dicapai pada setiap tahapan proyek. Untuk
                                menghindari maraton yang tidak pernah berakhir dalam sebuah proyek, setiap tahapan harus
                                memiliki tenggat waktu yang pasti.</p>

                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>
@endsection

<style>
    /* Awal card berada di bawah dan tersembunyi */
    .card {
        opacity: 0;
        transform: translateY(50px);
        transition: all 0.6s ease-out;
    }

    /* Setelah halaman dimuat, card akan muncul ke posisi semula */
    .card.show {
        opacity: 1;
        transform: translateY(0);
    }
</style>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
@endpush
