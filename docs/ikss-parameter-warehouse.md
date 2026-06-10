# Gudang Parameter IKSS

Parameter pengampu IKSS adalah bahan rinci yang muncul pada narasi, tabel, pembilang, penyebut, dan formula LKJiP. Nilai akhir IKSS hanya merupakan salah satu hasil dari parameter tersebut.

Input utama tersedia pada menu **Pengukuran > Parameter SS / IKSS**. Form menampilkan keterkaitan SS dan IKSS pada setiap kelompok parameter, sementara nilai hasil formula ditampilkan sebagai hasil otomatis.

## Assessment SS dan IKSS

| SS | IKSS | Kelompok parameter utama |
| --- | --- | --- |
| SS 1 | IKSS 1.1 | SKM per pelayanan, target PK, faktor, dan optimalisasi |
| SS 2 | IKSS 2.1 | Pidum, Pidsus, Pidmil/koneksitas, Datun, mediasi penal, denda damai, dan alternatif pemidanaan |
| SS 2 | IKSS 2.2 | Kegiatan dan operasi intelijen penegakan hukum |
| SS 2 | IKSS 2.3 | Penelusuran, perampasan, pemulihan aset, pembayaran denda, dan penyelamatan keuangan negara |
| SS 3 | IKSS 3.1 | Perkara perdata/TUN, pendapat hukum, pendampingan, audit, dan tindakan hukum lain |
| SS 4 | IKSS 4.1 | SKM, IKPA, SAKIP internal, LKE ZI, realisasi anggaran, dan prioritas nasional |
| SS 4 | IKSS 4.2 | Jumlah Jaksa, pelanggaran etika, target PK, faktor, dan optimalisasi |

Kode katalog secara otomatis mengikuti kode IKSS pada master aktif, baik format `IKSS1-1` maupun `1-1`.

Pagu DIPA pada IKSS 4.1 dibaca otomatis dari revisi terbaru `sinori_sakip_dipa`:

- `id_dukman` menjadi Pagu Program Dukungan Manajemen.
- `id_gakyankum` menjadi Pagu Program Penegakan dan Pelayanan Hukum.
- `id_pagu` menjadi Total Pagu.

Apabila total pagu berbeda secara material dari jumlah kedua program, total dihitung dari komponen dan koreksinya dicatat pada metadata nilai. Kolom realisasi kinerja triwulanan pada `sinori_sakip_penetapan` tidak diperlakukan sebagai realisasi anggaran rupiah.

Contoh:

- `skm.nilai_satker` menyimpan rata-rata SKM dan daftar nilai per pelayanan.
- `skm.target_pk` mengambil target PK IKSS 1.1.
- `skm.capaian_terhadap_target` menghitung `rata-rata SKM / target PK x 100%`.
- `pidum.prapenuntutan.spdp_diterima` menjadi penyebut.
- `pidum.prapenuntutan.perkara_diselesaikan` menjadi pembilang.
- `pidum.prapenuntutan.tingkat_keberhasilan` menghitung hasil pembilang dibagi penyebut.

## Struktur Data

| Tabel | Fungsi |
| --- | --- |
| `ikss_parameter_groups` | Mengelompokkan parameter sesuai bagian laporan, tabel, atau formula. |
| `ikss_parameters` | Master parameter yang terhubung ke `indikator_sastra.kode_indikator` melalui `ikss_id`. |
| `ikss_parameter_dependencies` | Sumber dan peran parameter dalam rumus, misalnya pembilang, penyebut, komponen, dan bobot. |
| `ikss_parameter_values` | Nilai per parameter, satker, tahun, triwulan, dan bulan. Nilai manual dan hasil agregasi disimpan di tabel yang sama dengan `source_type` berbeda. |
| `ikss_parameter_value_items` | Baris berulang, misalnya daftar pelayanan SKM, daftar perkara, atau daftar faktor pendukung. |
| `ikss_results` | Hasil IKSS termaterialisasi untuk pembacaan laporan yang cepat. |
| `ikss_calculation_runs` | Audit setiap proses perhitungan dan agregasi wilayah. |
| `lkjip_template_bindings` | Menghubungkan parameter atau grup dengan marker dan tabel pada custom template. |

Nilai dengan `month = 0` adalah ringkasan triwulan. Parameter bulanan tetap disimpan per bulan dan otomatis dibuatkan ringkasan triwulan.
Pada halaman Pelaporan, pilih **Bulan Data Bulanan** untuk mengisi parameter bulanan. Parameter triwulanan tetap memakai `month = 0`.

## Metode Perhitungan

Metode yang tersedia:

- `input`
- `sum`
- `average`
- `weighted_average`
- `ratio`
- `percentage`
- `min`
- `max`
- `latest`

Untuk `ratio` dan `percentage`, dependensi harus memakai peran `numerator` dan `denominator`. Nilai standar dikalikan 100; pengali dapat diubah melalui `formula_config.multiplier`.
Hasil rasio tidak akan dibuat apabila pembilang atau penyebut belum tersedia. Nilai `0` tetap dianggap sebagai nilai yang sah.

Setiap parameter memiliki:

- `calculation_method`: menghitung parameter dari parameter lain pada satu satker.
- `aggregation_method`: menggabungkan nilai Kejari dan Cabjari menjadi nilai Kejati.
- `parameter_role`: `component`, `numerator`, `denominator`, `result`, `context`, atau `narrative`.
- `input_mode`: nilai tunggal, daftar, atau tabel.
- `source_type`: manual, data lama, target PK, sistem, atau hasil formula.

Kejati terlebih dahulu menjumlahkan atau merata-ratakan parameter dasar dari Kejari/Cabjari. Setelah itu rumus Kejati dihitung ulang. Dengan demikian rasio wilayah menggunakan jumlah pembilang dibagi jumlah penyebut, bukan rata-rata persentase anak.

## Alur Otomatis

1. Kejari atau Cabjari menyimpan nilai parameter.
2. Daftar atau baris tabel disimpan pada `ikss_parameter_value_items`.
3. Rumus turunan satker dihitung dan disimpan.
4. Parameter dasar seluruh Kejari dan Cabjari dengan `id_kejati` yang sama digabungkan.
5. Rumus wilayah Kejati dihitung ulang dari hasil agregasi tersebut.
6. Binding template membentuk nilai tunggal, tabel dinamis, dan formula pada dokumen.
7. Hasil akhir IKSS disimpan pada `ikss_results` untuk pembacaan cepat.

Nilai manual dapat dikosongkan kembali dengan mengirim `clear: true`. Nilai berstatus `locked` tidak dapat diubah melalui input Pelaporan. Perubahan nilai yang sebelumnya diverifikasi akan kembali menjadi data input biasa agar tidak menyimpan informasi verifikasi yang sudah tidak berlaku.
Halaman Pelaporan hanya mengirim parameter yang benar-benar diubah oleh operator, sehingga penyimpanan tidak menulis ulang seluruh periode.

Penyimpanan melalui halaman Pengukuran lama juga memicu sinkronisasi satker dan Kejati untuk triwulan terkait.

## API Pelaporan

Semua endpoint memerlukan autentikasi.

| Method | Endpoint | Fungsi |
| --- | --- | --- |
| GET | `/pelaporan/ikss-parameters/catalog` | Daftar parameter sesuai tahun dan level satker. |
| GET | `/pelaporan/ikss-parameters/values?quarter=1` | Nilai parameter satker. |
| POST | `/pelaporan/ikss-parameters/values` | Simpan nilai dan langsung hitung Kejati. |
| POST | `/pelaporan/ikss-parameters/recalculate` | Hitung ulang satker atau wilayah Kejati. |
| GET | `/pelaporan/ikss-parameters/summary?quarter=1` | Ringkasan cepat hasil IKSS. |
| GET | `/pelaporan/ikss-parameters/report-data?quarter=1` | Data yang siap dimasukkan ke custom template. |
| POST | `/pelaporan/ikss-parameters/definitions` | Simpan definisi parameter; khusus admin. |

## Custom Template

Custom template dapat memakai marker:

```text
${param:skm.nilai_satker}
${param:skm.target_pk}
${param:skm.capaian_terhadap_target}
${table:ikss1.skm.services}
${table:ikss1.skm.region}
${table:ikss2.pidum.prapenuntutan.summary}
${table:ikss2.pidum.prapenuntutan.detail}
```

Untuk tabel lama yang belum memiliki marker, binding dapat memiliki `options.anchors`. Sistem mencari teks kepala atau isi tabel lama, lalu mengganti tabel tersebut dengan tabel hasil parameter.

Nilai tunggal pada template lama yang masih menggunakan titik-titik juga dapat memakai anchor:

```json
{
  "anchors": ["Mengacu pada target Perjanjian Kinerja Kepala"],
  "after_text": "sebesar",
  "minimum_dots": 3,
  "prefix": ""
}
```

Sistem mencari paragraf atau tabel yang memuat anchor, lalu mengganti rangkaian titik setelah `after_text`. Marker `${param:kode.parameter}` tetap menjadi pilihan paling presisi untuk custom template baru.

Katalog lengkap dapat dibuat ulang secara idempotent:

```powershell
php artisan ikss:seed-report-catalog
```

## Sinkronisasi Data Lama

Sinkronisasi semua Kejari dan Cabjari pada satu periode:

```powershell
php artisan ikss:import-legacy --year=2026 --quarter=1
```

Sinkronisasi satker tertentu:

```powershell
php artisan ikss:import-legacy --year=2026 --quarter=1 --satker=005017
```

Perintah ini bersifat idempotent: menjalankannya kembali memperbarui data periode yang sama tanpa membuat duplikasi.

## Kinerja

Indeks komposit ditambahkan pada `pengukuran(id_satker, tahun, indikator_id, bulan)` dan `target(id_satker, tahun, indikator_id)`. Pembacaan laporan memakai tabel `ikss_results` yang memiliki satu baris per IKSS, satker, tahun, dan triwulan. Katalog dan ringkasan memakai cache file khusus agar tidak bergantung pada keberadaan tabel cache database.

Pembuatan data laporan memuat seluruh grup binding dalam satu query katalog. Perhitungan Kejati mengambil nilai dasar seluruh Kejari/Cabjari sekaligus, kemudian menghitung ulang formula wilayah. Target PK milik Kejati dipertahankan; rata-rata target anak hanya digunakan apabila target Kejati belum tersedia.
