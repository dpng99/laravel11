@extends('layouts.app')

@section('title', 'Pengukuran')

@section('content')
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card" style="width: 100%;">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>Pengukuran</b></h2>
                    </center>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <strong>📌 Daftar Bidang</strong>
                                </div>
                                <div class="card-body">
                                    @php
                                        use App\Models\Bidang;
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
                                        <button class="btn btn-outline-warning text-black w-100 mb-2 bidang-item"
                                            data-rumpun="{{ $bidang->rumpun }}">
                                            {{ $bidang->bidang_nama }}
                                        </button>
                                    @endforeach

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
                                            <button type="submit" class="btn btn-success" id="btn-simpan"
                                                style="display: none;">Simpan</button>

                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const bulanList = [
            'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
            'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
        ];

        function formatRibuan(angka) {
            let parts = angka.replace(/[^\d,]/g, '').split(',');
            let number = parts[0];
            let decimal = parts[1] || '';
            let formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return decimal ? `${formatted},${decimal}` : formatted;
        }

        // $.getJSON('/get-subindikator/' + rumpun, function(response) {
        //     response.forEach(function(indikator) {
        //         let labelPenghitungan = ['Ditangani', 'Diselesaikan'];

        //         if (indikator.indikator_penghitungan) {
        //             let labels = indikator.indikator_penghitungan.split(',');
        //             if (labels.length === 2) {
        //                 labelPenghitungan = labels.map(s => s.trim());
        //             }
        //         }

        //         // Sekarang labelPenghitungan[0] dan labelPenghitungan[1] adalah label yang bisa kamu tampilkan
        //         console.log("Label:", labelPenghitungan[0], labelPenghitungan[1]);

        //         // Gunakan label ini untuk menampilkan di header tabel atau placeholder input
        //         // Tapi name="ditangani" dan name="diselesaikan" tetap
        //     });
        // });

        $(document).on('input', '.angka-format', function() {
            let val = $(this).val();
            let parts = val.replace(/[^0-9,]/g, '').split(',');
            let number = parts[0];
            let decimal = parts[1] ?? '';
            let formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            $(this).val(val.includes(',') ? `${formatted},${decimal}` : formatted);
        });

        $(document).ready(function() {
            $('.bidang-item').on('click', function() {
                // Hilangkan class aktif dari semua tombol bidang
                $('.bidang-item').removeClass('active-bidang');

                // Tambahkan class aktif ke tombol yang diklik
                $(this).addClass('active-bidang');

                // Lanjutkan dengan AJAX...
            });

            $('.bidang-item').on('click', function() {
                var rumpun = $(this).data('rumpun');
                $.ajax({
                    url: '/get-subindikator/' + rumpun,
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

                             // Ambil label dari indikator_penghitungan
                            let labelPenghitungan = ['Ditangani',
                                'Diselesaikan'
                            ]; // default
                            if (indikator.indikator_penghitungan) {
                                let labels = indikator.indikator_penghitungan.split(',')
                                    .map(s => s.trim());
                                 // kalau hanya ada 1 label → jadikan array 1 elemen
                                if (labels.length >= 1) {
                                    labelPenghitungan = labels;
                                }
                            }
 
                            subIndikators.forEach(sub => {
                                  let table = '';

                                if (labelPenghitungan.length === 1) {
                                    // === MODE TRIWULAN ===
                                    let triwulanList = ['TW1', 'TW2', 'TW3',
                                        'TW4'
                                    ];

                                    table = `
                        <div class="table-responsive mb-4">
                            <strong>${sub}</strong>
                            <input type="hidden" name="indikator_id[${sub}]" value="${indikator.id}" />
                            <input type="hidden" name="sub_indikator_list[]" value="${sub}" />

                <table class="table table-bordered text-center mt-2">
                    <thead>
                        <tr>
                            <th>Label</th>
                            ${triwulanList.map(tw => `<th>${tw}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>${labelPenghitungan[0]}</td>
                            ${triwulanList.map(tw => `
                                            <td>
                                                <input type="text" style="width:120px text-align:center"
                                                    class="form-control angka-format"
                                                    name="${labelPenghitungan[0].toLowerCase()}[${sub}][${tw}]"
                                                    placeholder="-">
                                            </td>
                                        `).join('')}
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
                                } else {
                                    // === MODE BULANAN ===
                                    let bulanList = [
                                        'JANUARI', 'FEBRUARI', 'MARET',
                                        'APRIL', 'MEI', 'JUNI',
                                        'JULI', 'AGUSTUS', 'SEPTEMBER',
                                        'OKTOBER', 'NOVEMBER', 'DESEMBER'
                                    ];


                                    let sisaTahunLaluInput = `
                            <div class="mb-2 d-flex align-items-center">
                    <label class="me-2 mb-0" style="white-space: nowrap;">Sisa tahun lalu:</label>
                    <input type="text" class="form-control angka-format mb-2" 
                    name="sisa_tahun_lalu[${sub}]" style="width:200px;" />
                </div>
                 `;

                                    table = `
            <div class="table-responsive mb-4">
                <strong>${sub}</strong>
                <input type="hidden" name="indikator_id[${sub}]" value="${indikator.id}" />
                <input type="hidden" name="sub_indikator_list[]" value="${sub}" />

                ${sisaTahunLaluInput}

            <table class="table table-bordered text-center mt-2">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        ${bulanList.map(bulan => `<th>${bulan}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                 ${labelPenghitungan.map(label => `
                                        <tr>
                                            <td>${label}</td>
                                            ${bulanList.map(bulan => `
                                    <td>
                                        <input type="text" style="width:120px"
                                            class="form-control angka-format"
                                            name="${label.toLowerCase()}[${sub}][${bulan}]"
                                            placeholder="-">
                                    </td>
                                `).join('')}
                                        </tr>
                                    `).join('')}
                    </tbody>
                </table>
            </div>
        `;
                                }

                                indikatorSection.append(table);
                            });

                           // Ambil data pengukuran dari DB
$.ajax({
    url: '/get-pengukuran/' + indikator.id,
    method: 'GET',
    success: function(pengukuranData) {
        const bulanIndex = [
            '', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
            'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
        ];

        // ambil label sesuai indikator ini
        let labels = indikator.indikator_penghitungan
            ? indikator.indikator_penghitungan.split(',').map(s => s.trim().toLowerCase())
            : ['ditangani', 'diselesaikan']; // default

        pengukuranData.forEach(item => {
            let bulanNama = bulanIndex[item.bulan];

            // isi nilai capaian ke setiap label
            if (item.perhitungan) {
                let parts = item.perhitungan.split(';');

                labels.forEach((label, idx) => {
                    let val = parts[idx] ?? '';
                    let selector = `input[name="${label}[${item.sub_indikator}][${bulanNama}]"]`;
                    if ($(selector).length) {
                        $(selector).val(val ? formatRibuan(val.toString()) : '-');
                    }
                });
            }

            // === tambahan: sisa tahun lalu (Januari saja)
            if (item.bulan === 1 && item.sisa_tahun_lalu !== null) {
                let sisaInput = $(`input[name="sisa_tahun_lalu[${item.sub_indikator}]"]`);
                if (sisaInput.length) {
                    sisaInput.val(formatRibuan(item.sisa_tahun_lalu.toString()));
                }
            }
        });
    },
    error: function(err) {
        console.error("Gagal load pengukuran:", err);
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
    <script>
        $(document).ready(function() {
            // Sembunyikan tombol simpan saat halaman pertama kali dimuat
            $('#btn-simpan').hide();

            // Ketika tombol bidang diklik
            $('.bidang-item').on('click', function() {
                // Tampilkan tombol simpan
                $('#btn-simpan').fadeIn();

                // Tambahan: jika ingin hanya 1 tombol bidang yang aktif berwarna kuning
                $('.bidang-item').removeClass('active text-white bg-warning');
                $(this).addClass('active text-white bg-warning');

                // TODO: Tambahkan logika AJAX load sub indikator jika perlu
            });
        });
    </script>
@endpush
<style>
    .bidang-item.active-bidang {
        background-color: #ffc107;
        /* Bootstrap's warning (yellow) */
        color: black;
        border-color: #ffc107;
    }
</style>
