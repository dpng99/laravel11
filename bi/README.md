# AKIP Python BI

Menu **Administrator > Business Intelligence** memakai Laravel sebagai data
gateway dan `akip_bi.py` sebagai mesin analisis. Python tidak mengakses database
secara langsung dan hanya menerima snapshot JSON tanpa kredensial.

Analisis yang dihasilkan:

- tren capaian terhadap target nasional empat tahun;
- skor dan tingkat risiko satker berdasarkan SS/IKSS;
- performa serta anomali wilayah;
- analisis target, capaian, dan capaian terhadap target per IKSS;
- performa Sasaran Strategis (SS);
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
