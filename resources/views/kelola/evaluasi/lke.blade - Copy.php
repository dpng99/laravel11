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
                <th>Aksi</th>
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
                                <td rowspan="{{ $subRowspan }}">{{ $firstSub->nama_subkomponen }}</td>
                            @endif

                            <td>{{ $row->id_kriteria }}</td>
                            <td>{{ $row->nama_kriteria }}</td>

                            @php
                                // kebutuhan dokumen & format
                                $needs = array_map('trim', explode(';', $row->bukti_pengisian ?? ''));
                                $formats = array_filter(array_map('trim', explode(';', $row->format_nama_file ?? '')));
                                $uploaded = $buktiDukung[$row->id_kriteria] ?? collect();

                                // cek tombol
                                $butuhVerif = false;
                                $butuhUpload = false;

                                foreach ($formats as $f) {
                                    if ($f === '') {
                                        continue;
                                    }
                                    if (stripos($f, '(perubahan terakhir)') !== false) {
                                        $butuhVerif = true;
                                    } else {
                                        $butuhUpload = true;
                                    }
                                }
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

                            @php
                                $formats = array_filter(array_map('trim', explode(';', $row->format_nama_file ?? '')));
                                $uploadFormats = collect($formats)
                                    ->filter(fn($f) => stripos($f, '(perubahan terakhir)') === false)
                                    ->values();
                                $verifFormats = collect($formats)
                                    ->filter(fn($f) => stripos($f, '(perubahan terakhir)') !== false)
                                    ->values();
                            @endphp

                            <td>
                                @if ($verifFormats->isNotEmpty())
                                    <button class="btn btn-sm btn-success btn-verif"
                                        data-id-kriteria="{{ $row->id_kriteria }}"
                                        data-id-komponen="{{ $row->id_komponen }}"
                                        data-id-subkomponen="{{ $row->id_subkomponen }}"
                                        data-formats='@json($verifFormats)'>
                                        Verif
                                    </button>
                                @endif

                                @if ($uploadFormats->isNotEmpty())
                                    <button class="btn btn-sm btn-primary btn-upload"
                                        data-id-kriteria="{{ $row->id_kriteria }}"
                                        data-id-komponen="{{ $row->id_komponen }}"
                                        data-id-subkomponen="{{ $row->id_subkomponen }}"
                                        data-formats='@json($uploadFormats)'>
                                        Upload
                                    </button>
                                @endif
                            </td>

                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>

    </table>
</div>

<!-- Modal Global -->

<!-- Modal Verifikasi -->
<div class="modal fade" id="verifModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verifikasi Dokumen</h5>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tbody id="verifContent"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('upload.dokumen') }}" enctype="multipart/form-data"
            class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Upload Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- akan diisi dinamis lewat JS -->
            </div>
            <div class="modal-footer">
                <input type="hidden" name="id_kriteria">
                <input type="hidden" name="id_komponen">
                <input type="hidden" name="id_subkomponen">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tombol Verif
            document.querySelectorAll('.btn-verif').forEach(btn => {
                btn.addEventListener('click', function() {
                    let formats = JSON.parse(this.dataset.formats);
                    let data = {
                        id_kriteria: this.dataset.idKriteria,
                        id_komponen: this.dataset.idKomponen,
                        id_subkomponen: this.dataset.idSubkomponen,
                        formats: formats
                    };

                    fetch("{{ route('verifikasi.dokumen') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                alert('Verifikasi berhasil');
                                location.reload();
                            } else {
                                alert('Verifikasi gagal');
                            }
                        });

                });
            });

            // Tombol Upload
            document.querySelectorAll('.btn-upload').forEach(btn => {
                btn.addEventListener('click', function() {
                    let formats = JSON.parse(this.dataset.formats);
                    let modalBody = document.querySelector('#uploadModal .modal-body');
                    modalBody.innerHTML = ''; // clear

                    formats.forEach((f, i) => {
                        if (!f.includes('(perubahan terakhir)')) {
                            modalBody.innerHTML += `
                        <div class="mb-2">
                            <label>${f}</label>
                            <input type="file" name="files[]" class="form-control" data-format="${f}">
                        </div>`;
                        }
                    });

                    // simpan id ke form
                    document.querySelector('#uploadModal input[name=id_kriteria]').value = this
                        .dataset.idKriteria;
                    document.querySelector('#uploadModal input[name=id_komponen]').value = this
                        .dataset.idKomponen;
                    document.querySelector('#uploadModal input[name=id_subkomponen]').value = this
                        .dataset.idSubkomponen;

                    // buka modal
                    new bootstrap.Modal(document.getElementById('uploadModal')).show();
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
