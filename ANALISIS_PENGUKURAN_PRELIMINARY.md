# ANALISIS PENGUKURAN LKJIP - CODEBASE EXISTING
## Preliminary Analysis (Menunggu BAB III Document Extraction)

**Date:** 2026-06-23  
**Status:** In Progress - Awaiting document analysis  

---

## 📊 CURRENT MEASUREMENT STRUCTURE FOUND IN CODEBASE

### 1. Pengukuran Model & Table Structure

**Model File:** `app/Models/Pengukuran.php`

**Database Table:** `pengukuran`

**Columns:**
```php
- indikator_id      // Foreign key to indicator
- id_satker         // Organization code
- tahun             // Year
- sub_indikator     // Sub-indicator classification
- capaian           // Achievement value
- perhitungan       // Calculation/computation result
- ditangani         // Handled count
- diselesaikan      // Completed count
- uraian_capaian    // Achievement description
- faktor            // Factors
- langkah_optimalisasi  // Optimization steps
- bulan             // Monthly breakdown (1-12)
- sisa_tahun_lalu   // Previous year remainder
```

**Key Pattern:** Monthly granularity + Quarterly aggregation

---

### 2. Indikator Structure

**Model File:** `app/Models/Indikator.php`

**Database Table:** `sinori_sakip_indikator`

**Key Columns:**
```php
- id                    // Unique indicator ID
- id_saspro             // Strategic objective link
- lingkup               // Scope (0=global, 1-7=specific levels)
- indikator_nama        // Indicator name
- indikator_pembilang   // Numerator formula
- indikator_penyebut    // Denominator formula
- indikator_penjelasan  // Explanation
- sub_indikator         // Sub-indicator type
- indikator_penghitungan // Calculation method
- tahun                 // Year
- tren                  // Trend indicator
```

**Scope Lingkup Values:**
- 0 = Global/All levels
- 1 = Pusat (Central)
- 2 = Kejati (Provincial)
- 3 = Kejari (District)
- 4 = CabJari (Branch)
- 5 = Pusat + Kejati + Kejari
- 6 = Kejari + CabJari
- 7 = Pusat + Kejati + Kejari + CabJari

---

### 3. LKJIP Structure

**Model File:** `app/Models/Lkjip.php`

**Database Table:** `sinori_sakip_lakip`

**Columns:**
```php
- id_periode        // Reporting period
- id_satker         // Organization
- id_perubahan      // Change/revision ID
- id_filename       // File reference
- id_tglupload      // Upload timestamp
- id_triwulan       // Quarter
```

---

### 4. LKE (Laporan Kerja Evaluasi) Components

**Models Found:**
- `lke_komponen.php` - Main components
- `lke_kriteria.php` - Criteria
- `lke_subkomponens.php` - Sub-components
- `lke_buktidukung.php` - Supporting evidence

**Structure:**
```
Komponen (Component)
  └─ SubKomponen (Sub-component)
       └─ Kriteria (Criteria)
            └─ BuktiDukung (Supporting Evidence)
```

**Relationships:**
- Komponen → hasMany SubKomponen
- SubKomponen → belongsTo Komponen
- Kriteria → belongsTo SubKomponen
- Kriteria ↔ BuktiDukung (Many-to-Many via lke_gabungan)

---

## 🔧 CONTROLLERS & ROUTES ANALYSIS

### PengukuranController Routes
```
GET    /pengukuran                          → index (display form)
POST   /simpan-pengukuran                   → store (save measurements)
GET    /pengukuran/indikator/{id_bidang}    → getIndikatorByBidang
GET    /pengukuran/indikator-nama           → getIndikatorNama
GET    /get-pengukuran/{indikator_id}       → getPengukuran
POST   /pengukuran/update-inline            → updateInline
POST   /pengukuran/update-bulanan           → updateBulanan
```

### Data Entry Flow
```
1. Select Bidang (Department)
   ├─ Fetch related Indicators
   └─ Fetch Sub-indicators

2. For each Sub-indicator:
   ├─ Input monthly data (Januari-Desember)
   │  ├─ ditangani (handled)
   │  ├─ diselesaikan (completed)
   │  └─ calculation labels (custom per indicator)
   └─ Input quarterly data
   
3. Save all data with PengukuranController.store()
   ├─ Normalize numeric values
   ├─ Create/update monthly records
   └─ Create/update quarterly records
```

---

## 📋 MEASUREMENT CALCULATION FLOW

### Status Determination (Current Hard-coded in LkjipTemplateService)

**File:** `app/Services/LkjipTemplateService.php`

**Status Logic:**
```php
public function status(float $nilai): string
{
    if ($nilai >= 100) {
        return 'Target tercapai';  // Target achieved
    }
    
    if ($nilai >= 80) {
        return 'Perlu optimalisasi';  // Needs optimization
    }
    
    return 'Perlu perhatian';  // Needs attention
}
```

**Threshold Levels:**
```
≥ 100% → "Target tercapai" (Excellent)
80-99% → "Perlu optimalisasi" (Good)
< 80%  → "Perlu perhatian" (Poor)
```

### Capaian Calculation

**Input Format (from HTML form):**
- Monthly: `ditangani.{subind}.{BULAN}` & `diselesaikan.{subind}.{BULAN}`
- Quarterly: `ditangani.{subind}.TW{N}` or `diselesaikan.{subind}.TW{N}`

**Processing:**
```php
// 1. Normalize numbers (remove dots, replace comma with dot)
$nilai = str_replace('.', '', $nilai);
$nilai = str_replace(',', '.', $nilai);

// 2. For monthly: concatenate with semicolon
$capaian = implode(';', $values);  // e.g., "100;95"

// 3. For quarterly: store single value
$capaian = $nilai;  // e.g., 98.5
```

### Averaging (Current Implementation)

**File:** `app/Services/LkjipTemplateService.php:61`

```php
$average = collect($rows)->avg('nilai_ikss') ?? 0;
$achieved = collect($rows)->where('nilai_ikss', '>=', 100)->count();
```

**Current Issue:** Simple average without weights - all indicators treated equally

---

## 🏗️ DATABASE RELATIONSHIPS

### Pengukuran to Indikator
```
Pengukuran.indikator_id → Indikator.id
```

### Indikator to Bidang
```
Indikator.link → Bidang.id (or Bidang.rumpun)
```

### Pengukuran to Organization
```
Pengukuran.id_satker → sinori_sakip_satker.id_satker
```

### Temporal Grain
```
- Monthly: pengukuran.bulan (1-12)
- Quarterly: pengukuran.bulan in (3,6,9,12) → TW1,TW2,TW3,TW4
- Yearly: pengukuran.tahun
```

---

## 📁 FILES & SERVICES STRUCTURE

### Core Services

**LkjipTemplateService.php (11KB)**
- Purpose: Generate LKJIP reports in Word format
- Key Methods:
  - `prepareRows()` - Format data for template
  - `generate()` - Create Word document
  - `status()` - Determine performance status
  - `generateFromCustomTemplate()` - Use custom template per level

**CustomLkjipTemplateProcessor.php**
- Purpose: Handle custom template replacement
- Methods:
  - `replaceValues()` - Replace scalar values
  - `replaceTableByMarker()` - Replace tables by marker
  - `replacePerformanceTable()` - Insert performance data

### API Endpoints (New)

**From previous implementation:**
- `/api/v1/measurement/evaluate` - Single evaluation
- `/api/v1/measurement/batch-evaluate` - Multiple evaluations
- `/api/v1/measurement/config/thresholds` - Configure thresholds
- `/api/v1/lke/` - LKE compliance endpoints
- `/api/v1/audit/` - Audit trails

---

## 🔍 LINGKUP (SCOPE) FILTERING LOGIC

**In PengukuranController:**

```php
private function applyLingkupFilter($query, $level)
{
    // Level 0 (Pusat) → scope 0 or 1
    if ($level == 1) {
        $query->whereIn('lingkup', [0, 1]);
    }
    // Level 2 (Kejati) → scope 0, 2, 5, 7
    elseif ($level == 2) {
        $query->whereIn('lingkup', [0, 2, 5, 7]);
    }
    // Level 3 (Kejari) → scope 0, 3, 5, 6, 7
    elseif ($level == 3) {
        $query->whereIn('lingkup', [0, 3, 5, 6, 7]);
    }
    // Level 4 (CabJari) → scope 0, 4, 6
    elseif ($level == 4) {
        $query->whereIn('lingkup', [0, 4, 6]);
    }
    
    return $query;
}
```

**Key Pattern:** Central has global scope, each level shows only relevant indicators

---

## 📊 MEASUREMENT DATA FLOW

```
┌─────────────────────────────────────────────────┐
│ Frontend Form (Inertia.js)                      │
│ - Select Bidang (Department)                    │
│ - Enter Monthly Data (Ditangani/Diselesaikan)   │
│ - Enter Quarterly Data (TW1-TW4)                │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│ PengukuranController.store()                    │
│ - Normalize numbers                             │
│ - Create monthly pengukuran records             │
│ - Create quarterly pengukuran records           │
│ - Sync to IKSS legacy tables                    │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│ Database: pengukuran table                      │
│ - 1 record per (indikator, satker, tahun, bulan)│
│ - Store: capaian, perhitungan, ditangani, dll  │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│ LkjipTemplateService.generate()                 │
│ - Fetch measurement data                        │
│ - Calculate status (value → threshold)          │
│ - Generate Word document with tables/narratives │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│ Output: LKJIP Report (Word Format)              │
│ - Performance Table with Status                 │
│ - Narasi Capaian (Achievement Narrative)        │
│ - Summary Statistics                            │
└─────────────────────────────────────────────────┘
```

---

## ⚠️ CURRENT GAPS (Awaiting BAB III Details)

From codebase analysis, these areas need clarification from BAB III:

1. **Calculation Methodology**
   - How is `nilai_ikss` calculated from ditangani/diselesaikan?
   - Is it: diselesaikan/ditangani * 100?
   - Are there other calculation methods?

2. **Target Determination**
   - How are targets set per indicator per level?
   - Are targets fixed or variable by period?

3. **Sub-indicator Processing**
   - How are multiple sub-indicators combined?
   - Is there aggregation logic?

4. **Quarterly Aggregation**
   - How are monthly data aggregated to quarterly?
   - Simple average or weighted?

5. **Cross-level Calculation**
   - How does Kejati report differ from Kejari?
   - Different calculation methods per level?

6. **Bidang Role**
   - What is Bidang in measurement context?
   - Is it hierarchical or categorical?

---

## 📚 AWAITING FROM DOCUMENTS

### BAB III (Chapter 3) Should Contain

1. **Measurement Objectives**
   - What is being measured and why

2. **Calculation Formulas**
   - Formal mathematical definitions
   - Handling of edge cases (division by zero, etc.)

3. **Data Collection Process**
   - Source of truth for each indicator
   - Validation rules

4. **Aggregation Rules**
   - How sub-indicators combine
   - How periods combine (month→quarter→year)

5. **Assessment Criteria**
   - Exact thresholds (currently hard-coded as 100%, 80%)
   - May vary by level or indicator

6. **Reporting Requirements**
   - What must be included in LKJIP
   - What metrics are displayed

---

## 🔗 NEXT STEPS

1. **Wait for document extraction** → Parse BAB III measurement methodology
2. **Identify discrepancies** → Compare document specs with current code
3. **Update implementation** → Modify if current implementation doesn't match
4. **Validate against existing data** → Test with historical measurements
5. **Document findings** → Create comprehensive measurement spec

---

**Analysis Status:** 60% Complete (Awaiting document extraction)
