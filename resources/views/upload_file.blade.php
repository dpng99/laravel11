@extends('layouts.app')
@section('content')
<div class="container">
    <div class="card ">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Upload Bukti Dukung</h5>
        </div>
        <div class="card-body">
            
            {{-- Notifikasi sukses / error --}}
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Pilihan bukti dukung --}}
                <div class="mb-3">
                        <label for="id_bukti" class="form-label">Pilih Bukti Dukung</label>
                        <select id="id_bukti" name="id_bukti" class="form-select" required>
                            <option value="">-- Pilih Bukti Dukung --</option>
                            @foreach($input as $item)
                                <option value="{{ (int) $item->id }}" data-kode="{{ $item->kode ?? '' }}">
                                    {{ $item->dokumen }}
                                </option>
                            @endforeach
                        </select>

                    </div>
                    <div class="form-group" id="tw-group" style="display:none;">
                        <label for="tw">Triwulan</label>
                        <select name="tw" id="tw" class="form-control">
                            <option value="">-- Pilih TW --</option>
                            <option value="TW 1">TW I</option>
                            <option value="TW 2">TW II</option>
                            <option value="TW 3">TW III</option>
                            <option value="TW 4">TW IV</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label">Pilih File</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Unggah</button>
                    <div class="mb-3">
                        <label class="form-label">File yang sudah diunggah</label>
                        <div id="uploaded-files" class="border rounded p-2" style="min-height:50px;">
                            <em>Pilih bukti dukung untuk melihat file</em>
                        </div>
                    </div>
                    @push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const idBuktiSelect = document.getElementById('id_bukti');
  const twSelect = document.getElementById('tw');
  const uploadedFilesDiv = document.getElementById('uploaded-files');

  function loadFiles() {
    const raw = idBuktiSelect.value;
    console.log('loadFiles raw id_bukti =>', raw);
    const idBukti = Number(raw); // safer parse
    if (!idBukti) {
      uploadedFilesDiv.innerHTML = "<em>Pilih bukti dukung untuk melihat file</em>";
      return;
    }

    const tw = twSelect ? twSelect.value : '';
    // buat URL robust (hindari placeholder yang gagal diganti)
    const base = "{{ url('/upload/files') }}"; // -> /upload/files
    const url = base + '/' + encodeURIComponent(idBukti) + (tw ? '?tw=' + encodeURIComponent(tw) : '');
    console.log('fetch URL ->', url);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(res => {
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
      })
      .then(data => {
        console.log('files response:', data);
        if (!data || data.length === 0) {
          uploadedFilesDiv.innerHTML = "<em>Belum ada file diunggah</em>";
          return;
        }
        let html = "<table class='table table-sm table-bordered'><thead><tr><th>No</th><th>Nama File</th><th>Triwulan</th><th>Tanggal Upload</th></tr></thead><tbody>";
        data.forEach((f, i) => {
          const filename = f.id_filename || f.filename || f.original_name || 'file.pdf';
          const satker = f.id_satker || f.id_satker;
          const link = "/uploads/repository/" + (satker || '') + "/" + filename;
          html += `<tr>
                    <td>${i+1}</td>
                    <td><a href="${link}" target="_blank">${filename}</a></td>
                    <td>${f.id_triwulan ?? f.triwulan ?? '-'}</td>
                    <td>${f.id_tglupload ?? f.created_at ?? '-'}</td>
                  </tr>`;
        });
        html += "</tbody></table>";
        uploadedFilesDiv.innerHTML = html;
      })
      .catch(err => {
        console.error(err);
        uploadedFilesDiv.innerHTML = "<span class='text-danger'>Gagal memuat data</span>";
      });
  }

  idBuktiSelect.addEventListener('change', loadFiles);
  if (twSelect) twSelect.addEventListener('change', loadFiles);
});
</script>
@endpush


            </form>

            <hr>
        </div>
    </div>
</div>
@endsection
