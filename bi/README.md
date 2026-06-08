# AKIP Python BI

Menu **Administrator > Business Intelligence** memakai Laravel sebagai data
gateway dan `akip_bi.py` sebagai mesin analisis. Python tidak mengakses database
secara langsung dan hanya menerima snapshot JSON tanpa kredensial.

Analisis yang dihasilkan:

- tren kelengkapan nasional empat tahun;
- skor dan tingkat risiko satker;
- performa serta anomali wilayah;
- peluang perbaikan per jenis dokumen;
- insight dan rekomendasi tindak lanjut otomatis.

## Menjalankan

Python 3 harus tersedia pada mesin PHP. Image Docker aplikasi sudah memasang
`python3`. Untuk instalasi non-Docker, binary dapat diatur melalui `.env`:

```env
BI_PYTHON_BINARY=python
BI_TIMEOUT=60
```

Route `/admin/business-intelligence` dilindungi autentikasi dan pemeriksaan
administrator pada controller.
