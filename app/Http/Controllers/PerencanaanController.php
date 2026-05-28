<?php

namespace App\Http\Controllers;

use App\Models\Dipa;
use App\Models\Iku;
use App\Models\Bidang;
use App\Models\Pk;
use App\Models\Renaksi;
use App\Models\Renja;
use App\Models\Renstra;
use App\Models\Rkakl;
use App\Models\TargetPK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use PhpOffice\PhpWord\TemplateProcessor;

class PerencanaanController extends Controller
{
    private const PK_TEMPLATE_PATH = 'templates/pk_template.docx';

    private const BASE_SELECT_COLUMNS = ['id', 'id_filename', 'id_perubahan', 'id_tglupload'];

    private const DIPA_SELECT_COLUMNS = [
        'id',
        'id_filename',
        'id_perubahan',
        'id_tglupload',
        'id_pagu',
        'id_gakyankum',
        'id_dukman',
    ];

    private const DOCUMENTS = [
        'renstra' => ['model' => Renstra::class, 'prefix' => 'renstra'],
        'iku' => ['model' => Iku::class, 'prefix' => 'IKU'],
        'renja' => ['model' => Renja::class, 'prefix' => 'renja'],
        'rkakl' => ['model' => Rkakl::class, 'prefix' => 'rkakl'],
        'dipa' => ['model' => Dipa::class, 'prefix' => 'dipa'],
        'renaksi' => ['model' => Renaksi::class, 'prefix' => 'renaksi'],
        'pk' => ['model' => Pk::class, 'prefix' => 'pk'],
    ];

    public function index(Request $request)
    {
        if (! session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $renstraPeriode = $this->renstraPeriode($tahun);

        return Inertia::render('Kelola/Perencanaan', [
            'tahun' => $tahun,
            'renstra' => $this->latestDocuments('renstra', $idSatker, $renstraPeriode),
            'iku' => $this->latestDocuments('iku', $idSatker, $tahun),
            'renja' => $this->latestDocuments('renja', $idSatker, $tahun),
            'rkakl' => $this->latestDocuments('rkakl', $idSatker, $tahun),
            'dipa' => $this->latestDocuments('dipa', $idSatker, $tahun),
            'renaksi' => $this->latestDocuments('renaksi', $idSatker, $tahun),
            'pk' => $this->latestDocuments('pk', $idSatker, $tahun),
            'id_satker' => str_pad((string) $idSatker, 6, '0', STR_PAD_LEFT),
            'bidang' => $this->pkBidangs(),
            'indikator' => $this->pkIndikators(),
            'target' => TargetPK::where('id_satker', $idSatker)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy(fn ($target) => (string) $target->indikator_id),
        ]);
    }

    public function showIndikator(Request $request)
    {
        return response()->json(
            $this->pkIndikators($request->input('rumpun'))->values()
        );
    }

    public function store(Request $request)
    {
        if ($request->has('indikator_id')) {
            return $this->storetarget($request);
        }

        return back()
            ->with('error', 'Data perencanaan tidak lengkap.')
            ->with('active_tab', 'perjanjian-kinerja');
    }

    public function uploadFile(Request $request, string $type)
    {
        $document = $this->documentConfig($type);

        if (! $document) {
            return back()->with('error', 'Tipe dokumen tidak valid.');
        }

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $periode = $this->documentPeriode($type, $tahun);

        $request->validate($this->uploadRules($type));

        $nextPerubahan = $this->nextPerubahan($document['model'], $idSatker, $periode);
        $fileName = $this->buildFileName($document['prefix'], $tahun, $nextPerubahan);

        try {
            Storage::disk('google')->putFileAs(
                $this->repositoryPath($idSatker),
                $request->file("{$type}_file"),
                $fileName
            );

            $document['model']::create($this->documentPayload(
                $request,
                $type,
                $idSatker,
                $periode,
                $nextPerubahan,
                $fileName
            ));

            return redirect()
                ->route('perencanaan')
                ->with([
                    $this->documentSuccessFlashKey($type) => "File {$type} berhasil diupload.",
                    'active_tab' => $this->documentTab($type),
                ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                "{$type}_file" => 'Gagal Upload ke Google Drive: '.$e->getMessage(),
            ]);
        }
    }

    public function updateFile(Request $request, string $type, int $id)
    {
        $document = $this->documentConfig($type);

        if (! $document) {
            return back()
                ->with('error', 'Tipe dokumen tidak valid.')
                ->with('active_tab', $this->documentTab($type));
        }

        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|mimes:pdf|max:10240',
            'id_pagu' => 'nullable|numeric',
            'id_gakyankum' => 'nullable|numeric',
            'id_dukman' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('active_tab', $this->documentTab($type));
        }

        try {
            $fileRecord = $this->findDocumentRecord($document['model'], $id, session('id_satker'));

            if (! $fileRecord) {
                return back()->with('error', 'File tidak ditemukan atau akses ditolak.');
            }

            $this->fillDipaMetadata($fileRecord, $request, $type);

            if ($request->hasFile('file')) {
                $newPerubahan = (int) $fileRecord->id_perubahan + 1;
                $newFileName = $this->buildFileName($document['prefix'], session('tahun_terpilih'), $newPerubahan);

                $this->replaceStoredFile($fileRecord, $request, session('id_satker'), $newFileName);

                $fileRecord->id_filename = $newFileName;
                $fileRecord->id_perubahan = $newPerubahan;
                $fileRecord->id_tglupload = now()->format('d/m/Y h:i A');
            } elseif (! $fileRecord->isDirty()) {
                return back()
                    ->with('error', 'Tidak ada perubahan data.')
                    ->with('active_tab', $this->documentTab($type));
            }

            $fileRecord->save();

            return back()
                ->with('success-update', 'Dokumen berhasil diperbarui.')
                ->with('active_tab', $this->documentTab($type));
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal update: '.$e->getMessage())
                ->with('active_tab', $this->documentTab($type));
        }
    }

    public function storetarget(Request $request)
    {
        $validated = $request->validate([
            'indikator_id' => 'required|exists:indikator_sastra,kode_indikator',
            'target_tahun' => 'required|numeric',
            'target_triwulan_1' => 'nullable|numeric',
            'target_triwulan_2' => 'nullable|numeric',
            'target_triwulan_3' => 'nullable|numeric',
            'target_triwulan_4' => 'nullable|numeric',
        ]);

        $idSatker = session('id_satker');
        $tahun = session('tahun_terpilih');

        TargetPK::updateOrCreate(
            [
                'indikator_id' => $validated['indikator_id'],
                'id_satker' => $idSatker,
                'tahun' => $tahun,
            ],
            [
                'target_tahun' => $validated['target_tahun'],
                'target_triwulan_1' => $validated['target_triwulan_1'] ?? null,
                'target_triwulan_2' => $validated['target_triwulan_2'] ?? null,
                'target_triwulan_3' => $validated['target_triwulan_3'] ?? null,
                'target_triwulan_4' => $validated['target_triwulan_4'] ?? null,
            ]
        );

        return Redirect::route('perencanaan')
            ->with('success-pk', 'Target berhasil disimpan!')
            ->with('active_tab', 'perjanjian-kinerja');
    }

    public function exportPkCsv()
    {
        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $fileName = "target_pk_{$idSatker}_{$tahun}.csv";
        $rows = $this->pkTargetRows();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'indikator_id',
                'bidang',
                'indikator',
                'target_tahun',
                'target_triwulan_1',
                'target_triwulan_2',
                'target_triwulan_3',
                'target_triwulan_4',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['indikator_id'],
                    $row['bidang'],
                    $row['indikator'],
                    $row['target_tahun'],
                    $row['target_triwulan_1'],
                    $row['target_triwulan_2'],
                    $row['target_triwulan_3'],
                    $row['target_triwulan_4'],
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importPkCsv(Request $request)
    {
        $validated = $request->validate([
            'pk_import_file' => 'required|file|mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel|max:2048',
        ]);

        $path = $validated['pk_import_file']->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return back()
                ->with('error', 'File CSV tidak dapat dibaca.')
                ->with('active_tab', 'perjanjian-kinerja');
        }

        $delimiter = $this->detectCsvDelimiter($path);
        $headers = fgetcsv($handle, 0, $delimiter);

        if (! $headers) {
            fclose($handle);

            return back()
                ->with('error', 'Header CSV tidak ditemukan.')
                ->with('active_tab', 'perjanjian-kinerja');
        }

        $headers = array_map(fn ($header) => $this->normalizeCsvHeader($header), $headers);
        $requiredHeaders = ['indikator_id', 'target_tahun'];

        foreach ($requiredHeaders as $requiredHeader) {
            if (! in_array($requiredHeader, $headers, true)) {
                fclose($handle);

                return back()
                    ->with('error', "Kolom {$requiredHeader} wajib ada pada CSV.")
                    ->with('active_tab', 'perjanjian-kinerja');
            }
        }

        $validIndikatorIds = collect($this->pkTargetRows())
            ->pluck('indikator_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $imported = 0;
        $skipped = 0;
        $lineNumber = 1;

        DB::beginTransaction();

        try {
            while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;

                if ($this->isBlankCsvRow($values)) {
                    continue;
                }

                $row = $this->combineCsvRow($headers, $values);
                $indikatorId = trim((string) ($row['indikator_id'] ?? ''));

                if ($indikatorId === '' || ! in_array($indikatorId, $validIndikatorIds, true)) {
                    $skipped++;

                    continue;
                }

                [$targetTahun, $targetTahunValid] = $this->parseNullableNumber($row['target_tahun'] ?? null);

                if ($targetTahun === null || ! $targetTahunValid) {
                    $skipped++;

                    continue;
                }

                $targetTriwulan = [];

                foreach (['target_triwulan_1', 'target_triwulan_2', 'target_triwulan_3', 'target_triwulan_4'] as $field) {
                    [$value, $valid] = $this->parseNullableNumber($row[$field] ?? null);

                    if (! $valid) {
                        throw new \InvalidArgumentException("Nilai {$field} pada baris {$lineNumber} tidak valid.");
                    }

                    $targetTriwulan[$field] = $value;
                }

                TargetPK::updateOrCreate(
                    [
                        'indikator_id' => $indikatorId,
                        'id_satker' => session('id_satker'),
                        'tahun' => session('tahun_terpilih'),
                    ],
                    [
                        'target_tahun' => $targetTahun,
                        'target_triwulan_1' => $targetTriwulan['target_triwulan_1'],
                        'target_triwulan_2' => $targetTriwulan['target_triwulan_2'],
                        'target_triwulan_3' => $targetTriwulan['target_triwulan_3'],
                        'target_triwulan_4' => $targetTriwulan['target_triwulan_4'],
                    ]
                );

                $imported++;
            }

            fclose($handle);
            $handle = null;
            DB::commit();
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            DB::rollBack();

            return back()
                ->with('error', 'Import CSV gagal: '.$e->getMessage())
                ->with('active_tab', 'perjanjian-kinerja');
        }

        if ($imported === 0) {
            return back()
                ->with('error', 'Tidak ada data PK valid yang dapat diimport.')
                ->with('active_tab', 'perjanjian-kinerja');
        }

        $message = "Import PK berhasil: {$imported} baris diproses.";

        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati.";
        }

        return Redirect::route('perencanaan')
            ->with('success-pk', $message)
            ->with('active_tab', 'perjanjian-kinerja');
    }

    public function exportPkWord()
    {
        $templatePath = storage_path('app/'.self::PK_TEMPLATE_PATH);

        if (! file_exists($templatePath)) {
            return back()
                ->with('error', 'Template Word PK belum ditemukan di storage/app/'.self::PK_TEMPLATE_PATH.'.')
                ->with('active_tab', 'perjanjian-kinerja');
        }

        $tahun = session('tahun_terpilih');
        $idSatker = session('id_satker');
        $satkernama = $this->satkerName();
        $rows = $this->pkTargetRows();
        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->setValues([
            'tahun' => (string) $tahun,
            'id_satker' => (string) $idSatker,
            'satkernama' => $satkernama,
            'tanggal_cetak' => now()->translatedFormat('d F Y'),
            'total_indikator' => (string) count($rows),
        ]);

        $wordRows = count($rows) > 0 ? $rows : [[
            'no' => '',
            'bidang' => '',
            'indikator_id' => '',
            'indikator' => '',
            'target_tahun' => '',
            'target_triwulan_1' => '',
            'target_triwulan_2' => '',
            'target_triwulan_3' => '',
            'target_triwulan_4' => '',
        ]];

        $rowVariable = $this->firstExistingTemplateVariable($templateProcessor, ['indikator', 'target_tahun', 'no']);

        if ($rowVariable) {
            $templateProcessor->cloneRowAndSetValues($rowVariable, $wordRows);
        } else {
            $templateProcessor->setValues($wordRows[0]);
        }

        Storage::disk('local')->makeDirectory('temp');

        $downloadName = "PK_{$idSatker}_{$tahun}.docx";
        $temporaryPath = storage_path('app/temp/'.uniqid('pk_', true).'.docx');

        $templateProcessor->saveAs($temporaryPath);

        return response()
            ->download($temporaryPath, $downloadName)
            ->deleteFileAfterSend(true);
    }

    public function deleteFile(string $type, int $id)
    {
        $document = $this->documentConfig($type);

        if (! $document) {
            return back()
                ->with('error', 'Tipe dokumen tidak valid.')
                ->with('active_tab', $this->documentTab($type));
        }

        try {
            $idSatker = session('id_satker');
            $fileRecord = $this->findDocumentRecord($document['model'], $id, $idSatker);

            if (! $fileRecord) {
                return back()->with('error', 'File tidak ditemukan.');
            }

            $pathFile = $this->repositoryPath($idSatker).'/'.$fileRecord->id_filename;

            if (Storage::disk('google')->exists($pathFile)) {
                Storage::disk('google')->delete($pathFile);
            }

            $fileRecord->delete();

            return back()
                ->with('success-delete', 'Dokumen berhasil dihapus permanen.')
                ->with('active_tab', $this->documentTab($type));
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Gagal hapus: '.$e->getMessage())
                ->with('active_tab', $this->documentTab($type));
        }
    }

    private function latestDocuments(string $type, mixed $idSatker, mixed $periode)
    {
        $document = $this->documentConfig($type);
        $columns = $type === 'dipa' ? self::DIPA_SELECT_COLUMNS : self::BASE_SELECT_COLUMNS;

        return $document['model']::select($columns)
            ->where('id_satker', $idSatker)
            ->where('id_periode', $periode)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->get();
    }

    private function nextPerubahan(string $modelClass, mixed $idSatker, mixed $periode): int
    {
        $latest = $modelClass::select('id_perubahan')
            ->where('id_satker', $idSatker)
            ->where('id_periode', $periode)
            ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
            ->first();

        return $latest ? (int) $latest->id_perubahan + 1 : 0;
    }

    private function documentConfig(string $type): ?array
    {
        return self::DOCUMENTS[$type] ?? null;
    }

    private function documentPeriode(string $type, mixed $tahun): mixed
    {
        return $type === 'renstra' ? $this->renstraPeriode($tahun) : $tahun;
    }

    private function renstraPeriode(mixed $tahun): string
    {
        return (string) $tahun === '2024' ? 'P1' : 'P2';
    }

    private function uploadRules(string $type): array
    {
        $rules = ["{$type}_file" => 'required|mimes:pdf|max:10240'];

        if ($type === 'dipa') {
            $rules['id_pagu'] = 'required|numeric';
            $rules['id_gakyankum'] = 'required|numeric';
            $rules['id_dukman'] = 'required|numeric';
        }

        return $rules;
    }

    private function documentPayload(
        Request $request,
        string $type,
        mixed $idSatker,
        mixed $periode,
        int $perubahan,
        string $fileName
    ): array {
        $payload = [
            'id_satker' => $idSatker,
            'id_periode' => $periode,
            'id_perubahan' => $perubahan,
            'id_filename' => $fileName,
            'id_tglupload' => now()->format('d/m/Y h:i A'),
        ];

        if ($type === 'dipa') {
            $payload['id_pagu'] = $request->id_pagu;
            $payload['id_gakyankum'] = $request->id_gakyankum;
            $payload['id_dukman'] = $request->id_dukman;
        }

        return $payload;
    }

    private function findDocumentRecord(string $modelClass, int $id, mixed $idSatker)
    {
        return $modelClass::where('id', $id)
            ->where('id_satker', $idSatker)
            ->first();
    }

    private function fillDipaMetadata($fileRecord, Request $request, string $type): void
    {
        if ($type !== 'dipa') {
            return;
        }

        foreach (['id_pagu', 'id_gakyankum', 'id_dukman'] as $field) {
            if ($request->filled($field)) {
                $fileRecord->{$field} = $request->input($field);
            }
        }
    }

    private function replaceStoredFile($fileRecord, Request $request, mixed $idSatker, string $newFileName): void
    {
        $folderPath = $this->repositoryPath($idSatker);

        if ($fileRecord->id_filename) {
            $oldPath = $folderPath.'/'.$fileRecord->id_filename;

            if (Storage::disk('google')->exists($oldPath)) {
                Storage::disk('google')->delete($oldPath);
            }
        }

        Storage::disk('google')->putFileAs($folderPath, $request->file('file'), $newFileName);
    }

    private function repositoryPath(mixed $idSatker): string
    {
        return "uploads/repository/{$idSatker}";
    }

    private function buildFileName(string $prefix, mixed $tahun, int $perubahan): string
    {
        return "{$prefix}_{$tahun}_{$perubahan}.pdf";
    }

    private function documentTab(string $type): string
    {
        return $type === 'pk' ? 'perjanjian-kinerja' : $type;
    }

    private function documentSuccessFlashKey(string $type): string
    {
        return $type === 'pk' ? 'success-pk-file' : "success-{$type}";
    }

    private function pkTargetRows(): array
    {
        $tahun = session('tahun_terpilih');
        $targets = TargetPK::where('id_satker', session('id_satker'))
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn ($target) => (string) $target->indikator_id);

        $rows = [];
        $number = 1;

        foreach ($this->pkBidangs() as $bidang) {
            foreach ($this->pkIndikators($bidang->rumpun) as $indikator) {
                $indikatorId = (string) $indikator['id'];
                $target = $targets->get($indikatorId);

                $rows[] = [
                    'no' => (string) $number++,
                    'bidang' => (string) $bidang->bidang_nama,
                    'indikator_id' => $indikatorId,
                    'indikator' => (string) $indikator['indikator_nama'],
                    'target_tahun' => $this->formatExportValue($target?->target_tahun),
                    'target_triwulan_1' => $this->formatExportValue($target?->target_triwulan_1),
                    'target_triwulan_2' => $this->formatExportValue($target?->target_triwulan_2),
                    'target_triwulan_3' => $this->formatExportValue($target?->target_triwulan_3),
                    'target_triwulan_4' => $this->formatExportValue($target?->target_triwulan_4),
                ];
            }
        }

        return $rows;
    }

    private function pkBidangs()
    {
        $links = $this->pkIndikators()
            ->pluck('link')
            ->filter(fn ($link) => $link !== '')
            ->unique()
            ->values()
            ->all();

        if (count($links) === 0) {
            return collect();
        }

        $level = $this->currentSakipLevel();
        $satkernama = $this->satkerName();
        $query = Bidang::whereNotNull('bidang_level')
            ->whereIn('rumpun', $links);

        if (in_array($level, [0, 99], true)) {
            return $query
                ->where('hide', 0)
                ->orderBy('bidang_lokasi')
                ->orderBy('bidang_level')
                ->get();
        }

        if ($level === 1) {
            $kataTerakhir = strtolower(trim(strrchr(' '.$satkernama, ' '))) ?: strtolower($satkernama);

            return $query
                ->where('bidang_lokasi', $level)
                ->where('hide', 0)
                ->whereRaw("LOWER(REPLACE(bidang_nama, '_', ' ')) LIKE ?", ['%'.$kataTerakhir.'%'])
                ->orderBy('bidang_level')
                ->get();
        }

        if (str_starts_with(strtoupper($satkernama), 'CABJARI') || $level > 1) {
            return $query
                ->where('bidang_lokasi', $level)
                ->orderBy('bidang_level')
                ->get();
        }

        return $query->orderBy('id')->get();
    }

    private function pkIndikators(mixed $rumpun = null)
    {
        $level = $this->currentSakipLevel();
        $linkExpression = "COALESCE(NULLIF(indikator.link, ''), NULLIF(sastra.link, ''), sastra.id_sastra)";
        $lingkupExpression = "COALESCE(NULLIF(indikator.lingkup, ''), NULLIF(sastra.lingkup, ''), 0)";

        $query = DB::table('indikator_sastra as indikator')
            ->join('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->select([
                'indikator.kode_indikator',
                'indikator.nama_indikator',
                'sastra.id_sastra',
                'sastra.nama_sastra',
                DB::raw("{$linkExpression} as link"),
                DB::raw("{$lingkupExpression} as lingkup"),
            ])
            ->when($rumpun !== null && $rumpun !== '', fn ($query) => $query->whereRaw("{$linkExpression} = ?", [$rumpun]));

        match ($level) {
            1 => $query->whereIn(DB::raw($lingkupExpression), [0, 1]),
            2 => $query->whereIn(DB::raw($lingkupExpression), [0, 2, 5, 7]),
            3 => $query->whereIn(DB::raw($lingkupExpression), [0, 3, 5, 6, 7]),
            4 => $query->whereIn(DB::raw($lingkupExpression), [0, 4, 6, 7]),
            default => null,
        };

        return $query
            ->orderBy('sastra.id_sastra')
            ->orderBy('indikator.kode_indikator')
            ->get()
            ->map(fn ($indikator) => [
                'id' => (string) $indikator->kode_indikator,
                'kode_indikator' => (string) $indikator->kode_indikator,
                'indikator_nama' => (string) $indikator->nama_indikator,
                'link' => (string) $indikator->link,
                'nama_sastra' => (string) $indikator->nama_sastra,
                'tahun' => null,
                'lingkup' => (int) $indikator->lingkup,
            ]);
    }

    private function currentSakipLevel(): int
    {
        return (int) (auth()->user()?->id_sakip_level ?? session('id_sakip_level', 0));
    }

    private function satkerName(): string
    {
        return str_replace('_', ' ', (string) (auth()->user()?->satkernama ?? session('satkernama', '')));
    }

    private function formatExportValue(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    private function detectCsvDelimiter(string $path): string
    {
        $line = (string) file_get_contents($path, false, null, 0, 2048);
        $delimiters = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];

        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function normalizeCsvHeader(mixed $header): string
    {
        $header = strtolower(trim((string) $header));
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim((string) $header, '_');
    }

    private function combineCsvRow(array $headers, array $values): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = $values[$index] ?? null;
        }

        return $row;
    }

    private function isBlankCsvRow(array $values): bool
    {
        return collect($values)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isEmpty();
    }

    private function parseNullableNumber(mixed $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [null, true];
        }

        $normalized = str_replace(['%', ' '], '', $value);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized)
            ? [$normalized, true]
            : [null, false];
    }

    private function firstExistingTemplateVariable(TemplateProcessor $templateProcessor, array $variables): ?string
    {
        $availableVariables = $templateProcessor->getVariables();

        foreach ($variables as $variable) {
            if (in_array($variable, $availableVariables, true)) {
                return $variable;
            }
        }

        return null;
    }
}
