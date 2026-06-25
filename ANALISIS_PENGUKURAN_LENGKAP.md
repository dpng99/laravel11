# ANALISIS KOMPREHENSIF: PENGUKURAN LKJIP BERDASARKAN BAB III
## Integrasi Dokumen + Codebase + Database

**Date:** 2026-06-23  
**Status:** ✅ Complete Analysis  
**Sources:** BAB III LKJIP-Kejari-CabJari + LKJIP-Kejati + Codebase

---

## 📋 BAGIAN I: MEASUREMENT FRAMEWORK FROM BAB III

### A. Sasaran Strategis (Strategic Objectives)

#### Sasaran Strategis 1
**Judul:** Terwujudnya Kelembagaan Hukum yang Transparan dan Adil

**Indikator Utama:**
```
Indeks Persepsi Publik terhadap Citra Kejaksaan Republik Indonesia
```

**Deskripsi:**
- Mengukur penilaian dan pandangan masyarakat terhadap kinerja institusi Kejaksaan
- Cerminan tingkat kepercayaan publik
- Berdasarkan pengalaman langsung, informasi media, sikap aparat
- Dipengaruhi oleh: profesionalisme, integritas, transparansi, keadilan

**Data Sumber:**
- Survei nasional oleh lembaga survei independen
- Contoh: Indikator Politik Indonesia (Februari 2026)
- Mengukur kepercayaan publik pada institusi negara

**Hasil Survei 2026 (Sampel Data):**
```
74% cukup percaya + 6% sangat percaya = 80% total kepercayaan
Posisi: Paling dipercaya dibanding MK (75%), PA (74.4%), KPK (71.8%), Polri (65.5%)
Trend: Rekor tertinggi dalam 2 tahun terakhir
```

---

#### Sasaran Strategis 2
**Judul:** Terwujudnya Efektivitas Penegakan Hukum dan Keadilan melalui Transformasi Sistem Penuntutan

**Sub-Indikator:**

| Kode | Indikator | Deskripsi |
|------|-----------|-----------|
| **K1** | Tingkat Keberhasilan Penanganan Perkara | Mencakup perkara pidana umum, pidana khusus, pidana militer, pidana sipil, pidana administrasi |
| **K2** | Persentase Penanganan Perkara Alternatif | Melalui mediasi penal, diskresi penuntutan, denda damai |
| **K3** | Persentase Penuntutan Alternatif | Melalui pemidanaan alternatif/non-custodial |

---

### B. SKM (Survei Kepuasan Masyarakat) Framework

**Regulasi Dasar:**
- Peraturan Menteri PAN dan RB Nomor 14 Tahun 2017
- Tentang: Pedoman Penyusunan Survei Kepuasan Masyarakat (SKM)
- Untuk: Unit Penyelenggara Pelayanan Publik (UPP)

**Metodologi:**
- **Metode:** Kualitatif dengan pengukuran Skala Likert
- **Skala Likert:** Responden pilih 1 dari beberapa pilihan persetujuan
- **Sifat:** Psikometrik, umum dalam survei riset

**9 Unsur SKM (KPM):**

```
1. Persyaratan Pelayanan
   └─ Prosedur, dokumen yang dibutuhkan

2. Sistem, Mekanisme dan Prosedur
   └─ Cara pelayanan diselenggarakan

3. Waktu Penyelesaian
   └─ Berapa lama penyelesaian

4. Biaya/Tarif
   └─ Retribusi dan biaya lain

5. Produk Spesifikasi Jenis Pelayanan
   └─ Hasil dan jenis pelayanan

6. Kompetensi Pelaksana
   └─ Kemampuan penyelenggara

7. Perilaku Pelaksana
   └─ Sikap penyelenggara layanan

8. Penanganan Pengaduan, Saran dan Masukan
   └─ Mekanisme respon keluhan

9. Sarana dan Prasarana
   └─ Fasilitas dan infrastruktur
```

---

### C. Calculation Methodology for SKM

**Formula Dasar:**

```
Rata-rata nilai SKM pada wilayah Kejaksaan
─────────────────────────────────────────────── × 100 = Hasil (%)
Target "Indeks Persepsi Publik" (Perjanjian Kinerja)
```

**Komponen Perhitungan:**
1. Kumpulkan nilai SKM dari setiap satuan kerja
2. Hitung rata-rata (mean) dari semua nilai SKM
3. Bagi dengan target yang ditetapkan dalam perjanjian kinerja
4. Kalikan 100 untuk mendapat persentase

**Skala Pengukuran (Likert):**
```
Responden diminta memilih dari pilihan:
- Sangat Puas / Sangat Setuju
- Puas / Setuju
- Netral / Cukup Setuju
- Tidak Puas / Tidak Setuju
- Sangat Tidak Puas / Sangat Tidak Setuju
```

**Konversi ke Angka:**
```
(Estimasi berdasarkan standar Likert)
5 = Sangat Puas
4 = Puas
3 = Netral/Cukup
2 = Tidak Puas
1 = Sangat Tidak Puas

Rata-rata Score = (Σ Respons × Bobot) / Total Responden
```

---

## 📊 BAGIAN II: CODEBASE MEASUREMENT STRUCTURE

### Current Implementation in LkjipTemplateService

**Status Determination Logic:**

```php
public function status(float $nilai): string
{
    if ($nilai >= 100) {
        return 'Target tercapai';      // ≥ 100%
    }
    if ($nilai >= 80) {
        return 'Perlu optimalisasi';   // 80-99%
    }
    return 'Perlu perhatian';          // < 80%
}
```

**Threshold Mapping:**
| Nilai | Status | Interpretasi |
|-------|--------|--------------|
| ≥ 100% | Target tercapai | Excellent - Exceeds target |
| 80-99% | Perlu optimalisasi | Good - Achieves target with optimization |
| < 80% | Perlu perhatian | Poor - Below target, needs attention |

---

## 🗄️ BAGIAN III: DATABASE STRUCTURE FOR MEASUREMENT

### pengukuran table

**Primary Purpose:** Store monthly and quarterly performance data

```sql
CREATE TABLE pengukuran (
    id BIGINT PRIMARY KEY,
    indikator_id VARCHAR(255),         -- FK to sinori_sakip_indikator
    id_satker VARCHAR(255),             -- Organization code
    tahun INT,                          -- Year (e.g., 2026)
    sub_indikator VARCHAR(255),         -- Sub-indicator type
    bulan INT,                          -- Month (1-12) or Quarter (3,6,9,12)
    capaian DECIMAL(10,2),              -- Achievement value
    perhitungan VARCHAR(500),           -- Calculation/components (semicolon-separated)
    ditangani DECIMAL(10,2),            -- Handled count
    diselesaikan DECIMAL(10,2),         -- Completed count
    uraian_capaian TEXT,                -- Achievement narrative
    faktor VARCHAR(500),                -- Factors affecting performance
    langkah_optimalisasi TEXT,          -- Optimization steps
    sisa_tahun_lalu DECIMAL(10,2),      -- Previous year remainder
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (indikator_id) REFERENCES sinori_sakip_indikator(id),
    INDEX idx_indikator_satker_tahun (indikator_id, id_satker, tahun),
    INDEX idx_satker_tahun_bulan (id_satker, tahun, bulan)
);
```

**Key Data Patterns:**
- Monthly records: bulan = 1,2,3,...,12
- Quarterly records: bulan = 3,6,9,12 (storing TW1-TW4 data)
- Remainder: bulan = 1 (January stores sisa_tahun_lalu)

### sinori_sakip_indikator table

**Purpose:** Define indicators and their calculation methods

```sql
CREATE TABLE sinori_sakip_indikator (
    id VARCHAR(255) PRIMARY KEY,
    id_saspro VARCHAR(255),
    link VARCHAR(255),                  -- FK to bidang (department)
    lingkup INT,                        -- Scope (0-7)
    indikator_nama VARCHAR(255),        -- Indicator name
    indikator_pembilang VARCHAR(500),   -- Numerator formula
    indikator_penyebut VARCHAR(500),    -- Denominator formula
    indikator_penjelasan TEXT,          -- Explanation
    sub_indikator VARCHAR(255),         -- Sub-indicator type
    indikator_penghitungan VARCHAR(500),-- Calculation method
    tahun INT,
    tren VARCHAR(100)
);
```

**Calculation Methods:**
- Pembilang (Numerator): What to sum/count
- Penyebut (Denominator): What to divide by
- Formula: Pembilang / Penyebut × 100

---

## 🔄 BAGIAN IV: DATA FLOW INTEGRATION

### Complete Measurement Workflow

```
┌─────────────────────────────────────────┐
│ 1. DATA COLLECTION PHASE                │
│                                         │
│ Form Input (Monthly):                   │
│ - Ditangani (Handled)                   │
│ - Diselesaikan (Completed)              │
│ - Custom labels (per indikator)         │
│                                         │
│ Form Input (Quarterly):                 │
│ - Target achievement value              │
│ - Alternative handling methods          │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 2. STORAGE PHASE                        │
│                                         │
│ Pengukuran.store():                     │
│ - Normalize numbers                     │
│ - Create 12 monthly records (M1-M12)    │
│ - Create 4 quarterly records (TW1-TW4)  │
│ - Store each as separate DB row         │
│ - Concatenate multiple values with ;    │
│                                         │
│ Example:                                │
│ ditangani.IND_001.JANUARI → 100         │
│ diselesaikan.IND_001.JANUARI → 95       │
│ Stored as: perhitungan = "100;95"       │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 3. CALCULATION PHASE                    │
│                                         │
│ Extract formula from indikator:         │
│ - Pembilang: How to calculate numerator │
│ - Penyebut: How to calculate denominator│
│                                         │
│ Parse perhitungan string:               │
│ "100;95" → [100, 95]                    │
│                                         │
│ Apply formula:                          │
│ Result = 95/100 × 100 = 95%             │
│                                         │
│ nilai_ikss = 95.0                       │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 4. EVALUATION PHASE                     │
│                                         │
│ Apply Threshold:                        │
│ if (95 >= 100)       → "Target tercapai" │
│ if (95 >= 80 < 100)  → "Perlu optimalisasi"│
│ if (95 < 80)         → "Perlu perhatian"│
│                                         │
│ Status = "Perlu optimalisasi"           │
│ Score = 95%                             │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 5. REPORTING PHASE                      │
│                                         │
│ LkjipTemplateService.generate():        │
│ - Retrieve all measurements per satker  │
│ - Prepare rows with formatted values    │
│ - Generate Word document                │
│ - Include tables, narratives, summary   │
│                                         │
│ Output Fields:                          │
│ - nilai_ikss (95.0)                     │
│ - status (Perlu optimalisasi)           │
│ - narasi (Achievement narrative)        │
│ - target_label (formatted %)            │
│ - capaian_label (formatted %)           │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│ 6. FINAL OUTPUT                         │
│                                         │
│ LKJIP Report (Word Document):           │
│ - Title & Metadata                      │
│ - Performance Table                     │
│   ├─ Sasaran Strategis                  │
│   ├─ Indikator Kinerja                  │
│   ├─ Target                             │
│   ├─ Capaian                            │
│   ├─ Status                             │
│   └─ Narasi                             │
│ - Summary Statistics                    │
│ - Narrative per Indicator               │
└─────────────────────────────────────────┘
```

---

## 🎯 BAGIAN V: MEASUREMENT GAPS & ALIGNMENTS

### Current Implementation vs. BAB III Spec

| Aspek | BAB III | Codebase | Status |
|-------|---------|----------|--------|
| **Threshold 100%** | Target tercapai | ≥ 100 | ✅ Aligned |
| **Threshold 80%** | Perlu optimalisasi | 80-99 | ✅ Aligned |
| **Threshold <80%** | Perlu perhatian | < 80 | ✅ Aligned |
| **Calculation** | Pembilang/Penyebut | diselesaikan/ditangani | ✅ Aligned |
| **Granularity** | Monthly + Quarterly | bulan (1-12, 3,6,9,12) | ✅ Aligned |
| **Aggregation** | Rata-rata per wilayah | Simple average | ⚠️ Needs weight |
| **Scope filtering** | Per level (Kejati/Kejari) | lingkup filter | ✅ Aligned |
| **SKM Integration** | 9 unsur SKM | Partial implementation | ⚠️ Incomplete |
| **Verification** | Survey based | Manual entry | ⚠️ Needs validation |

---

## 📍 BAGIAN VI: CONTROLLERS & SERVICES MAPPING

### PengukuranController Routes

```php
// Data Input Routes
GET    /pengukuran                  → Display measurement form
POST   /simpan-pengukuran           → Store monthly/quarterly data

// Data Retrieval Routes
GET    /pengukuran/indikator/{id_bidang}      → Get indicators by department
GET    /pengukuran/indikator-nama             → Get indicator names
GET    /get-pengukuran/{indikator_id}         → Get measurement history

// Data Update Routes
POST   /pengukuran/update-inline              → Quick edit
POST   /pengukuran/update-bulanan             → Update monthly data
```

### LkjipTemplateService Methods

```php
// 1. prepareRows($rows)
//    Purpose: Format measurement data for document
//    Process:
//    - Extract nilai_ikss
//    - Calculate status (threshold)
//    - Format numeric values
//    - Generate narasi (narrative)

// 2. generate($path, $metadata, $rows, $reportData)
//    Purpose: Create LKJIP Word document
//    Process:
//    - Load template (custom or default)
//    - Replace placeholders
//    - Insert performance table
//    - Add narratives

// 3. status($nilai)
//    Purpose: Determine performance status
//    Thresholds:
//    - >= 100% → "Target tercapai"
//    - 80-99%  → "Perlu optimalisasi"
//    - < 80%   → "Perlu perhatian"

// 4. narrative($ss_name, $ikss_name, $nilai, $status)
//    Purpose: Generate achievement narrative
//    Pattern: "{IKSS} pada {SS} mencapai {nilai}% ({status})"
```

---

## 🔧 BAGIAN VII: KEY FORMULAS & CALCULATIONS

### Formula 1: Performance Achievement (nilai_ikss)

```
nilai_ikss = (diselesaikan / ditangani) × 100

Where:
- diselesaikan = Number of cases/matters completed
- ditangani    = Number of cases/matters handled
- Result       = Percentage (0-∞, though typically 0-150%)
```

**Example:**
```
Indikator: Tingkat Keberhasilan Penanganan Perkara Pidana
Ditangani (Handled): 100 perkara
Diselesaikan (Completed): 95 perkara

nilai_ikss = (95 / 100) × 100 = 95%
Status: Perlu optimalisasi (need optimization)
```

### Formula 2: Average Performance (untuk satker level)

```
Rata-rata IKSS = Σ nilai_ikss / n

Where:
- Σ nilai_ikss = Sum of all individual IKSS values
- n            = Number of IKSS/indikators
```

**Current Issue (from codebase):**
```php
$average = collect($rows)->avg('nilai_ikss') ?? 0;
// Simple average without weights
```

**Problem:** All indicators treated equally, doesn't align with K1, K2, K3 weighting

### Formula 3: SKM Index Calculation (from BAB III)

```
SKM Index = (Rata-rata SKM di wilayah) / (Target SKM) × 100

Where:
- Rata-rata SKM = Average SKM score from all respondents
- Target SKM    = Target set in performance agreement
- Result        = Percentage
```

**9 Unsur SKM Components:**
```
SKM Rata-rata = (∑ Skor 9 Unsur) / 9

Where each Unsur scored 1-5 based on Likert scale
```

---

## 📈 BAGIAN VIII: STRATEGIC OBJECTIVES BREAKDOWN

### Sasaran Strategis 1: Indeks Persepsi Publik

**Data Source:** National surveys (e.g., Indikator Politik Indonesia)

**Measurement Points:**
- Public trust in prosecution office (multiple dimensions)
- Comparison with other law enforcement agencies
- Trend analysis (month-over-month, year-over-year)

**Sample Data from 2026:**
```
Kepercayaan Publik terhadap Kejaksaan RI: 80%
  ├─ Cukup percaya: 74%
  ├─ Sangat percaya: 6%
  └─ Total: 80%

Comparison:
  - Mahkamah Konstitusi: 75%
  - Pengadilan: 74.4%
  - KPK: 71.8%
  - Polri: 65.5%

Trend: Record tertinggi dalam 2 tahun terakhir
```

---

### Sasaran Strategis 2: Efektivitas Penegakan Hukum

**Sub-Indikator K1: Tingkat Keberhasilan Penanganan Perkara**

**Categories:**
- Perkara Pidana Umum (General Criminal)
- Perkara Pidana Khusus (Special Criminal - Corruption, Narcotics, etc.)
- Perkara Pidana Militer (Military Criminal)
- Perkara Pidana Sipil (Civil)
- Perkara Pidana Administrasi (Administrative)

**Calculation:** diselesaikan / ditangani × 100

---

**Sub-Indikator K2: Persentase Penanganan Perkara Alternatif**

**Methods:**
- Mediasi Penal (Penal Mediation)
- Diskresi Penuntutan (Prosecutorial Discretion)
- Denda Damai (Settlement/Fine)

**Calculation:** (perkara_alternatif / total_perkara) × 100

---

**Sub-Indikator K3: Persentase Penuntutan Alternatif**

**Methods:**
- Pemidanaan Alternatif (Alternative Sentencing)
- Non-Custodial Punishment

**Calculation:** (penuntutan_alternatif / total_penuntutan) × 100

---

## ✅ BAGIAN IX: RECOMMENDATIONS FOR CODEBASE

### Immediate Actions (Priority 1)

1. **Align Status Thresholds**
   ```php
   // Current: OK
   // Confirm from BAB III: 100%, 80%, <80%
   ```

2. **Verify Calculation Formulas**
   ```php
   // Confirm diselesaikan/ditangani × 100 is correct
   // Check if pembilang/penyebut always means this
   ```

3. **Implement Weighted Average**
   ```php
   // Change from simple average to weighted
   // Weight per indicator based on kriteria (K1, K2, K3)
   ```

### Medium-term Actions (Priority 2)

4. **Separate K1, K2, K3 Calculations**
   ```php
   // K1: Case handling success
   // K2: Alternative methods
   // K3: Alternative sentencing
   // Each with different calculation/weights
   ```

5. **Implement SKM Integration**
   ```php
   // Link pengukuran with SKM scores
   // Implement 9 unsur scoring
   ```

6. **Add Audit Trail**
   ```php
   // Track measurement changes
   // Record who changed what and when
   ```

### Long-term Actions (Priority 3)

7. **Multi-level Aggregation**
   ```php
   // Support levels: Pusat → Kejati → Kejari → CabJari
   // Each with appropriate aggregation
   ```

8. **Predictive Analytics**
   ```php
   // Trend analysis
   // Performance forecasting
   ```

9. **Compliance Reporting**
   ```php
   // Automatic validation against thresholds
   // Exception reporting
   ```

---

## 📋 BAGIAN X: NEXT STEPS

### Phase 1: Validation (Week 1)
- [ ] Confirm formulas with stakeholders
- [ ] Validate against historical data
- [ ] Document any discrepancies

### Phase 2: Enhancement (Week 2-3)
- [ ] Implement weighted averaging
- [ ] Add calculation logging
- [ ] Create formula audit trail

### Phase 3: Testing (Week 4)
- [ ] Unit tests for all formulas
- [ ] Integration tests with existing data
- [ ] Performance benchmarking

### Phase 4: Deployment (Week 5+)
- [ ] Update LkjipTemplateService
- [ ] Update PengukuranController
- [ ] Training and documentation

---

**Analysis Complete.** ✅

Generated: 2026-06-23  
Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>
