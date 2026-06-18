# Rencana Implementasi: Rekonstruksi Pengukuran IKSS Berdasarkan Template LKjIP

Berdasarkan telaah pada dokumen template `lkjip-kejati.docx` dan `lkjip-kejari-cabjari.docx`, serta struktur yang tercermin pada `IkssReportCatalogService`, berikut adalah peta rekonstruksi hierarki pengukuran kinerja secara utuh beserta rumusnya.

## Peta Struktur dan Rumus Pengukuran (SS → IKSS → Komponen)

### SS 1. Terwujudnya Penegakan Hukum yang Memenuhi Rasa Keadilan Masyarakat
#### IKSS 1.1 Indeks Persepsi Publik terhadap Citra Kejaksaan RI
*   **Formula Capaian**: `Indeks Persepsi (Nilai IKSS 1.1) = Rata-rata SKM seluruh jenis pelayanan`
*   **Komponen Input**:
    *   `skm.nilai_satker`: Daftar Nilai Survei Kepuasan Masyarakat (SKM) per jenis pelayanan (Tabel: Nama Pelayanan, Nilai).

### SS 2. Terwujudnya Penegakan Hukum yang Profesional dan Proporsional
#### IKSS 2.1 Persentase Peningkatan Pengendalian Perkara
*   **Formula Capaian**: `(K1 + K2 + K3) / 3`
*   **Komponen Utama**:
    *   **K1: Tingkat Keberhasilan Penanganan Perkara (Rata-rata 13 Indikator)**
        *   `Pidum`: Rata-rata tingkat keberhasilan Prapenuntutan (diselesaikan/SPDP), Penuntutan (diselesaikan/P16A), dan Eksekusi (dieksekusi/inkracht).
        *   `Pidsus Korupsi`: Rata-rata 5 tahap (Lid, Dik, PraTut, Tut, Eksekusi).
        *   `Pidsus Perpajakan`: Rata-rata 3 tahap (PraTut, Tut, Eksekusi).
        *   `Pidsus Kepabeanan`: Rata-rata 3 tahap (PraTut, Tut, Eksekusi).
        *   `Pidsus Cukai`: Rata-rata 3 tahap (PraTut, Tut, Eksekusi).
        *   `Pidsus Perekonomian Negara`: Rata-rata 5 tahap.
        *   `Pidmil Koneksitas`: Rata-rata 4 tahap (Lid, Dik, PraTut, Eksekusi).
        *   `Datun Penegakan`: Gugatan dikabulkan / Gugatan ditangani.
    *   **K2: Persentase penanganan melalui mediasi penal, diskresi penuntutan, dan denda damai**
        *   `Rata-rata dari`:
            *   Persentase Pidum Restorative Justice (diselesaikan RJ / memenuhi syarat RJ).
            *   Persentase Pidsus Denda Damai (diselesaikan denda / memenuhi syarat denda).
    *   **K3: Persentase penuntutan melalui alternatif pemidanaan**
        *   `Formula`: Perkara dituntut alternatif pemidanaan / Perkara memenuhi syarat alternatif.

#### IKSS 2.2 Tingkat Keberhasilan Kegiatan dan Operasi Intelijen Penegakan Hukum
*   **Formula Capaian**: `(Jumlah LID/PAM/GAL berhasil dilaksanakan) / (Jumlah seluruh LID/PAM/GAL dilaksanakan) * 100%`
*   ### 3. Halaman Pengukuran Utama (Take-over Route Lama)

Sesuai permintaan, sistem **tidak akan** membuat menu "Pengukuran IKSS" yang terpisah. Antarmuka Master-Detail berjenjang ini akan langsung menggantikan halaman Pengukuran yang lama.

#### [MODIFY] Route di web.php
Semua route di bawah `Route::controller(PengukuranController::class)` akan ditinjau. Route utama `/pengukuran` akan diarahkan ke antarmuka React/Inertia yang baru (menggunakan `PengukuranIkssController` atau me-rewrite method `index` di `PengukuranController`).

#### [NEW] resources/js/Pages/Pengukuran/IkssParameters.vue (atau Index.jsx)
Form pengisian parameter dengan tampilan hierarki Master-Detail:
- **Level navigasi 1**: Tab SS (SS 1 - SS 4)
- **Level navigasi 2**: Inner Sidebar (Tree View) untuk IKSS (IKSS 2.1, 2.2, dst).
- **Tampilan form dinamis** untuk setiap tahapan (Prapenuntutan, Penuntutan, Eksekusi).
- **Progress bar** per IKSS: berapa % parameter wajib sudah diisi
- **Tombol Simpan** per grup + perhitungan otomatis secara *realtime* di layar.

#### IKSS 2.3 Tingkat Keberhasilan Pemulihan Aset Negara
*   **Formula Capaian**: `Rata-rata keberhasilan Penelusuran, Perampasan, dan Pemulihan Aset`
*   **Komponen**:
    *   `Keberhasilan Penelusuran`: Nilai aset diserahkan ke pemohon / Nilai taksiran aset penelusuran.
    *   `Keberhasilan Perampasan`: Nilai aset dirampas berdasar BA penerimaan / Nilai aset berhasil dieksekusi.
    *   `Keberhasilan Pemulihan`: Nilai aset diselesaikan via lelang, dll / Nilai aset dinilai.
    *   *Konteks tambahan*: Data Penyelamatan/Pembayaran denda Korupsi, Perpajakan, Kepabeanan, Cukai, dan Penyelamatan Keuangan Negara Litigasi/Non-Litigasi.

### SS 3. Terwujudnya Perlindungan Hukum dan Kepastian Hukum
#### IKSS 3.1 Tingkat Efektivitas Pelaksanaan Kewenangan Advocaat Generaal
*   **Formula Capaian**: `Rata-rata(P1, P2)`
*   **Komponen**:
    *   **P1: Tingkat Keberhasilan Penanganan Perkara Perdata dan TUN**
        *   Rata-rata persentase penyelesaian Perdata Litigasi, Perdata Non-Litigasi, dan TUN Litigasi.
    *   **P2: Tingkat Penjaminan Kualitas Pengajuan Pertimbangan Hukum**
        *   Rata-rata persentase penyelesaian Pendapat Hukum (LO), Pendampingan Hukum + Audit Hukum, dan Tindakan Hukum Lain.

### SS 4. Terwujudnya Organisasi yang Bersih, Efektif, Efisien dan Melayani
#### IKSS 4.1 Indeks Reformasi Birokrasi Kejaksaan RI
*   **Formula Capaian**: `Rata-rata(Nilai SKM, Nilai IKPA, Hasil Evaluasi SAKIP, LKE ZI)`
*   **Komponen Input (masing-masing skala 0-100)**:
    *   `rb.skm`: Diambil dari rata-rata SKM (IKSS 1.1).
    *   `rb.ikpa`: Rata-rata IKPA (terdiri dari Revisi DIPA, Deviasi, Penyerapan, dll).
    *   `rb.sakip`: Rata-rata nilai perencanaan, pengukuran, pelaporan, evaluasi, akuntabilitas.
    *   `rb.lke_zi`: Nilai LKE Zona Integritas oleh TPD.
    *   *Konteks*: Pagu vs Realisasi Anggaran DIPA.

#### IKSS 4.2 Tingkat Penerapan Etika Profesi Jaksa
*   **Formula Capaian**: `(Jumlah Jaksa yang TIDAK melakukan pelanggaran etika) / (Total Jaksa) * 100%`

---

## Pemetaan Satker: Kejati (Level 2) vs Kejari/Cabjari (Level 3/4)

Terdapat perbedaan komposisi struktur kinerja berdasarkan level Satuan Kerja (Satker), khususnya pada elemen **Pidana Militer (Koneksitas)** di bawah IKSS 2.1 (K1).

### 1. Struktur Kejari dan Cabjari (Level 3 dan 4)
*   **Kondisi Baku**: Di tingkat Kejaksaan Negeri, **tidak ada** Asisten Pidana Militer. Oleh karena itu, form Pengukuran IKSS 2.1 tidak boleh memuat komponen Pidmil.
*   **Kalkulasi K1 (Tingkat Keberhasilan)**: Rata-rata akan dihitung dari **9 indikator** saja, yaitu:
    *   Pidum (3 indikator tahapan)
    *   Pidsus (5 indikator per program)
    *   Datun (1 indikator penegakan hukum)
*   **Tindakan Sistem**: UI secara otomatis akan menyembunyikan navigasi "Pidmil" jika user yang login berada di level 3 atau 4. Sistem `IkssParameterService` di backend sudah didesain menghitung rata-rata secara proporsional berdasarkan data yang relevan (9 komponen).

### 2. Struktur Kejati (Level 2)
*   **Kondisi Dinamis**: Di tingkat Kejaksaan Tinggi, keberadaan Asisten Pidana Militer bergantung pada tipologi Kejati (ada yang memiliki, ada yang tidak).
*   **Kalkulasi K1 (Tingkat Keberhasilan)**:
    *   **Kejati dengan Pidmil**: Rata-rata dihitung dari **13 indikator** (9 indikator + 4 indikator tahapan Pidmil).
    *   **Kejati tanpa Pidmil**: Rata-rata dihitung dari **9 indikator**.
*   **Tindakan Sistem**: UI akan memunculkan opsi form "Pidmil" untuk Kejati. Namun, form Pidmil ini sifatnya *Optional/Toggleable*. Jika Kejati tersebut tidak memiliki Pidmil, mereka dapat melewati form ini, dan backend tidak akan menghitungnya sebagai faktor pembagi rata-rata K1.

### 3. Agregasi Regional Kejati
Selain mengisi datanya sendiri, Kejati juga berfungsi sebagai **agregator** (roll-up) bagi data Kejari di bawahnya (seperti nilai IKPA wilayah, LKE ZI wilayah, SKM Regional di IKSS 1.1).

---

## Rincian Data Parameter: K1 (IKSS 2.1) dan IKSS 3.1

Untuk menggambarkan sedalam apa form yang harus diisi, berikut penjabaran detail parameter yang menjadi dasar kalkulasi.

### Rincian K1 (IKSS 2.1) - Tingkat Keberhasilan Penanganan Perkara

#### 1. Bidang Tindak Pidana Umum (Pidum)
Terbagi menjadi 3 Tahapan:
*   **Prapenuntutan** (Rumus: Perkara Diselesaikan / SPDP Diterima)
    *   *Penyebut*: Jumlah SPDP yang diterima dan diterbitkan P-16.
    *   *Pembilang* (Perkara Diselesaikan), yang rinciannya terdiri dari:
        *   Penyerahan tersangka dan barang bukti (Tahap II)
        *   SPDP dikembalikan (melebihi 7 hari)
        *   SPDP tanpa berkas dikembalikan
        *   SPDP & berkas dikembalikan (melebihi 30 hari)
        *   Berkas dihentikan penyidikannya.
*   **Penuntutan** (Rumus: Perkara Diselesaikan / Surat P-16A)
    *   *Penyebut*: Jumlah Surat P-16A.
    *   *Pembilang* (Perkara Diselesaikan), yang rinciannya terdiri dari:
        *   Dihentikan melalui Restorative Justice
        *   Dihentikan melalui Diversi (Perkara Anak)
        *   Dihentikan alasan sah lain
        *   Dilimpahkan ke Pengadilan.
*   **Eksekusi** (Rumus: Dieksekusi / Inkracht)
    *   *Penyebut*: Jumlah terpidana putusan BHT (P-48).
    *   *Pembilang*: Jumlah terpidana yang dieksekusi.

#### 2. Bidang Tindak Pidana Khusus (Pidsus)
Dipisah menjadi 5 Program: **Korupsi**, **Perpajakan**, **Kepabeanan**, **Cukai**, dan **Kerugian Perekonomian Negara**. Masing-masing program memiliki tahapan dengan data:
*   **Penyelidikan** (Khusus Korupsi & Perekonomian Negara): Diselesaikan vs Diterima.
*   **Penyidikan** (Rumus: Diselesaikan / Diterima)
    *   Rincian Penyelesaian: Dihentikan kepentingan umum, Dilanjutkan Tahap II, Dilimpahkan instansi lain.
*   **Prapenuntutan** (Rumus: Diselesaikan / SPDP Ditangani)
    *   Rincian Penyelesaian: Dilanjutkan Tahap II, Pengembalian SPDP.
*   **Penuntutan** (Rumus: Diselesaikan / Ditangani Tahap Penuntutan)
    *   Rincian Penyelesaian: Dilimpahkan ke Pengadilan, Dihentikan Penuntutannya.
*   **Eksekusi** (Rumus: Dieksekusi / Putusan BHT)

#### 3. Bidang Pidana Militer (Koneksitas)
Mencakup 4 Tahapan (Penyelidikan, Penyidikan, Prapenuntutan, Eksekusi). Masing-masing meminta data rasio sederhana (Diselesaikan dibagi Ditangani pada tiap tahap).

#### 4. Bidang Perdata dan Tata Usaha Negara (Datun - Khusus Penegakan Hukum)
*   **Penegakan Hukum** (Rumus: Gugatan Dikabulkan / Gugatan Penegakan Hukum).

---

### Rincian IKSS 3.1 - Kewenangan Advocaat Generaal (SS3)

Pada IKSS 3.1, capaian dihitung dari rata-rata **P1** dan **P2**.

#### P1: Tingkat Keberhasilan Penanganan Perkara Perdata & TUN (Litigasi / Non-Litigasi)
Dihitung dari rata-rata persentase 3 sub-indikator berikut:
1.  **Perdata Litigasi**
    *   *Penyebut*: Permasalahan perdata dimohonkan (SKK Litigasi masuk).
    *   *Pembilang*: Perkara yang berjalan di pengadilan (SKK Substitusi).
2.  **Perdata Non-Litigasi**
    *   *Penyebut*: Permasalahan ditangani (SKK Non-Litigasi).
    *   *Pembilang*: Diselesaikan (Laporan Akhir).
3.  **Tata Usaha Negara (TUN) Litigasi**
    *   *Penyebut*: Perkara TUN yang ditangani (SKK TUN Litigasi).
    *   *Pembilang*: Perkara TUN diputus oleh pengadilan.

#### P2: Tingkat Penjaminan Kualitas Pengajuan Pertimbangan Hukum (Layanan Hukum)
Dihitung dari rata-rata persentase 4 sub-indikator, yang masing-masing rumusnya adalah **Jumlah Diselesaikan (Terbit Dokumen/Laporan) dibagi Jumlah Permohonan Disetujui (Setelah Telaah)**:
1.  **Pendapat Hukum (Legal Opinion)**
2.  **Pendampingan Hukum (Legal Assistance)** *(Dirata-rata dengan Audit sebelum masuk ke rata-rata P2 utama)*
3.  **Audit Hukum (Legal Audit)**
4.  **Tindakan Hukum Lain**

---

## User Review Required

> [!WARNING]
> **Data Master & Kalkulasi Bertingkat**
> Karena ada banyak sekali parameter (237 parameter di 34 grup), menyajikan semua dalam satu halaman UI akan membuat aplikasi sangat lambat dan membingungkan pengguna. Perlu persetujuan terkait desain UX (User Experience).

> [!IMPORTANT]
> **Integrasi CMS vs Input Manual**
> Dalam skema ideal, data seperti "Jumlah P-16A", "Perkara Inkracht", dll. ditarik otomatis dari API CMS Pidum/Pidsus/Datun. Jika fitur ini belum tersedia di sistem pusat, form ini harus mendukung **input manual secara keseluruhan** namun harus didesain agar strukturnya siap menerima data via cron job/API kelak (mode read-only untuk field yang ditarik sistem).

## Open Questions

> [!CAUTION]
> **1. Metode Pengisian UI:** Apakah setiap IKSS perlu dipisah per halaman/tab, atau cukup menggunakan Accordion (kolaps-buka) dalam 1 halaman seperti yang telah saya buat di versi awal?
> **2. Kewenangan Pengisian:** Untuk data seperti *LKE ZI* atau *SAKIP Internal*, apakah Kejari yang menginput sendiri, atau otomatis turun dari evaluasi Kejati/Pusat?

---

## Rencana Implementasi Perbaikan

### 1. Penguatan UI/UX untuk Hierarki Dalam (Deep Nesting)
Mengingat IKSS 2.1 (K1, K2, K3) dan IKSS 3.1 (P1, P2) memiliki banyak anak dan sub-anak, menggunakan sistem *Accordion* datar akan membuat halaman sangat panjang dan membingungkan. 

Solusi UI/UX yang akan diterapkan:
- **Master-Detail Layout dengan Inner Sidebar**: 
  - Bagian atas tetap berupa **Tab Sasaran Strategis (SS 1 - SS 4)**.
  - Saat memilih Tab "SS 2", di sisi kiri akan muncul **Menu Navigasi Vertikal (Inner Sidebar)** yang berisi *Tree View*.
  - *Tree View* ini bisa di-expand/collapse (Misal: klik `IKSS 2.1` -> muncul `Pidum`, `Pidsus`, `Pidmil`, `Datun`, `K2`, `K3`).
  - Saat `Pidum` diklik, area konten sebelah kanan hanya akan menampilkan form input khusus untuk Pidum (Prapenuntutan, Penuntutan, Eksekusi).
  - Dengan ini, pengguna fokus hanya pada formulir yang sedang diisi tanpa terganggu tabel lain.

- **Tampilan Berjenjang (Card within Card)**:
  - Di dalam form Pidum (area kanan), akan ada kelompok (Card) untuk masing-masing tahap (Prapenuntutan, Penuntutan). 
  - Setiap kelompok memiliki baris Input, baris Anak/Item (jika ada), dan di bawahnya terdapat baris hasil Formula secara otomatis.

### 2. Form Tabel Dinamis & Agregasi Otomatis
- Untuk data bertipe tabel (seperti SKM per layanan, atau Sub-item Penyerahan Tersangka & BB), form harus bisa `Add Row` / `Remove Row` sebelum disimpan.

### 3. Engine Kalkulasi (Auto-Compute di Frontend & Backend)
- **Frontend Realtime Calculation**: Menambahkan logika di React agar saat user mengetik angka "Jumlah Gugatan Dikabulkan", field "Persentase" otomatis ter-update di UI tanpa harus klik "Hitung Ulang" terlebih dahulu (memberikan feedback instan).
- **Backend Validation**: Memastikan `IkssParameterService::recalculateSatker` mengeksekusi Dependency Tree (154 dependensi) secara bottom-up sesuai hierarki yang didefinisikan.

### 4. Pembangunan Halaman Laporan / Cetak
- Mempersiapkan endpoint dan UI untuk eksport form pengukuran ini ke format Word (mengisi template `lkjip-kejari-cabjari.docx` secara otomatis berdasarkan data bulan/triwulan yang dipilih).

## Verification Plan

### Manual Verification
1. Buka halaman Pengukuran IKSS, pastikan terdapat 4 Tab SS.
2. Buka Tab SS 2, buka Akordion IKSS 2.1.
3. Input data Pidum (Prapenuntutan, Penuntutan, Eksekusi).
4. Pastikan form perhitungan rasio bekerja secara instan di UI.
5. Klik Simpan, lalu pastikan Ringkasan Capaian IKSS ter-update menjadi (Nilai_Input / Target) * 100%.
