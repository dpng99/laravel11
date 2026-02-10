@extends('layouts.app')

@section('title', 'Sumber Aturan')

@section('content')
    @php
        $levelSakip = session('id_sakip_level', 0);
    @endphp
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card border-light shadow-sm" style="background-color: #e6bf3e;">
                    <center>
                        <h2><b>Sumber Aturan</b></h2>
                    </center>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <!-- Tombol Tambah Peraturan -->
                    @if ($levelSakip == 99)
                        <div class="mb-3">
                            <a href="{{ route('aturan.create') }}" class="btn btn-sm btn-yellow">Tambah Peraturan</a>
                        </div>
                    @endif
                    <table class="table table-bordered table-striped">
                        <thead class="table-warning">
                            <tr>
                                <th>No</th> <!-- Kolom nomor -->
                                <th style="width: 70%;">Nama Peraturan</th>
                                <th>Pemilik</th>
                                    <th>Tahun</th>
                                @if ($levelSakip == 99)
                                <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($aturan as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if (!empty($item->id_filename))
                                            <a href="{{ asset('uploads/peraturan/' . $item->id_filename) }}" target="_blank"
                                                style="text-decoration: none; color: inherit;">
                                                {{ $item->id_namaproduk }}
                                            </a>
                                        @else
                                            {{ $item->id_namaproduk }}
                                        @endif
                                    </td>

                                    <td>{{ $item->id_produsen }}</td>
                                    <td>{{ $item->id_tahun }}</td>
                                    @if ($levelSakip == 99)
                                        <td> <!-- Tombol Edit -->
                                            <a href="{{ route('aturan.edit', $item->id) }}"
                                                class="btn btn-success btn-sm">Edit</a>
                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('aturan.destroy', $item->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus peraturan ini?')">Hapus</button>
                                    @endif
                                    </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .table {
            background-color: white;
            /* Warna dasar tabel */
        }

        .table-warning {
            background-color: #f0bb49;
            /* Warna kuning untuk header tabel */
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
            /* Warna putih untuk baris genap */
        }

        .table-striped tbody tr:hover {
            background-color: #e2e6ea;
            /* Warna saat hover */
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-yellow {
            background-color: #f0bb49;
            /* Warna kuning */
            color: white;
            border-color: #f0bb49;
        }

        .btn-yellow:hover {
            background-color: #e0a83c;
            /* Warna kuning gelap saat hover */
            border-color: #e0a83c;
            color: white;
        }

        .btn-yellow:focus {
            box-shadow: 0 0 0 0.2rem rgba(240, 187, 73, 0.5);
            /* Shadow untuk focus state */
        }
    </style>
@endsection
