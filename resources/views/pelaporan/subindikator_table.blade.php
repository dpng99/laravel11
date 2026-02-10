@foreach ($indikators as $indikator)
    <div class="mb-4 p-3 border rounded shadow-sm">
        <strong>{{ $indikator->indikator_nama }}</strong>
        <table class="table table-bordered align-middle mt-3">
            <thead class="text-center bg-warning-subtle">
                <tr>
                    <th>Jumlah Ditangani</th>
                    <th>Jumlah Diselesaikan</th>
                    <th>Persentase Penyelesaian</th>
                    <th>Target PK TW</th>
                    <th>Capaian Target PK TW</th>
                    <th>Trend Capaian Kinerja</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <tr>
                    <td><input type="number" class="form-control text-center" value="{{ $indikator->total_ditangani }}"
                            readonly></td>
                    <td><input type="number" class="form-control text-center" value="{{ $indikator->total_diselesaikan }}"
                            readonly></td>
                    <td><input type="text" class="form-control text-center"
                            value="{{ number_format($indikator->persentase, 2) }}%" readonly></td>
                    <td><input type="text" class="form-control text-center"
                            value="{{ number_format($indikator->target_pk, 2) }}%" readonly></td>
                    <td><input type="text" class="form-control text-center"
                            value="{{ number_format($indikator->capaian_pk, 2) }}%" readonly></td>
                    <textarea class="form-control" rows="2" readonly>{{ $indikator->faktor }}</textarea>
                    <textarea class="form-control" rows="2" readonly>{{ $indikator->langkah_optimalisasi }}</textarea>
                    <td>
                        <select class="form-select">
                            <option>Naik</option>
                            <option>Turun</option>
                            <option>Tetap</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="mb-2">
            <label><strong>Faktor-Faktor yang mempengaruhi capaian kinerja tersebut:</strong></label>
            <textarea class="form-control" rows="2" placeholder="Tulis faktor di sini..." readonly></textarea>
        </div>

        <div class="mb-2">
            <label><strong>Upaya optimalisasi kinerja yang akan/telah dilaksanakan:</strong></label>
            <textarea class="form-control" rows="2" placeholder="Tulis upaya di sini..." readonly></textarea>
        </div>
    </div>
@endforeach
