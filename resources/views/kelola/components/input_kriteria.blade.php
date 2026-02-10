@extends('layouts.app')
@section('content')
<div class="container">
<h3>Input Kriteria</h3>
    <form method="POST" action="{{ route('kriteria.store') }}">
        @csrf
        <div class="mb-3">
            <label for="id_komponen" class="form-label">Komponen</label>
            <select id="id_komponen" name="id_komponen" class="form-select" required>
                <option value="">Pilih Komponen</option>
                @foreach($komponen as $k)
                    <option value="{{ $k->id }}">{{ $k->komponen }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="id_subkomponen" class="form-label">Subkomponen</label>
            <select id="id_subkomponen" name="id_subkomponen" class="form-select" required>
                <option value="">Pilih Subkomponen</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="range_nilai" class="form-label">Range Nilai</label>
            <input type="text" name="range_nilai" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="bentuk_bukti" class="form-label">Bentuk Bukti</label>
            <input type="text" name="bentuk_bukti" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="bobot" class="form-label">Bobot</label>
            <input type="number" name="bobot" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="kriteria" class="form-label">Kriteria</label>
            <textarea name="kriteria" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>
<script>
document.getElementById('id_komponen').addEventListener('change', function() {
    var komponenId = this.value;
    var subkomponenSelect = document.getElementById('id_subkomponen');
    subkomponenSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('/get-subkomponen/' + komponenId)
        .then(response => response.json())
        .then(data => {
            subkomponenSelect.innerHTML = '<option value="">Pilih Subkomponen</option>';
            data.forEach(function(sub) {
                subkomponenSelect.innerHTML += `<option value="${sub.id}">${sub.subkomponen}</option>`;
            });
        });
});
</script>

@endsection