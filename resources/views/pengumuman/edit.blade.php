@extends('layouts.app')

@section('title', 'Edit Pengumuman')

@section('content')
    <div class="content" id="content">
        <div class="container-fluid">
            <div class="card border-light shadow-sm">
                <div class="card-body">
                    <center>
                        <h2><b>Edit Pengumuman</b></h2>
                    </center><br><br>

                    <form action="{{ route('pengumuman.update', $pengumuman->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Pengumuman</label>
                            <input type="text" class="form-control" id="judul" name="judul" value="{{ $pengumuman->judul }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="isi" class="form-label">Isi Pengumuman</label>
                            <textarea class="form-control" id="isi" name="isi" rows="4" required>{{ $pengumuman->isi }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
