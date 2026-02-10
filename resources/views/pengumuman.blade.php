@extends('layouts.app')

@section('title', 'Pengumuman')

@section('content')
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card-body">
                    <center>
                        <h2><b>Daftar Pengumuman</b></h2>
                    </center>

                    <!-- Tombol Tambah Pengumuman Berwarna Kuning -->
                    <button class="btn btn-sm btn-yellow mb-4" data-bs-toggle="modal"
                        data-bs-target="#addPengumumanModal">Tambah Pengumuman</button>


                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- List Pengumuman -->
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Isi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pengumuman as $item)
                                <tr>
                                    <td>{{ $item->judul }} <br></td>
                                    <td>{{ $item->isi }}</td>
                                    <td>
                                        <!-- Tombol Edit Berwarna Hijau -->
                                        <a href="{{ route('pengumuman.edit', $item->id) }}"
                                            class="btn btn-success btn-sm">Edit</a>
                                        <form action="{{ route('pengumuman.destroy', $item->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah Pengumuman -->
        <div class="modal fade" id="addPengumumanModal" tabindex="-1" aria-labelledby="addPengumumanModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('pengumuman.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="addPengumumanModalLabel">Tambah Pengumuman</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="judul" class="form-label">Judul Pengumuman</label>
                                <input type="text" class="form-control" id="judul" name="judul" required>
                            </div>
                            <div class="mb-3">
                                <label for="isi" class="form-label">Isi Pengumuman</label>
                                <textarea class="form-control" id="isi" name="isi" rows="4" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
.btn-yellow {
        background-color: #f0bb49; /* Warna kuning */
        color: white;
        border-color: #f0bb49;
    }

    .btn-yellow:hover {
        background-color: #e0a83c; /* Warna kuning gelap saat hover */
        border-color: #e0a83c;
        color: white;
    }

    .btn-yellow:focus {
        box-shadow: 0 0 0 0.2rem rgba(240, 187, 73, 0.5); /* Shadow untuk focus state */
    }
    </style>
