@extends('layouts.app')

@section('title', 'Monitoring')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@section('content')
    @php
        $levelSakip = session('id_sakip_level', 0);
    @endphp
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card" style="width: 100%;">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>Monitoring</b></h2>
                    </center>
                </div>
                <div class="card-body">
                    <!-- Form Pencarian -->
                    
                    @if ($levelSakip == 99 || $levelSakip == 0 || !Str::startsWith($id_satker, 'was'))
                    <form method="GET" action="{{ route('monitoring') }}" class="row g-2 mb-4">
                        <div class="col-md-5">
                            <select name="satker" id="satkerInput" class="form-select">
                                <option value="">-- Pilih Satker --</option>
                                @foreach ($satkers as $satker)
                                    <option value="{{ $satker->id_satker }}"
                                        {{ $search == $satker->id_satker ? 'selected' : '' }}>
                                        {{ $satker->satkernama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">Cari</button>
                        </div>
                    </form>

                    @if ($selectedSatker)
                        <div class="card mt-4">
                            <div class="card-header" style="background-color: #e6bf3e;">
                                <h5>Capaian Kinerja - {{ $selectedSatker->satkernama }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Sidebar Bidang -->
                                    <div class="col-md-3">
                                        <div class="card">
                                            <div class="card-header bg-warning">
                                                <strong>📌 Daftar Bidang</strong>
                                            </div>
                                            <div class="card-body">
                                                @foreach ($bidangs as $bidang)
                                                    <button
                                                        class="btn btn-outline-success text-black w-100 mb-2 bidang-item"
                                                        data-rumpun="{{ $bidang->rumpun }}">
                                                        {{ $bidang->bidang_nama }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Konten Indikator -->
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
                                                <div class="alert alert-info">Pilih Bidang Terlebih dahulu</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @endif
                    <br>
                    @php
                        use Illuminate\Support\Str;
                    @endphp

                    @if ($levelSakip == 99 || $levelSakip == 0 || !Str::startsWith($id_satker, 'was') || !Str::startsWith($id_satker, 'Pengawasan'))
                        <div class="card mb-4">
                            <div class="card-header" style="background-color: #e6bf3e;">
                                <h5>Capaian Sasaran Strategis - {{ $tahun }}</h5>
                            </div>

                            <div class="card-body" id="saspro-wrapper">
                            <!-- Tabs Saspro -->
                            <ul class="nav nav-tabs mb-3" id="sasproTabs" role="tablist">
                                <!-- Tab akan dimasukkan dinamis -->
                            </ul>
                            
                            <!-- Content Saspro -->
                            <div class="tab-content" id="sasproContent">
                                <!-- Konten tabel Saspro akan dimasukkan dinamis -->
                            </div>
                        </div>
                        </div>
                        <br>
                    @endif
                    
                </div>
            </div>
        </div>
    </div>
@endsection

<!-- CDN Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#satkerInput').select2({
                placeholder: "Ketik Nama atau Kode Satker...",
                allowClear: true,
                width: '100%'
            });
        });

        $(document).ready(function() {
            let selectedRumpun = null;
            let idSatker = $("#satkerInput").val();

            // Klik bidang
            $(".bidang-item").on("click", function() {
                $(".bidang-item").removeClass("active");
                $(this).addClass("active");

                selectedRumpun = $(this).data("rumpun");

                // tampilkan controls triwulan + tombol reload
                $("#controls-wrapper").show();

                let triwulan = $("#triwulan").val();
                loadSubindikator(selectedRumpun, triwulan);
            });

            // Ganti triwulan langsung load
            $(document).on('change', '#triwulan', function() {
                let triwulan = $(this).val();
                if (selectedRumpun) {
                    loadSubindikator(selectedRumpun, triwulan);
                }
            });

            // Klik tombol reload
            $(document).on('click', '#reloadBtn', function() {

                let triwulan = $("#triwulan").val();
                loadSubindikator(selectedRumpun, triwulan);
            });

            // Fungsi load data subindikator
            function loadSubindikator(rumpun, tw) {
                $("#subindikator-wrapper").html('<div class="text-muted">Memuat data...</div>');
                $.ajax({
                    url: "/monitoring/subindikator2/" + rumpun,
                    data: {
                        triwulan: tw,
                        id_satker: idSatker
                    },
                    success: function(res) {
                        if (!res || res.length === 0) {
                            $("#subindikator-wrapper").html(
                                '<div class="alert alert-warning">Tidak ada data</div>');
                            return;
                        }

                        let html = `
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Indikator</th>
                                <th>Capaian</th>
                                <th>Target</th>
                                <th>Capaian terhadap target</th>
                                <th>Faktor</th>
                                <th>Upaya</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                        $.each(res, function(i, row) {
                            html += `
                        <tr>
                            <td>${row.indikator_nama}</td>
                            <td>${row.persentase}%</td>
                            <td>${row.target_pk}%</td>
                            <td>${row.capaian_pk}%</td>
                            <td>${row.faktor ?? '-'}</td>
                            <td>${row.langkah ?? '-'}</td>
                        </tr>
                    `;
                        });

                        html += "</tbody></table>";
                        $("#subindikator-wrapper").html(html);
                    },
                    error: function(xhr) {
                        $("#subindikator-wrapper").html(
                            '<div class="alert alert-danger">Terjadi kesalahan mengambil data</div>'
                        );
                        console.error(xhr.responseText);
                    }
                });
            }
        });
        
    </script>
   <script>
$(document).ready(function () {
    loadSaspro();

    function loadSaspro() {
        $.ajax({
            url: "{{ route('capaian.saspro.all') }}",
            method: "GET",
            dataType: "json",
            success: function (res) {
                if (!res || res.length === 0) {
                    $("#sasproContent").html('<div class="alert alert-danger">Tidak ada data</div>');
                    return;
                }

                let tabsHtml = '';
                let contentHtml = '';

                $.each(res, function (i, saspro) {
                    let activeClass = i === 0 ? 'active' : '';
                    let showClass = i === 0 ? 'show active' : '';

                    // buat tab header
                    tabsHtml += `
                        <li class="nav-item" role="presentation">
                            <button class="nav-link ${activeClass}" id="tab-${saspro.id_saspro}" data-bs-toggle="tab"
                                data-bs-target="#content-${saspro.id_saspro}" type="button" role="tab">
                                Sasaran Strategis ${i + 1}
                            </button>
                        </li>
                    `;

                    // buat tabel indikator
                    let indikatorRows = '';
                    $.each(saspro.indikators, function (j, ind) {
                        function showVal(v) {
                            return (v !== null && v !== undefined) ? v + '%' : '-';
                        }

                        indikatorRows += `
                            <tr>
                                <td>${j+1}</td>
                                <td>${ind.nama}</td>
                                <td class="text-center">${ind.target_tw1 || 0}%</td>
                                <td class="text-center">${showVal(ind.capaian_tw1)}</td>
                                <td class="text-center">${showVal(ind.capaian_terhadap_target_tw1)}</td>
                                <td class="text-center">${showVal(ind.capaian_tw2)}</td>
                                <td class="text-center">${showVal(ind.capaian_terhadap_target_tw2)}</td>
                                <td class="text-center">${showVal(ind.capaian_tw3)}</td>
                                <td class="text-center">${showVal(ind.capaian_terhadap_target_tw3)}</td>
                                <td class="text-center">${showVal(ind.capaian_tw4)}</td>
                                <td class="text-center">${showVal(ind.capaian_terhadap_target_tw4)}</td>

                            </tr>
                        `;
                    });

                    // setiap saspro punya canvas chart unik
                    let chartId = `chart-${saspro.id_saspro}`;

                    contentHtml += `
                        <div class="tab-pane fade ${showClass}" id="content-${saspro.id_saspro}" role="tabpanel">
                            <h4 class="mt-3"><b>${saspro.nama_saspro}</b></h4>
                            <table class="table table-bordered table-striped">
                                <thead class="table-warning text-center align-middle">
                                    <tr>
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Nama Indikator</th>
                                        <th rowspan="2">Target</th>
                                        <th colspan="2">Triwulan 1</th>
                                        <th colspan="2">Triwulan 2</th>
                                        <th colspan="2">Triwulan 3</th>
                                        <th colspan="2">Triwulan 4</th>
                                    </tr>
                                    <tr>
                                        <th>Capaian</th>
                                        <th>Capaian terhadap Target</th>
                                        <th>Capaian</th>
                                        <th>Capaian terhadap Target</th>
                                        <th>Capaian</th>
                                        <th>Capaian terhadap Target</th>
                                        <th>Capaian</th>
                                        <th>Capaian terhadap Target</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${indikatorRows}
                                </tbody>
                            </table>
                            <canvas id="${chartId}" height="100" class="mb-4"></canvas>
                        </div>
                    `;
                });

                // append tab & content ke DOM
                $("#sasproTabs").html(tabsHtml);
                $("#sasproContent").html(contentHtml);
            function chunkArray(arr, size) {
                const result = [];
                for (let i = 0; i < arr.length; i += size) {
                    result.push(arr.slice(i, i + size));
                }
                return result;
            }
                            // buat chart untuk tiap saspro
                            res.forEach((saspro, idx) => {
                const ctx = document.getElementById(`chart-${saspro.id_saspro}`).getContext('2d');
                const labels = saspro.indikators.map(ind => ind.nama);
                const tw1 = saspro.indikators.map(ind => ind.capaian_tw1 ?? 0);
                const tw2 = saspro.indikators.map(ind => ind.capaian_tw2 ?? 0);
                const tw3 = saspro.indikators.map(ind => ind.capaian_tw3 ?? 0);
                const tw4 = saspro.indikators.map(ind => ind.capaian_tw4 ?? 0);
            
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'TW1', data: tw1, backgroundColor: 'rgba(54, 162, 235, 0.6)' },
                            { label: 'TW2', data: tw2, backgroundColor: 'rgba(255, 206, 86, 0.6)' },
                            { label: 'TW3', data: tw3, backgroundColor: 'rgba(75, 192, 192, 0.6)' },
                            { label: 'TW4', data: tw4, backgroundColor: 'rgba(255, 99, 132, 0.6)' },
                        ]
                    },
                    options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: `Capaian ${saspro.nama_saspro}`,  font: { size: 20, weight: 'bold' } }
                },
                scales: {
                    y: { beginAtZero: false, min: 0, max: 100 },
                    x: {
                        ticks: {
                callback: function(value, index, ticks) {
                    let label = this.getLabelForValue(index);
                    
                    // pisahkan label jadi kata-kata
                    let words = label.split(' '); 
                    let lines = [];
                    let line = '';
            
                    words.forEach((word) => {
                        if ((line + ' ' + word).trim().split(' ').length <= Math.ceil(words.length / 4)) {
                            line = (line + ' ' + word).trim();
                        } else {
                            lines.push(line);
                            line = word;
                        }
                    });
            
                    if(line) lines.push(line);
            
                    return lines;
                },
                font: { size: 14 }
            }
            
                    }
                }
            }

    });
});

            },
            error: function (xhr) {
                let msg = (xhr.responseJSON && xhr.responseJSON.error)
                    ? xhr.responseJSON.error
                    : xhr.statusText;
                $("#sasproContent").html('<div class="alert alert-danger">Terjadi kesalahan: ' + msg + '</div>');
                console.error(xhr.responseText);
            }
        });
    }
});
</script>
<style>
/* wrap label sumbu X Chart.js */
.chartjs-label-wrap {
    white-space: normal !important;
    font-size: 14px;
}
</style>
@endpush
