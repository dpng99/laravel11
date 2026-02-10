@extends('layouts.app')

@section('title', 'Edit aturan')

@section('content')
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card border-light shadow-sm" style="background-color: #e3e2e2;">
                    <center>
                        <h2><b>Edit Peraturan</b></h2>
                    </center></div>
                <div class="card-body">
                    <form action="{{ route('aturan.update', $aturan->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
            
                        <div class="form-group">
                            <label for="id_namaproduk">Nama Peraturan</label>
                            <input type="text" class="form-control" id="id_namaproduk" name="id_namaproduk" value="{{ $aturan->id_namaproduk }}" required>
                        </div>
            
                        <div class="form-group">
                            <label for="id_produsen">Pemilik</label>
                            <input type="text" class="form-control" id="id_produsen" name="id_produsen" value="{{ $aturan->id_produsen }}" required>
                        </div>
            
                        <div class="form-group">
                            <label for="id_tahun">Tahun</label>
                            <input type="number" class="form-control" id="id_tahun" name="id_tahun" value="{{ $aturan->id_tahun }}" required>
                        </div>
            
                        <div class="form-group">
                            <label for="file">Upload File Baru (PDF)</label>
                            <input type="file" class="form-control" id="file" name="file">
                        </div>
            <br>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
