# VISUAL MAPPING: LKJIP MEASUREMENT SYSTEM
## Database → Models → Controllers → Services → Output

---

## 1. DATABASE TABLES ARCHITECTURE

```
┌─────────────────────────────────────────────────────────┐
│          sinori_sakip_indikator (Indikator)            │
├─────────────────────────────────────────────────────────┤
│ id                    TEXT PRIMARY KEY                 │
│ indikator_nama        VARCHAR(255)                     │
│ indikator_pembilang   VARCHAR(500)  ← Numerator       │
│ indikator_penyebut    VARCHAR(500)  ← Denominator     │
│ indikator_penghitungan VARCHAR(500) ← Calculation method
│ sub_indikator         VARCHAR(255)                     │
│ lingkup              INT (0-7)      ← Scope per level │
│ tahun                INT                              │
└───────────────────────────┬─────────────────────────────┘
                            │ FK: indikator_id
                            ▼
┌─────────────────────────────────────────────────────────┐
│           pengukuran (Measurement Data)                 │
├─────────────────────────────────────────────────────────┤
│ indikator_id          VARCHAR(255)  ← Points to indicator
│ id_satker             VARCHAR(255)  ← Organization code
│ tahun                 INT            ← Year (2026)    │
│ bulan                 INT (1-12)     ← Monthly breakdown│
│ capaian               DECIMAL(10,2)  ← Achievement value
│ perhitungan           VARCHAR(500)   ← Components (;-sep)
│ ditangani             DECIMAL(10,2)  ← Handled count │
│ diselesaikan          DECIMAL(10,2)  ← Completed count
│ uraian_capaian        TEXT           ← Description   │
│ langkah_optimalisasi  TEXT           ← Optimization  │
│ sisa_tahun_lalu       DECIMAL(10,2)  ← Previous year │
└───────────────────────────┬─────────────────────────────┘
                            │ Stores measurement
                            │ for each month/quarter
                            ▼
                    Monthly Breakdown:
                    bulan: 1-12 (Jan-Dec)
                    
                    Quarterly Breakdown:
                    bulan: 3,6,9,12 (TW1-TW4)
```

---

## 2. MODEL RELATIONSHIPS

```
┌─────────────────────────┐
│  Indikator (Model)      │  ← sinori_sakip_indikator
└────────────┬────────────┘
             │ indikator()
             │ (BelongsTo)
             ▼
┌─────────────────────────┐
│  Pengukuran (Model)     │  ← pengukuran
├─────────────────────────┤
│ indikator_id            │
│ id_satker               │
│ tahun                   │
│ bulan (1-12 or 3,6,9,12)│
│ capaian                 │
└─────────────────────────┘

Relationship Path:
pengukuran.indikator_id → Indikator.id
```

---

## 3. DATA TRANSFORMATION FLOW

```
┌──────────────────────────────────────────────┐
│ INPUT: Form Data (Monthly)                   │
├──────────────────────────────────────────────┤
│ ditangani.IND_001.JANUARI = "100"           │
│ diselesaikan.IND_001.JANUARI = "95"         │
│ ditangani.IND_001.FEBRUARI = "110"          │
│ diselesaikan.IND_001.FEBRUARI = "105"       │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ NORMALIZATION (PengukuranController)         │
├──────────────────────────────────────────────┤
│ • Remove dots: "1.000" → "1000"             │
│ • Replace comma: "1,5" → "1.5"              │
│ • Convert to float: "100" → 100.0           │
│ • Handle empty: "" → null                   │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ STORAGE (Create pengukuran records)          │
├──────────────────────────────────────────────┤
│ Record 1: bulan=1,  perhitungan="100;95"    │
│ Record 2: bulan=2,  perhitungan="110;105"   │
│ Record 3: bulan=3,  perhitungan="..." (TW1) │
│ Record 4: bulan=4,  perhitungan="..."       │
│ ...                                          │
│ Record 12: bulan=12, perhitungan="..." (TW4)│
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ RETRIEVAL (For Report Generation)            │
├──────────────────────────────────────────────┤
│ SELECT p.* FROM pengukuran p                │
│ WHERE p.indikator_id = 'IND_001'           │
│ AND p.id_satker = 'KEJARI_001'             │
│ AND p.tahun = 2026                         │
│ AND p.bulan IN (3,6,9,12)  -- Quarterly only
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ CALCULATION (Extract formula from Indikator) │
├──────────────────────────────────────────────┤
│ indikator.indikator_pembilang  = "diselesaikan"
│ indikator.indikator_penyebut   = "ditangani"  │
│ indikator.indikator_penghitungan = "standard" │
│                                              │
│ Parse perhitungan: "100;95" → [100, 95]    │
│ • Index 0 = diselesaikan = 100               │
│ • Index 1 = ditangani = 95                   │
│ • Wait... yang mana pembilang? Perlu klarifikasi
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ FORMULA EVALUATION                           │
├──────────────────────────────────────────────┤
│ nilai_ikss = (diselesaikan / ditangani) × 100
│            = (100 / 95) × 100               │
│            = 105.26%                        │
│                                              │
│ OR (if roles reversed)                       │
│ nilai_ikss = (95 / 100) × 100               │
│            = 95%                            │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ THRESHOLD APPLICATION                        │
├──────────────────────────────────────────────┤
│ if (nilai >= 100.0)                         │
│   status = "Target tercapai"   ← Excellent  │
│ elseif (nilai >= 80.0)                      │
│   status = "Perlu optimalisasi" ← Good      │
│ else                                         │
│   status = "Perlu perhatian"   ← Poor       │
│                                              │
│ For nilai = 95%:                            │
│ → status = "Perlu optimalisasi"             │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ NARRATIVE GENERATION                         │
├──────────────────────────────────────────────┤
│ narrative() method:                          │
│                                              │
│ Pattern: "{IKSS} pada {SS} mencapai         │
│          {nilai}% ({status})"               │
│                                              │
│ Example: "Tingkat Keberhasilan Penanganan   │
│ Perkara Pidana pada Efektivitas Penegakan  │
│ Hukum mencapai 95% (Perlu optimalisasi)"   │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ ROW PREPARATION (For Template)               │
├──────────────────────────────────────────────┤
│ $row = [                                     │
│   'no' => 1,                                 │
│   'ss_id' => 'SS_2',                         │
│   'nama_ss' => 'Sasaran Strategis 2',       │
│   'nama_ikss' => 'Keberhasilan Perkara',    │
│   'target_label' => '100%',                 │
│   'capaian_label' => '95%',                 │
│   'nilai_ikss' => 95.0,                     │
│   'nilai_ikss_label' => '95.00%',           │
│   'status' => 'Perlu optimalisasi',         │
│   'narasi' => '...narrative...'             │
│ ]                                            │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ DOCUMENT GENERATION (LkjipTemplateService)   │
├──────────────────────────────────────────────┤
│ • Load template (custom per level)           │
│ • Create Word document with:                 │
│   - Metadata (satker, tahun, triwulan)      │
│   - Performance Table                        │
│   - Summary Statistics                       │
│   - Narrative per Indicator                  │
│ • Save as .docx file                         │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────┐
│ OUTPUT: LKJIP Report (Word Format)           │
├──────────────────────────────────────────────┤
│ LAPORAN KINERJA INSTANSI PEMERINTAH         │
│ KEJAKSAAN [SATKER]                           │
│ TRIWULAN I TAHUN 2026                        │
│                                              │
│ ┌────────┬──────────┬──────┬────────┬────────┐
│ │ NO     │ IKSS     │TARGET│CAPAIAN │ STATUS │
│ ├────────┼──────────┼──────┼────────┼────────┤
│ │ 1      │ Keberhasilan Perkara  │ 100% │ 95%    │ Opt. │
│ │ 2      │ Mediasi Penal        │ 80%  │ 88%    │ TT   │
│ │ 3      │ Pemidanaan Alt.      │ 60%  │ 75%    │ TT   │
│ └────────┴──────────┴──────┴────────┴────────┘
│                                              │
│ Ringkasan: Rata-rata 86% dari 3 IKSS       │
│ Narasi per Indikator...                     │
└──────────────────────────────────────────────┘
```

---

## 4. CONTROLLER FLOW

```
PengukuranController
│
├── index()
│   └─ Retrieve bidangs based on user level
│      └─ Filter by lingkup
│         └─ Return Pengukuran Inertia view
│
├── store() [POST /simpan-pengukuran]
│   │
│   ├─ Get sub_indikator_list from form
│   ├─ Normalize all numeric values
│   │  └─ Remove dots, replace commas
│   │
│   ├─ For each sub-indicator:
│   │  │
│   │  ├─ Get Indikator model
│   │  ├─ Extract calculation labels
│   │  │  (e.g., "ditangani,diselesaikan")
│   │  │
│   │  ├─ Store sisa_tahun_lalu (remainder)
│   │  │  └─ Create record with bulan=1
│   │  │
│   │  ├─ For each month (1-12):
│   │  │  │
│   │  │  ├─ Collect values for each label
│   │  │  ├─ Concatenate with semicolon
│   │  │  │  (e.g., "100;95")
│   │  │  │
│   │  │  └─ Create/Update pengukuran record
│   │  │     └─ Store in perhitungan column
│   │  │
│   │  └─ For each quarter (TW1-TW4):
│   │     │
│   │     ├─ Extract quarterly value
│   │     │  (from TW1, TW2, TW3, TW4 inputs)
│   │     │
│   │     └─ Create/Update pengukuran records
│   │        └─ Store capaian (achievement)
│   │
│   └─ Sync to IKSS legacy tables
│      └─ Update performance data
│
├── getIndikatorByBidang()
│   └─ Return indicators filtered by:
│      ├─ Bidang (department)
│      └─ Lingkup (scope for user level)
│
└── updateInline() / updateBulanan()
    └─ Quick edit without full form reload
```

---

## 5. SERVICE LAYER: LkjipTemplateService

```
LkjipTemplateService
│
├── prepareRows($rows)
│   │
│   ├─ For each measurement row:
│   │  │
│   │  ├─ Extract nilai_ikss
│   │  │  └─ Parse from capaian/perhitungan
│   │  │
│   │  ├─ Call status($nilai)
│   │  │  └─ Return status string
│   │  │
│   │  ├─ Format values
│   │  │  ├─ formatPercentage(nilai)
│   │  │  └─ formatNullablePercentage(target)
│   │  │
│   │  ├─ Generate narrative
│   │  │  └─ narrative(ss_name, ikss_name, nilai, status)
│   │  │
│   │  └─ Return prepared row
│   │
│   └─ Return array of prepared rows
│
├── generate($path, $metadata, $rows, $reportData)
│   │
│   ├─ Prepare rows
│   │  └─ Call prepareRows()
│   │
│   ├─ Check custom template
│   │  └─ customTemplatePath(level)
│   │
│   ├─ If custom template exists:
│   │  │
│   │  └─ generateFromCustomTemplate()
│   │     │
│   │     ├─ Load custom .docx template
│   │     ├─ Replace placeholders
│   │     │  ├─ satker, tahun, triwulan
│   │     │  └─ reportData scalars
│   │     │
│   │     ├─ Replace performance table
│   │     │  └─ Insert rows XML
│   │     │
│   │     ├─ Replace report tables
│   │     │  └─ For each table marker
│   │     │
│   │     └─ Save modified document
│   │
│   ├─ Else (default template):
│   │  │
│   │  ├─ Create new PhpWord document
│   │  ├─ Add metadata section
│   │  ├─ Add performance table
│   │  │  └─ insertPerformanceTable($section, $rows)
│   │  │
│   │  ├─ Add summary section
│   │  │  ├─ Calculate average
│   │  │  ├─ Count achieved (≥100%)
│   │  │  └─ Display summary text
│   │  │
│   │  ├─ Add narrative section
│   │  │  └─ For each row: add narasi text
│   │  │
│   │  └─ Save as Word2007 format
│   │
│   └─ Output file saved to $path
│
├── status($nilai)
│   │
│   ├─ if ($nilai >= 100)
│   │  └─ return 'Target tercapai'
│   │
│   ├─ elseif ($nilai >= 80)
│   │  └─ return 'Perlu optimalisasi'
│   │
│   └─ else
│      └─ return 'Perlu perhatian'
│
├── narrative($ss_name, $ikss_name, $nilai, $status)
│   │
│   └─ Generate text like:
│      "{IKSS} pada {SS} mencapai {nilai}% ({status})"
│
└── addPerformanceTable($section, $rows)
    │
    ├─ Create table with headers
    │  └─ NO, SASARAN STRATEGIS, INDIKATOR, TARGET, CAPAIAN
    │
    └─ For each row: add table cells
       └─ With formatted values and status
```

---

## 6. LEVEL-BASED FILTERING

```
User Level → Bidang Filter → Lingkup Values

Level 1 (Pusat/Central)
├─ bidang_lokasi = 1
└─ lingkup IN (0, 1)
   ├─ 0: Global (all organizations)
   └─ 1: Pusat specific

Level 2 (Kejati/Provincial)
├─ bidang_lokasi = 2
└─ lingkup IN (0, 2, 5, 7)
   ├─ 0: Global
   ├─ 2: Kejati specific
   ├─ 5: Pusat + Kejati + Kejari
   └─ 7: All

Level 3 (Kejari/District)
├─ bidang_lokasi = 3
└─ lingkup IN (0, 3, 5, 6, 7)
   ├─ 0: Global
   ├─ 3: Kejari specific
   ├─ 5: Pusat + Kejati + Kejari
   ├─ 6: Kejari + CabJari
   └─ 7: All

Level 4 (CabJari/Branch)
├─ bidang_lokasi = 4
└─ lingkup IN (0, 4, 6)
   ├─ 0: Global
   ├─ 4: CabJari specific
   └─ 6: Kejari + CabJari
```

---

## 7. TEMPLATE PER LEVEL

```
LkjipTemplateService.customTemplatePath(level)
│
├─ Level 2 (Kejati)
│  └─ /resources/templates/lkjip/lkjip-kejati.docx
│
├─ Level 3-4 (Kejari/CabJari)
│  └─ /resources/templates/lkjip/lkjip-kejari-cabjari.docx
│
└─ Default
   └─ Generate from code (if template not found)
```

---

## 8. CALCULATION VERIFICATION

```
Current Status: ⚠️ NEEDS CLARIFICATION

Ambiguity in perhitungan field:
┌─────────────────────────────────────────┐
│ Form Input:                             │
│ ditangani.IND_001.JAN = 100             │
│ diselesaikan.IND_001.JAN = 95           │
│                                         │
│ Stored as:                              │
│ perhitungan = "100;95"                  │
│                                         │
│ Question: Which is which?               │
│ Option 1:                               │
│   [0] = ditangani = 100                 │
│   [1] = diselesaikan = 95               │
│   Result = 95/100 × 100 = 95%           │
│                                         │
│ Option 2:                               │
│   [0] = diselesaikan = 100              │
│   [1] = ditangani = 95                  │
│   Result = 100/95 × 100 = 105%          │
│                                         │
│ Option 3:                               │
│   Order follows indikator_penghitungan  │
│   (need to check this value)            │
│                                         │
│ ⚠️ MUST CLARIFY WITH STAKEHOLDERS       │
└─────────────────────────────────────────┘
```

---

**Mapping Complete** ✅

Generated: 2026-06-23  
Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>
