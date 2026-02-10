@extends('layouts.app')
@section('title', 'LKE Pengawasan')
@section('content')
 <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card-body">
<!--<div class='container'>-->
<!--    <div class='container-fluid align-items-center'>-->
<!--        <div class='card border-light shadow-sm'>-->
<!--            <div class='card-body'>-->
                <center>
                    <h2><b>LKE - Pengawasan</b></h2>
                </center><br><br>
              <div class="text mb-4">
                <p>Bukti Dukung pada Kejaksaan Negeri {{ $satkernama }}</p>
                <a href="{{ route('lke_was') }}" class="btn btn-secondary btn-sm">
                    ← Kembali
                </a>
            </div>

<div class="card shadow-sm mb-3">
    <table class="table table-bordered">
        <thead class="bg-success text-white">
            <tr>
                <th>No</th>
                <th>Komponen</th>
                <!--<th>Kode Subkomponen</th>-->
                <th>Subkomponen</th>
                <th>Kode Kriteria</th>
                
                <th>Kriteria</th>
                <th>Dokumen Bukti Dukung</th>
                <!--<th>Aksi</th>-->
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($komponen->groupBy('id_komponen') as $idKomponen => $dataKomponen)
                @php
                    $komponenRowspan = $dataKomponen->count();
                    $firstKomponen = $dataKomponen->first();
                @endphp

                @foreach ($dataKomponen->groupBy('id_subkomponen') as $idSub => $dataSub)
                    @php
                        $subRowspan = $dataSub->count();
                        $firstSub = $dataSub->first();
                    @endphp

                    @foreach ($dataSub as $index => $row)
                        <tr>
                            @if ($loop->first && $loop->parent->first)
                                <td rowspan="{{ $komponenRowspan }}">{{ $no++ }}</td>
                                <td rowspan="{{ $komponenRowspan }}">{{ $firstKomponen->nama_komponen }}</td>
                            @endif

                            @if ($loop->first)
                                <!--<td rowspan="{{ $subRowspan }}">{{ $firstSub->id_subkomponen }}</td>-->
                                <td rowspan="{{ $subRowspan }}">{{ $firstSub->nama_subkomponen }}</td>
                            @endif

                            <td>{{ $row->kode }}</td>
                            <td>{{ $row->nama_kriteria }}</td>

                            @php
                                $needs = array_map('trim', explode(';', $row->bukti_pengisian ?? ''));
                                $formats = array_map('trim', explode(';', $row->format_nama_file ?? ''));
                                $uploaded = $buktiDukung[$row->id_kriteria] ?? collect();
                            @endphp

                            <td>
                                <ul>
                                    @foreach ($needs as $i => $need)
                                        @php
                                            $pattern = $formats[$i] ?? '';
                                            $prefix = strtolower(preg_split('/[_\s]/', $pattern)[0] ?? $pattern);
                                            $dok = $uploaded->first(
                                                fn($d) => str_contains(strtolower($d->link_bukti_dukung), $prefix),
                                            );
                                        @endphp

                                        @if ($dok)
                                            <li>
                                                <a href="{{ asset('uploads/repository/' . $idSatker . '/' . $dok->link_bukti_dukung) }}"
                                                    target="_blank">{{ $need }}</a>
                                            </li>
                                        @else
                                            <li class="text-danger">{{ $need }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </td>

                            <!--<td>-->
                            <!--    <button class="btn btn-sm btn-primary btn-open-modal"-->
                            <!--        data-id-kriteria="{{ $row->id_kriteria }}"-->
                            <!--        data-id-komponen="{{ $row->id_komponen }}"-->
                            <!--        data-id-subkomponen="{{ $row->id_subkomponen }}"-->
                            <!--        data-needs="{{ implode(';', $needs) }}"-->
                            <!--        data-formats="{{ implode(';', $formats) }}">-->
                            <!--        Edit-->
                            <!--    </button>-->
                            <!--</td>-->
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Global -->

<div class="modal fade" id="aksiModalGlobal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kelola Dokumen Bukti Dukung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="modalContent"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const modalEl = document.getElementById("aksiModalGlobal");
            const tbody = document.getElementById("modalContent");

            document.querySelectorAll(".btn-open-modal").forEach(btn => {
                btn.addEventListener("click", function() {
                    let needs = this.dataset.needs.split(";");
                    let formats = this.dataset.formats.split(";");
                    let idKriteria = this.dataset.idKriteria;
                    let idKomponen = this.dataset.idKomponen;
                    let idSubkomponen = this.dataset.idSubkomponen;

                    tbody.innerHTML = "";

                    needs.forEach((need, i) => {
                        let pattern = formats[i] ?? "";
                        let row = `
                        <tr>
                            <td>${need}</td>
                            <td>
                                ${
                                    pattern.includes("(perubahan terakhir)")
                                    ? `<button type="button" class="btn btn-sm btn-success btn-verif"
                                                    data-url="{{ route('verifikasi.dokumen') }}"
                                                    data-id-kriteria="${idKriteria}"
                                                    data-id-komponen="${idKomponen}"
                                                    data-id-subkomponen="${idSubkomponen}"
                                                    data-format="${need}">
                                                    Verif
                                                </button>`
                                    : `<form action="{{ route('upload.dokumen') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="id_kriteria" value="${idKriteria}">
                                                    <input type="hidden" name="id_komponen" value="${idKomponen}">
                                                    <input type="hidden" name="id_sub_komponen" value="${idSubkomponen}">
                                                    <input type="file" name="file" class="form-control mb-2" required>
                                                    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                                                </form>`
                                }
                            </td>
                        </tr>
                    `;
                        tbody.insertAdjacentHTML("beforeend", row);
                    });

                    // Re-bind event listener untuk tombol verif setelah isi tabel
                    // Re-bind event listener untuk tombol verif setelah isi tabel
                    tbody.querySelectorAll(".btn-verif").forEach(vbtn => {
                        vbtn.addEventListener("click", function() {
                            let url = this.dataset.url;
                            let idKriteria = this.dataset.idKriteria;
                            let idKomponen = this.dataset.idKomponen;
                            let idSubkomponen = this.dataset.idSubkomponen;
                            let format = this.dataset.format;

                            // Kirim AJAX
                            fetch(url, {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                        "Content-Type": "application/json"
                                    },
                                    body: JSON.stringify({
                                        id_kriteria: idKriteria,
                                        id_komponen: idKomponen,
                                        id_sub_komponen: idSubkomponen,
                                        format: format
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    alert("Verifikasi berhasil!");
                                    console.log(data);

                                    // Hanya update baris terkait (tanpa menutup modal)
                                    vbtn.classList.remove("btn-success");
                                    vbtn.classList.add("btn-secondary");
                                    vbtn.textContent = "Terverifikasi";
                                    vbtn.disabled = true;
                                })

                                .catch(err => {
                                    alert("Gagal verifikasi!");
                                    console.error(err);
                                });
                        });
                    });

                    modalEl.addEventListener('hidden.bs.modal', function() {
                        location.reload();
                    });

                    let modal = new bootstrap.Modal(modalEl);
                    modal.show();
                });
            });
        });
    </script>
@endpush

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
@endsection