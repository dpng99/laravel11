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
                    <h2><b>Bukti Dukung LKE AKIP Internal Tahun 2025</b></h2>
                </center><br>
                <br>
                <p class="text-justify">Halaman ini digunakan untuk melihat dokumen/bukti dukung setiap satuan kerja sebagaimana tercantum pada Lembar Kerja Evaluasi (LKE) AKIP Tahun 2025.  Adapun untuk memberikan nilai akhir evaluasi AKIP setiap sateker tetap menggunakan LKE yg telah disampaikan oleh Bidang Pembinaan (format excel)</p>
            <table class="table table-bordered mt-4">
            <thead class="table-light">
            <tr>
            <th style="width:40px;">No</th>
            <th>Nama Satuan Kerja</th>
            <th style="width:150px;">Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach($list_kejari as $index => $kejari)
                <tr>
                    <td>{{ $index + 1 }}</td>
                   <td>{{ $kejari->satkernama }}</td>
                    <td>
                    <a href="{{ route('lke_was_list', $kejari->id_satker) }}" class="btn btn-info btn-sm">Detail</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
            </table>

            </div>
        </div>
</div>
@endsection