<!--@extends('layouts.app')-->

<!--@section('content')-->
<!-- Modal -->
<!--<div class ="container">-->
<!--<div class="container-fluid align-items-center">-->
<!--<div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel" aria-hidden="true">-->
<!--  <div class="modal-dialog">-->
<!--    <div class="modal-content">-->
<!--      <div class="modal-header">-->
<!--        <h5 class="modal-title" id="buktiModalLabel">Daftar Nama File Bukti Dukung</h5>-->
<!--        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>-->
<!--      </div>-->
<!--      <div class="modal-body">-->
<!--        @if(isset($bukti_dukung) && count($bukti_dukung))-->
<!--        <table class="table table-bordered mt-4">-->
<!--          <thead class="table-light">-->
<!--            <tr>-->
<!--              <th style="width:40px;">No</th>-->
<!--              <th>Nama Bukti Dukung</th>-->
<!--              <th style="width:150px;">Ketersediaan File</th>-->
<!--            </tr>-->
<!--          </thead>-->
<!--          <tbody>-->
<!--            @foreach($bukti_dukung as $index => $item)-->
<!--              <tr>-->
<!--                <td>{{ $index + 1 }}</td>-->
<!--                <td>{{ $item['nama'] }}</td>-->
<!--                <td class="text-center">-->
<!--                  @if($item['status'] == 'Ada')-->
<!--                    <span class="badge bg-success">Ada</span>-->
<!--                  @else-->
<!--                    <span class="badge bg-danger">upload</span>-->
<!--                  @endif-->
<!--                </td>-->
<!--              </tr>-->
<!--            @endforeach-->
<!--          </tbody>-->
<!--        </table>-->
<!--        @else-->
<!--          <p>Tidak ada file bukti dukung.</p>-->
<!--        @endif-->
<!--      </div>-->
<!--      <div class="modal-footer">-->
<!--        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>-->
<!--      </div>-->
<!--    </div>-->
<!--  </div>-->
<!--</div>-->

<!-- Tombol untuk membuka modal -->
<!--<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buktiModal">-->
<!--  Lihat Nama File Bukti Dukung-->
<!--</button>-->
<!--</div>-->
<!--</div>-->

<!--@endsection-->