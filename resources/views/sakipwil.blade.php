@extends('layouts.app')

@section('title', 'SAKIP Wilayah')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@section('content')
    <div class="content" id="content">
<!--        <div id="loadingOverlay"-->
<!--            style="-->
<!--    position: fixed; top:0; left:0;-->
<!--    width:100%; height:100%;-->
<!--    background: rgba(255,255,255,0.9);-->
<!--    display: flex;-->
<!--    align-items: center;-->
<!--    justify-content: center;-->
<!--    z-index: 9999;-->
<!--">-->
<!--            <div class="spinner"-->
<!--                style="-->
<!--        border: 8px solid #f3f3f3;-->
<!--        border-top: 8px solid #3498db;-->
<!--        border-radius: 50%;-->
<!--        width: 60px; height: 60px;-->
<!--        animation: spin 1s linear infinite;-->
<!--    ">-->
<!--            </div>-->
<!--        </div>-->

<!--        <style>-->
<!--            @keyframes spin {-->
<!--                0% {-->
<!--                    transform: rotate(0deg);-->
<!--                }-->

<!--                100% {-->
<!--                    transform: rotate(360deg);-->
<!--                }-->
<!--            }-->
<!--        </style>-->

        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>DATA PERENCANAAN AKIP SATUAN KERJA KEJAKSAAN RI</b></h2>
                    </center>
                </div>
                <div class="card-body">
                    @php
                        $levelSakip = session('id_sakip_level', 0);
                    @endphp
                    <!-- List Pengumuman -->
                    @if ($levelSakip == 99 || $levelSakip == 0)
                        <div class="mb-3 d-flex align-items-center">
                            <input type="text" id="searchInput" class="form-control me-2"
                                placeholder="Cari Nama Satker">
                            <button id="exportExcel" class="btn btn-success me-2">Export Excel</button>
                            <!--<button id="exportPdf" class="btn btn-danger">Export PDF</button>-->
                        </div>
                    @endif
                   <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center rounded" id="satkerTable">
                        <thead class="table-warning">
                                <tr>
                                    <th>No</th>
                                    <!--<th>ID Satker</th>-->
                                    <th>Nama Satker</th>
                                    {{-- <th>Keputusan</th> --}}
                                    <th>Renstra</th>
                                    <th>IKU</th>
                                    <th>Renja</th>
                                    <th>RKAKL</th>
                                    <th>Dipa</th>
                                    <th>Renaksi</th>
                                    <th>PK</th>
                                    <th>LKJIP TW1</th>
                                    <th>LKJIP TW2</th>
                                    <th>LKJIP TW3</th>
                                    <th>LKJIP TW4</th>
                                    <th>Rapat Staff</th>
                                    <th>LHE AKIP</th>
                                    <th>TL LHE AKIP</th>
                                    <th>Monev Renaksi</th>
                                    @if ($levelSakip == 999)
                                        <th>Perjanjian Kinerja</th>
                                        <th>Jumlah Indikator Kinerja</th>
                                        <th>Status Pengukuran Kinerja</th>
                                        <th>LKjIP</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if ($data->isNotEmpty())
                                    @foreach ($data as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <!--<td>{{ $row->id_satker }}</td>-->
                                            <td style="text-align: left;">
                                                @if ($row->id_kejari == 0)
                                                    <b>{{ str_replace('_', ' ', $row->satkernama) }}</b>
                                                @else
                                                    {{ str_replace('_', ' ', $row->satkernama) }}
                                                @endif
                                            </td>
                                            {{-- <td>
                                                @if (!empty($kepList[$row->id_satker]))
                                                    <a href="{{ asset('uploads/KEP/' . $row->id_satker . '.pdf') }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003;
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td> --}}
                                            <td>
                                                @if (isset($renstra[$row->id_satker]) && $renstra[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestRenstra = $renstra[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestRenstra->id_satker . '/' . $latestRenstra->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($iku[$row->id_satker]) && $iku[$row->id_satker]->isNotEmpty())
                                                    <!-- Ambil data iku pertama (karena sudah dikelompokkan berdasarkan id_satker dan diurutkan) -->
                                                    @php
                                                        $latestiku = $iku[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestiku->id_satker . '/' . $latestiku->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($renja[$row->id_satker]) && $renja[$row->id_satker]->isNotEmpty())
                                                    <!-- Ambil data renja pertama (karena sudah dikelompokkan berdasarkan id_satker dan diurutkan) -->
                                                    @php
                                                        $latestrenja = $renja[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestrenja->id_satker . '/' . $latestrenja->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($rkakl[$row->id_satker]) && $rkakl[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestRkakl = $rkakl[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestRkakl->id_satker . '/' . $latestRkakl->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($dipa[$row->id_satker]) && $dipa[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestDipa = $dipa[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestDipa->id_satker . '/' . $latestDipa->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($renaksi[$row->id_satker]) && $renaksi[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        // Ambil data renaksi pertama untuk id_satker tertentu
                                                        $latestRenaksi = $renaksi[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestRenaksi->id_satker . '/' . $latestRenaksi->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (isset($pk[$row->id_satker]) && $pk[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestPk = $pk[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestPk->id_satker . '/' . $latestPk->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                                 <td>
    @php $filename = $sortedLkjipTW1[$row->id_satker]; @endphp
    @if($filename)
        <a href="{{ asset('uploads/repository/' . $row->id_satker . '/' . rawurlencode($filename)) }}"
           target="_blank" class="text-success" style="text-decoration: none;">&#10003;</a>
    @else
        <span class="text-danger">-</span>
    @endif
</td>

<td>
    @php $filename = $sortedLkjipTW2[$row->id_satker]; @endphp
    @if($filename)
        <a href="{{ asset('uploads/repository/' . $row->id_satker . '/' . rawurlencode($filename)) }}"
           target="_blank" class="text-success" style="text-decoration: none;">&#10003;</a>
    @else
        <span class="text-danger">-</span>
    @endif
</td>

<!-- sama untuk TW3 dan TW4 -->
<td>
    @php $filename = $sortedLkjipTW3[$row->id_satker]; @endphp
    @if($filename)
        <a href="{{ asset('uploads/repository/' . $row->id_satker . '/' . rawurlencode($filename)) }}"
           target="_blank" class="text-success" style="text-decoration: none;">&#10003;</a>
    @else
        <span class="text-danger">-</span>
    @endif
</td>

<td>
    @php $filename = $sortedLkjipTW4[$row->id_satker]; @endphp
    @if($filename)
        <a href="{{ asset('uploads/repository/' . $row->id_satker . '/' . rawurlencode($filename)) }}"
           target="_blank" class="text-success" style="text-decoration: none;">&#10003;</a>
    @else
        <span class="text-danger">-</span>
    @endif
</td>


                                            <td>
                                                @if (isset($rastaff[$row->id_satker]) && $rastaff[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestRastaff = $rastaff[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestRastaff->id_satker . '/' . $latestRastaff->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif

                                            </td>
                                            {{-- LHE AKIP --}}
                                            <td>
                                                @if (isset($lhe[$row->id_satker]) && $lhe[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestLhe = $lhe[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestLhe->id_satker . '/' . $latestLhe->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003; <!-- Tanda centang -->
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>
                                            {{-- TL LHE AKIP --}}
                                            <td>
                                                @if (isset($tl_lhe_akip[$row->id_satker]) && $tl_lhe_akip[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestTlLheAkip = $tl_lhe_akip[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestTlLheAkip->id_satker . '/' . $latestTlLheAkip->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003;
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>

                                            {{-- Monev Renaksi --}}
                                            <td>
                                                @if (isset($monev_renaksi[$row->id_satker]) && $monev_renaksi[$row->id_satker]->isNotEmpty())
                                                    @php
                                                        $latestMonevRenaksi = $monev_renaksi[$row->id_satker]->first();
                                                    @endphp
                                                    <a href="{{ asset('uploads/repository/' . $latestMonevRenaksi->id_satker . '/' . $latestMonevRenaksi->id_filename) }}"
                                                        target="_blank" class="text-success" style="text-decoration: none;">
                                                        &#10003;
                                                    </a>
                                                @else
                                                    <span class="text-danger">-</span>
                                                @endif
                                            </td>

                                            @if ($levelSakip == 999)
                                                <td>{{ $row->jumlah_indikator_kinerja ?? '-' }}</td>
                                                <td>{{ $row->status_pengukuran_kinerja ?? '-' }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="13" class="text-center text-danger">Tidak ada data yang tersedia.
                                        </td> <!-- Pesan jika tidak ada data -->
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    </div>
                    <br>
                    <div class="col-md-12">
                        <div class="card shadow-sm mb-4">
                            <div class="card border-light shadow-sm" style="background-color: #e3e2e2;">
                                <center>
                                    <h2><b>Rekap Dokumen Seluruh Satker</b></h2>
                                </center>
                            </div>

                            <div class="row">
                                {{-- <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>Keputusan Terisi</b></h5>
                                            </center>
                                            <canvas id="pieChart1"></canvas>
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>Renstra</b></h5>
                                            </center>
                                            <canvas id="pieChart2"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>IKU</b></h5>
                                            </center>
                                            <canvas id="pieChart3"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>Renja</b></h5>
                                            </center>
                                            <canvas id="pieChart4"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>RKAKL</b></h5>
                                            </center>
                                            <canvas id="pieChart5"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>DIPA</b></h5>
                                            </center>
                                            <canvas id="pieChart6"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>Renaksi</b></h5>
                                            </center>
                                            <canvas id="pieChart7"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>Perjanjian Kinerja</b></h5>
                                            </center>
                                            <canvas id="pieChart8"></canvas>
                                        </div>
                                    </div>
                                </div>

    @foreach (['TW 1','TW 2','TW 3','TW 4'] as $index => $tw)
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <center>
                        <h5 class="card-title"><b>LKJIP {{ $tw }}</b></h5>
                    </center>
                    <canvas id="pieChartLkjip{{ $index + 1 }}"></canvas>
                </div>
            </div>
        </div>
    @endforeach

                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>Rapat Staff</b></h5>
                                            </center>
                                            <canvas id="pieChart10"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>LHE AKIP</b></h5>
                                            </center>
                                            <canvas id="pieChart11"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>TL LHE AKIP</b></h5>
                                            </center>
                                            <canvas id="pieChart12"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-body">
                                            <center>
                                                <h5 class="card-title"><b>Monev Renaksi</b></h5>
                                            </center>
                                            <canvas id="pieChart13"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('style')
    <style>
        .link-no-underline {
            text-decoration: none;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }

        .table-bordered {
            border: 1px solid #dee2e6;
        }

        .table thead th {
            border-bottom: 2px solid #ebca37;
            position: sticky;
            top: 0;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .table-center {
            width: 100%;
            /* Opsional: sesuaikan lebar tabel */
            border-collapse: collapse;
            /* Menghilangkan jarak antara border sel */
        }

        .table-center th,
        .table-center td {
            text-align: center;
            /* Mengatur teks di tengah */
            padding: 10px;
            /* Menambahkan padding untuk estetika */
            border: 1px solid #ddd;
            /* Mengatur border pada sel */
        }

        .no-link {
            text-decoration: none;
            /* Menghilangkan garis bawah */
            color: inherit;
            /* Menggunakan warna teks dari elemen induk */
            /* cursor: default; */
            /* Mengubah kursor agar tidak menunjukkan sebagai link */
        }
        .table-responsive {
        max-height: 800px; /* atur tinggi sesuai kebutuhan */
        overflow-y: auto;
    }
    </style>
    


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // keputusan
            // const sortedKepList = @json($sortedKepList); // Mengambil data dari PHP

            // // Menghitung jumlah keputusan yang terisi dan belum terisi
            // const terisi = sortedKepList.filter(item => item).length; // Menghitung yang terisi
            // const belumTerisi = sortedKepList.length - terisi; // Menghitung yang belum terisi

            // const ctx = document.getElementById('pieChart1').getContext('2d');
            // const pieChart = new Chart(ctx, {
            //     type: 'pie',
            //     data: {
            //         labels: ['Keputusan Terisi', 'Keputusan Belum Terisi'],
            //         datasets: [{
            //             label: 'Jumlah Keputusan',
            //             data: [terisi, belumTerisi],
            //             backgroundColor: ['#4CAF50', '#E53935'],
            //             borderColor: ['#00838F', '#C62828'],
            //             borderWidth: 1
            //         }]
            //     },
            //     options: {
            //         responsive: true,
            //         plugins: {
            //             legend: {
            //                 position: 'bottom',
            //             },
            //             tooltip: {
            //                 callbacks: {
            //                     label: function(tooltipItem) {
            //                         return `${tooltipItem.label}: ${tooltipItem.raw}`;
            //                     }
            //                 }
            //             }
            //         }
            //     }
            // });
            //renstra
            const sortedRenstraList = @json($sortedRenstraList);
            const terisiRenstra = sortedRenstraList.filter(item => item).length;
            const belumTerisiRenstra = sortedRenstraList.length - terisiRenstra;

            const ctx3 = document.getElementById('pieChart2').getContext('2d');
            new Chart(ctx3, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiRenstra, belumTerisiRenstra],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            //IKU
            const sortedIkuList = @json($sortedIkuList);
            const terisiIku = sortedIkuList.filter(item => item).length;
            const belumTerisiIku = sortedIkuList.length - terisiIku;

            const ctxIku = document.getElementById('pieChart3').getContext('2d');
            new Chart(ctxIku, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiIku, belumTerisiIku],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            //renja
            const sortedRenjaList = @json($sortedRenjaList);
            const terisiRenja = sortedRenjaList.filter(item => item).length;
            const belumTerisiRenja = sortedRenjaList.length - terisiRenja;

            const ctxRenja = document.getElementById('pieChart4').getContext('2d');
            new Chart(ctxRenja, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiRenja, belumTerisiRenja],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            //rkakl
            const sortedRkaklList = @json($sortedRkaklList);
            const terisiRkakl = sortedRkaklList.filter(item => item).length;
            const belumTerisiRkakl = sortedRkaklList.length - terisiRkakl;

            const ctxRkakl = document.getElementById('pieChart5').getContext('2d');
            new Chart(ctxRkakl, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiRkakl, belumTerisiRkakl],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            //dipa
            const sortedDipaList = @json($sortedDipaList);
            const terisiDipa = sortedDipaList.filter(item => item).length;
            const belumTerisiDipa = sortedDipaList.length - terisiDipa;

            const ctxDipa = document.getElementById('pieChart6').getContext('2d');
            new Chart(ctxDipa, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiDipa, belumTerisiDipa],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            //renaksi
            const sortedRenaksiList = @json($sortedRenaksiList);
            const terisiRenaksi = sortedRenaksiList.filter(item => item).length;
            const belumTerisiRenaksi = sortedRenaksiList.length - terisiRenaksi;

            const ctxRenaksi = document.getElementById('pieChart7').getContext('2d');
            new Chart(ctxRenaksi, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiRenaksi, belumTerisiRenaksi],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            //pk
            const sortedPkList = @json($sortedPkList);
            const terisiPk = sortedPkList.filter(item => item).length;
            const belumTerisiPk = sortedPkList.length - terisiPk;

            const ctxPk = document.getElementById('pieChart8').getContext('2d');
            new Chart(ctxPk, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiPk, belumTerisiPk],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            //ljkip
             // Data per TW
    const lkjipTW1 = @json($sortedLkjipTW1);
    const lkjipTW2 = @json($sortedLkjipTW2);
    const lkjipTW3 = @json($sortedLkjipTW3);
    const lkjipTW4 = @json($sortedLkjipTW4);

    const lkjipData = [lkjipTW1, lkjipTW2, lkjipTW3, lkjipTW4];

    lkjipData.forEach((twData, i) => {
        const terisi = Object.values(twData).filter(item => item).length;
        const belumTerisi = Object.values(twData).length - terisi;

        const ctx = document.getElementById('pieChartLkjip' + (i + 1)).getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Terisi', 'Belum Terisi'],
                datasets: [{
                    data: [terisi, belumTerisi],
                    backgroundColor: ['#4CAF50', '#E53935'],
                    borderColor: ['#00838F', '#C62828'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
    
            //rastaff
            const sortedRastaffList = @json($sortedRastaffList);
            const terisiRastaff = sortedRastaffList.filter(item => item).length;
            const belumTerisiRastaff = sortedRastaffList.length - terisiRastaff;

            const ctxRastaff = document.getElementById('pieChart10').getContext('2d');
            new Chart(ctxRastaff, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiRastaff, belumTerisiRastaff],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            //lhe akip
            const sortedLheList = @json($sortedLheList);
            const terisiLhe = sortedLheList.filter(item => item).length;
            const belumTerisiLhe = sortedLheList.length - terisiLhe;
            const ctxLhe = document.getElementById('pieChart11').getContext('2d');
            new Chart(ctxLhe, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiLhe, belumTerisiLhe],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            //tl lhe akip
            const sortedTlLheAkipList = @json($sortedTlLheAkipList);
            const terisiTlLheAkip = sortedTlLheAkipList.filter(item => item).length;
            const belumTerisiTlLheAkip = sortedTlLheAkipList.length - terisiTlLheAkip;
            const ctxTlLheAkip = document.getElementById('pieChart12').getContext('2d');
            new Chart(ctxTlLheAkip, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiTlLheAkip, belumTerisiTlLheAkip],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            //monev renaksi
            const sortedMonevRenaksiList = @json($sortedMonevRenaksiList);
            const terisiMonevRenaksi = sortedMonevRenaksiList.filter(item => item).length;
            const belumTerisiMonevRenaksi = sortedMonevRenaksiList.length - terisiMonevRenaksi;
            const ctxMonevRenaksi = document.getElementById('pieChart13').getContext('2d');
            new Chart(ctxMonevRenaksi, {
                type: 'pie',
                data: {
                    labels: ['Terisi', 'Belum Terisi'],
                    datasets: [{
                        data: [terisiMonevRenaksi, belumTerisiMonevRenaksi],
                        backgroundColor: ['#4CAF50', '#E53935'],
                        borderColor: ['#00838F', '#C62828'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

        });
        // Hilangkan loading setelah halaman selesai dimuat
        // window.addEventListener("load", function() {
        //     document.getElementById("loadingOverlay").style.display = "none";
        // });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- <script>
        $(document).ready(function() {
            $('#searchInput').on('keyup', function() {
                let value = $(this).val().toLowerCase();

                $('#satkerTable tbody tr').filter(function() {
                    let col2 = $(this).find('td:nth-child(2)').text().toLowerCase();
                    let col3 = $(this).find('td:nth-child(3)').text().toLowerCase();
                    $(this).toggle(col2.indexOf(value) > -1 || col3.indexOf(value) > -1);
                });
            });
        });
    </script> --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("searchInput");
            const table = document.getElementById("satkerTable");

            // 🔎 Filter pencarian hanya kolom ID Satker & Nama Satker
            searchInput.addEventListener("keyup", function() {
                const value = this.value.toLowerCase();
                Array.from(table.tBodies[0].rows).forEach(row => {
                    const col2 = row.cells[1].textContent.toLowerCase(); // ID Satker
                    const col3 = row.cells[2].textContent.toLowerCase(); // Nama Satker
                    row.style.display = (col2.includes(value) || col3.includes(value)) ? "" :
                        "none";
                });
            });

            // 📊 Export Excel (hanya baris & kolom yang tampil + ID Satker tetap 00 di depan)
            document.getElementById("exportExcel").addEventListener("click", function() {
                let data = [];
                let rows = table.querySelectorAll("tr");

                rows.forEach((row, rowIndex) => {
                    // ✅ cek apakah row masih tampil (tidak hidden oleh filter)
                    if (row.style.display !== "none") {
                        let rowData = [];
                        row.querySelectorAll("th, td").forEach((cell, cellIndex) => {
                            let text = cell.innerText;

                            // Jika kolom ke-2 (ID Satker), simpan sebagai teks
                            if (rowIndex > 0 && cellIndex === 1) {
                                rowData.push("'" + text);
                            } else {
                                rowData.push(text);
                            }
                        });
                        data.push(rowData);
                    }
                });

                // Buat worksheet & workbook
                let ws = XLSX.utils.aoa_to_sheet(data);
                let wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

                // Simpan file
                XLSX.writeFile(wb, "data_satker.xlsx");
            });


            // Export PDF
            document.addEventListener("DOMContentLoaded", function() {
                const table = document.getElementById("satkerTable");

                document.getElementById("exportPDF").addEventListener("click", function() {
                    const {
                        jsPDF
                    } = window.jspdf; // ambil object jsPDF
                    const doc = new jsPDF('l', 'pt', 'a4'); // landscape, point, A4

                    doc.text("Data Satker", 40, 30);

                    // gunakan plugin autotable
                    doc.autoTable({
                        html: '#satkerTable',
                        startY: 50,
                        theme: 'grid',
                        headStyles: {
                            fillColor: [22, 160, 133]
                        }
                    });

                    doc.save("data_satker.pdf");
                });
            });
        });
    </script>
    <script>
    $(document).ready(function() {
    $('#satkerTable').DataTable({
        "paging": false,          // bisa diubah ke false kalau tidak ingin pagination
        "ordering": true,        // mengaktifkan fitur sort
        "info": false,           // sembunyikan "Showing x of y entries"
        "autoWidth": false,      // agar tidak auto resize kolom
        "order": [],             // biar default tidak langsung sort kolom pertama
        "searching": false,
        "fixedHeader": true, 
        // "language": {
        //     "search": "Cari:",    // ubah teks pencarian jadi Bahasa Indonesia
        //     "paginate": {
        //         "previous": "Sebelumnya",
        //         "next": "Berikutnya"
        //         }
        //     }
        });
    });
    </script>
    
    <!-- Include jsPDF AutoTable plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.27/jspdf.plugin.autotable.min.js"></script>
