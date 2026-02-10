<!--<table class="table table-bordered mt-4">-->
<!--  <thead class="table-light">-->
<!--    <tr>-->
<!--      <th style="width:40px;">No</th>-->
<!--      <th>Nama Bukti Dukung</th>-->
<!--      <th style="width:150px;">Ketersediaan File</th>-->
<!--      <th style="width:100px;">Aksi</th> <!-- Tambahkan kolom Aksi -->-->
<!--    </tr>-->
<!--  </thead>-->
<!--  <tbody>-->
<!--    @foreach($bukti_dukung as $index => $item)-->
<!--      <tr>-->
<!--        <td>{{ $index + 1 }}</td>-->
<!--        <td>{{ $item['nama'] }}</td>-->
<!--        <td class="text-center">-->
<!--          @if($item['status'] == 'Ada')-->
<!--            <span class="badge bg-success">Ada</span>-->
<!--          @else-->
<!--            <span class="badge bg-danger">Upload</span>-->
<!--          @endif-->
<!--        </td>-->
<!--        <td class="text-center">-->
<!--          @if($item['file'])-->
<!--            <a href="{{ asset($item['file']) }}" target="_blank" class="btn btn-sm btn-primary">-->
<!--              Lihat-->
<!--            </a>-->
<!--          @else-->
<!--            <span class="text-muted">-</span>-->
<!--          @endif-->
<!--        </td>-->
<!--      </tr>-->
<!--    @endforeach-->
<!--  </tbody>-->
<!--</table>-->
