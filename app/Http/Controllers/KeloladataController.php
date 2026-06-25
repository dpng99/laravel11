<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Indikator;
use App\Models\Saspro;
use App\Models\indikator_sastra_new;
use App\Models\IkssParameter;
use App\Models\IkssParameterGroup;
use App\Models\saspro_indikator_new;
use App\Models\saspro_new;
use App\Models\sastra_new;
use App\Services\LkeBuktiDukungService;
use App\Services\SatkerAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class KeloladataController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 25, 50];
    private const STATUS_VALUES = ['0', '1'];

    public function index(Request $request)
    {
        $this->ensureAdmin();

        if (! session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $perPage = $this->perPage($request);

        return Inertia::render('KelolaData', [
            'tahun' => session('tahun_terpilih'),
            'bidangs' => $this->bidangData($request, $perPage),
            'saspros' => $this->sasproData($request, $perPage),
            'indikators' => $this->indikatorData($request, $perPage),
            'bidangall' => $this->bidangOptions(),
            'sasproAll' => $this->sasproOptions(),
            'masterDataTabs' => $this->masterDataTabs($request),
            'ikssMasterData' => $this->ikssMasterData(),
            'lkeMasterData' => $this->lkeMasterData($request),
            'lingkupOptions' => $this->lingkupOptions(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'canManagePohonKinerja' => $this->isAdmin(),
            'filters' => $request->only([
                'search',
                'pohon_search',
                'tab',
                'per_page',
            ]),
        ]);
    }

    // ---------------------------------------------------------------------
    // Master IKSS dan parameter
    // ---------------------------------------------------------------------

    private function ikssMasterData(): array
    {
        if (! Schema::hasTable('ikss_parameters') || ! Schema::hasTable('ikss_parameter_groups')) {
            return ['available' => false, 'groups' => [], 'parameters' => [], 'ikssOptions' => []];
        }

        $ikssOptions = DB::table('indikator_sastra as ikss')
            ->leftJoin('sakip_sastra_new as ss', 'ikss.kode_sastra', '=', 'ss.id_sastra')
            ->orderBy('ikss.kode_sastra')
            ->orderByRaw($this->orderExpression('indikator_sastra', 'ikss.kode_indikator', 'ikss'))
            ->get(['ikss.kode_indikator', 'ikss.nama_indikator', 'ikss.kode_sastra', 'ss.nama_sastra'])
            ->map(fn ($row) => [
                'value' => (string) $row->kode_indikator,
                'label' => "{$row->kode_sastra} / {$row->kode_indikator} - {$row->nama_indikator}",
            ])
            ->values()
            ->all();

        $groups = IkssParameterGroup::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($group) => [
                ...$group->toArray(),
                'is_active' => (int) $group->is_active,
                'settings_json' => $group->settings ? json_encode($group->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '',
            ])
            ->values()
            ->all();

        $parameters = IkssParameter::query()
            ->with('dependencies')
            ->orderBy('ikss_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($parameter) => [
                ...$parameter->toArray(),
                'is_result' => (int) $parameter->is_result,
                'is_required' => (int) $parameter->is_required,
                'include_in_report' => (int) $parameter->include_in_report,
                'is_active' => (int) $parameter->is_active,
                'entry_levels' => $parameter->entry_levels ?? [],
                'aggregate_to_levels' => $parameter->aggregate_to_levels ?? [],
                'formula_config_json' => $parameter->formula_config ? json_encode($parameter->formula_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '',
                'dependencies' => $parameter->dependencies->map(fn ($dependency) => [
                    'source_parameter_id' => $dependency->source_parameter_id,
                    'role' => $dependency->role,
                    'weight' => $dependency->weight,
                    'sort_order' => $dependency->sort_order,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'available' => true,
            'groups' => $groups,
            'parameters' => $parameters,
            'ikssOptions' => $ikssOptions,
        ];
    }

    public function ikssGroupStore(Request $request)
    {
        return $this->saveIkssGroup($request);
    }

    public function ikssGroupUpdate(Request $request, int $id)
    {
        return $this->saveIkssGroup($request, $id);
    }

    private function saveIkssGroup(Request $request, ?int $id = null)
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'ikss_id' => ['nullable', 'string', 'max:100', Rule::exists('indikator_sastra', 'kode_indikator')],
            'parent_id' => ['nullable', 'integer', Rule::exists('ikss_parameter_groups', 'id')],
            'code' => ['required', 'string', 'max:150', Rule::unique('ikss_parameter_groups', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'section_code' => ['nullable', 'string', 'max:100'],
            'group_type' => ['required', Rule::in(['table', 'section', 'list', 'narrative'])],
            'settings_json' => ['nullable', 'json'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
        $validated['settings'] = filled($validated['settings_json'] ?? null)
            ? json_decode($validated['settings_json'], true)
            : null;
        unset($validated['settings_json']);

        IkssParameterGroup::query()->updateOrCreate(['id' => $id], $validated);
        $this->refreshIkssCatalogCache();

        return Redirect::back()->with('success', 'Kelompok parameter IKSS berhasil disimpan.');
    }

    public function ikssGroupDestroy(int $id)
    {
        $this->ensureAdmin();
        IkssParameterGroup::query()->findOrFail($id)->delete();
        $this->refreshIkssCatalogCache();

        return Redirect::back()->with('success', 'Kelompok parameter IKSS berhasil dihapus.');
    }

    public function ikssParameterStore(Request $request)
    {
        return $this->saveIkssParameter($request);
    }

    public function ikssParameterUpdate(Request $request, int $id)
    {
        return $this->saveIkssParameter($request, $id);
    }

    private function saveIkssParameter(Request $request, ?int $id = null)
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'ikss_id' => ['required', 'string', 'max:100', Rule::exists('indikator_sastra', 'kode_indikator')],
            'parent_id' => ['nullable', 'integer', Rule::exists('ikss_parameters', 'id')],
            'group_id' => ['nullable', 'integer', Rule::exists('ikss_parameter_groups', 'id')],
            'code' => ['required', 'string', 'max:100', Rule::unique('ikss_parameters', 'code')->where('ikss_id', $request->input('ikss_id'))->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parameter_role' => ['required', Rule::in(['input', 'component', 'numerator', 'denominator', 'result', 'context', 'narrative'])],
            'input_mode' => ['required', Rule::in(['scalar', 'list', 'table'])],
            'source_type' => ['required', Rule::in(['manual', 'legacy', 'target', 'system', 'formula'])],
            'source_reference' => ['nullable', 'string', 'max:150'],
            'legacy_indicator_id' => ['nullable', 'string', 'max:100'],
            'value_type' => ['required', Rule::in(['number', 'integer', 'percentage', 'currency', 'boolean', 'text'])],
            'unit' => ['nullable', 'string', 'max:50'],
            'period_type' => ['required', Rule::in(['monthly', 'quarterly', 'annual'])],
            'calculation_method' => ['required', Rule::in(['input', 'sum', 'average', 'weighted_average', 'ratio', 'percentage', 'min', 'max', 'latest'])],
            'aggregation_method' => ['required', Rule::in(['sum', 'average', 'weighted_average', 'ratio', 'percentage', 'min', 'max', 'latest'])],
            'aggregation_scope' => ['required', Rule::in(['children', 'self_and_children'])],
            'entry_levels' => ['nullable', 'array'],
            'entry_levels.*' => ['integer', Rule::in([1, 2, 3, 4])],
            'aggregate_to_levels' => ['nullable', 'array'],
            'aggregate_to_levels.*' => ['integer', Rule::in([1, 2, 3, 4])],
            'formula_config_json' => ['nullable', 'json'],
            'decimal_places' => ['required', 'integer', 'between:0,6'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_result' => ['required', 'boolean'],
            'is_required' => ['required', 'boolean'],
            'include_in_report' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'valid_from_year' => ['nullable', 'integer', 'between:2000,2200'],
            'valid_until_year' => ['nullable', 'integer', 'between:2000,2200', 'gte:valid_from_year'],
            'dependencies' => ['nullable', 'array'],
            'dependencies.*.source_parameter_id' => ['required', 'integer', Rule::exists('ikss_parameters', 'id'), Rule::notIn(array_filter([$id]))],
            'dependencies.*.role' => ['required', Rule::in(['component', 'numerator', 'denominator', 'weight'])],
            'dependencies.*.weight' => ['nullable', 'numeric'],
            'dependencies.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $dependencies = $validated['dependencies'] ?? [];
        $validated['formula_config'] = filled($validated['formula_config_json'] ?? null)
            ? json_decode($validated['formula_config_json'], true)
            : null;
        unset($validated['dependencies'], $validated['formula_config_json']);

        DB::transaction(function () use ($validated, $dependencies, $id) {
            $parameter = IkssParameter::query()->updateOrCreate(['id' => $id], $validated);
            $parameter->dependencies()->delete();
            foreach ($dependencies as $index => $dependency) {
                $parameter->dependencies()->create([...$dependency, 'sort_order' => $dependency['sort_order'] ?? $index]);
            }
        });
        $this->refreshIkssCatalogCache();

        return Redirect::back()->with('success', 'Definisi parameter IKSS berhasil disimpan.');
    }

    public function ikssParameterDestroy(int $id)
    {
        $this->ensureAdmin();
        IkssParameter::query()->findOrFail($id)->delete();
        $this->refreshIkssCatalogCache();

        return Redirect::back()->with('success', 'Parameter IKSS berhasil dihapus.');
    }

    public function ikssExport()
    {
        $this->ensureAdmin();

        $groups = IkssParameterGroup::query()
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($group) => [
                ...$group->only($this->ikssGroupImportFields()),
                'parent_code' => $group->parent?->code,
            ])->all();
        $parameters = IkssParameter::query()
            ->with(['parent', 'group', 'dependencies.sourceParameter'])
            ->orderBy('ikss_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($parameter) => [
                ...$parameter->only($this->ikssParameterImportFields()),
                'group_code' => $parameter->group?->code,
                'parent_code' => $parameter->parent?->code,
                'dependencies' => $parameter->dependencies->map(fn ($dependency) => [
                    'source_ikss_id' => $dependency->sourceParameter?->ikss_id,
                    'source_code' => $dependency->sourceParameter?->code,
                    'role' => $dependency->role,
                    'weight' => $dependency->weight,
                    'sort_order' => $dependency->sort_order,
                ])->values()->all(),
            ])->all();
        $payload = [
            'format' => 'sicana-ikss-catalog',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'groups' => $groups,
            'parameters' => $parameters,
        ];

        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'katalog-ikss-' . now()->format('Ymd-His') . '.json',
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public function ikssImport(Request $request)
    {
        $this->ensureAdmin();
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $payload = json_decode($request->file('file')->get(), true);
        if (! is_array($payload) || ($payload['format'] ?? null) !== 'sicana-ikss-catalog' || (int) ($payload['version'] ?? 0) !== 1) {
            throw ValidationException::withMessages(['file' => 'Format file tidak dikenali. Gunakan file JSON hasil ekspor katalog IKSS.']);
        }
        if (! is_array($payload['groups'] ?? null) || ! is_array($payload['parameters'] ?? null)) {
            throw ValidationException::withMessages(['file' => 'File harus memiliki daftar groups dan parameters.']);
        }

        $stats = DB::transaction(function () use ($payload) {
            $stats = ['groups' => 0, 'parameters' => 0, 'dependencies' => 0];
            $groupFields = $this->ikssGroupImportFields();
            $parameterFields = $this->ikssParameterImportFields();

            foreach ($payload['groups'] as $row) {
                if (blank($row['code'] ?? null) || blank($row['name'] ?? null)) {
                    throw ValidationException::withMessages(['file' => 'Setiap kelompok wajib memiliki code dan name.']);
                }
                IkssParameterGroup::query()->updateOrCreate(
                    ['code' => $row['code']],
                    collect($row)->only($groupFields)->except('parent_id')->all()
                );
                $stats['groups']++;
            }
            foreach ($payload['groups'] as $row) {
                IkssParameterGroup::query()->where('code', $row['code'])->update([
                    'parent_id' => filled($row['parent_code'] ?? null)
                        ? IkssParameterGroup::query()->where('code', $row['parent_code'])->value('id')
                        : null,
                ]);
            }

            foreach ($payload['parameters'] as $row) {
                if (blank($row['ikss_id'] ?? null) || blank($row['code'] ?? null) || blank($row['name'] ?? null)) {
                    throw ValidationException::withMessages(['file' => 'Setiap parameter wajib memiliki ikss_id, code, dan name.']);
                }
                if (! DB::table('indikator_sastra')->where('kode_indikator', $row['ikss_id'])->exists()) {
                    throw ValidationException::withMessages(['file' => "IKSS {$row['ikss_id']} belum tersedia pada master IKSS."]);
                }
                $values = collect($row)->only($parameterFields)->except(['parent_id', 'group_id'])->all();
                $values['group_id'] = filled($row['group_code'] ?? null)
                    ? IkssParameterGroup::query()->where('code', $row['group_code'])->value('id')
                    : null;
                IkssParameter::query()->updateOrCreate(
                    ['ikss_id' => $row['ikss_id'], 'code' => $row['code']],
                    $values
                );
                $stats['parameters']++;
            }

            foreach ($payload['parameters'] as $row) {
                $parameter = IkssParameter::query()->where('ikss_id', $row['ikss_id'])->where('code', $row['code'])->firstOrFail();
                $parameter->update([
                    'parent_id' => filled($row['parent_code'] ?? null)
                        ? IkssParameter::query()->where('ikss_id', $row['ikss_id'])->where('code', $row['parent_code'])->value('id')
                        : null,
                ]);
                $parameter->dependencies()->delete();
                foreach ($row['dependencies'] ?? [] as $index => $dependency) {
                    $source = IkssParameter::query()
                        ->where('ikss_id', $dependency['source_ikss_id'] ?? $row['ikss_id'])
                        ->where('code', $dependency['source_code'] ?? '')
                        ->first();
                    if (! $source) {
                        throw ValidationException::withMessages(['file' => "Sumber relasi {$dependency['source_code']} tidak ditemukan."]);
                    }
                    $parameter->dependencies()->create([
                        'source_parameter_id' => $source->id,
                        'role' => $dependency['role'] ?? 'component',
                        'weight' => $dependency['weight'] ?? null,
                        'sort_order' => $dependency['sort_order'] ?? $index,
                    ]);
                    $stats['dependencies']++;
                }
            }

            return $stats;
        });
        $this->refreshIkssCatalogCache();

        return Redirect::back()->with('success', "Impor selesai: {$stats['groups']} kelompok, {$stats['parameters']} parameter, dan {$stats['dependencies']} relasi diperbarui/ditambahkan.");
    }

    private function ikssGroupImportFields(): array
    {
        return ['ikss_id', 'code', 'name', 'description', 'section_code', 'group_type', 'settings', 'sort_order', 'is_active'];
    }

    private function ikssParameterImportFields(): array
    {
        return [
            'ikss_id', 'code', 'name', 'description', 'parameter_role', 'input_mode', 'source_type',
            'source_reference', 'legacy_indicator_id', 'value_type', 'unit', 'period_type',
            'calculation_method', 'aggregation_method', 'aggregation_scope', 'entry_levels',
            'aggregate_to_levels', 'formula_config', 'decimal_places', 'sort_order', 'is_result',
            'is_required', 'include_in_report', 'is_active', 'valid_from_year', 'valid_until_year',
        ];
    }

    // ---------------------------------------------------------------------
    // Master LKE
    // ---------------------------------------------------------------------

    private function lkeMasterData(Request $request): array
    {
        if (! Schema::hasTable('lke_komponen')) {
            return ['year' => 2025, 'years' => [], 'tabs' => [], 'counts' => []];
        }

        $year = (int) $request->input('lke_year', session('tahun_terpilih', 2025));
        $availableYears = collect($this->lkeTables())
            ->flatMap(fn ($table) => Schema::hasColumn($table, 'tahun') ? DB::table($table)->distinct()->pluck('tahun') : [2025])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
        $years = collect(range(2024, ((int) date('Y')) + 5))
            ->merge($availableYears)
            ->push($year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
        $options = fn ($table, $value, $label) => DB::table($table)
            ->where('tahun', $year)
            ->orderBy($value)
            ->get([$value, $label])
            ->map(fn ($row) => ['value' => (string) $row->{$value}, 'label' => "{$row->{$value}} - {$row->{$label}}"])
            ->values()
            ->all();
        $components = $options('lke_komponen', 'id', 'nama');
        $subcomponents = $options('lke_subkomponen', 'kode', 'nama');
        $criteria = $options('lke_kriteria', 'kode', 'nama');
        $evidence = $options('lke_buktidukung', 'id', 'dokumen');

        $sourceTableOptions = app(LkeBuktiDukungService::class)->sourceTableOptions();
        $tabs = [
            $this->lkeTab($year, 'komponen', 'Komponen', 'lke_komponen', 'id', [
                ['name' => 'no', 'label' => 'Nomor', 'type' => 'number', 'required' => true],
                ['name' => 'nama', 'label' => 'Nama Komponen', 'required' => true, 'md' => 9],
            ], ['no', 'nama']),
            $this->lkeTab($year, 'subkomponen', 'Subkomponen', 'lke_subkomponen', 'id', [
                ['name' => 'komponen_id', 'label' => 'Komponen', 'type' => 'select', 'options' => $components, 'required' => true],
                ['name' => 'kode', 'label' => 'Kode', 'required' => true],
                ['name' => 'nama', 'label' => 'Nama Subkomponen', 'multiline' => true, 'required' => true, 'md' => 12],
            ], ['kode', 'nama', 'komponen_id']),
            $this->lkeTab($year, 'kriteria', 'Kriteria & Bukti Dukung', 'lke_kriteria', 'id', [
                ['name' => 'subkomponen_id', 'label' => 'Subkomponen', 'type' => 'select', 'options' => $subcomponents, 'required' => true],
                ['name' => 'kode', 'label' => 'Kode Kriteria', 'required' => true],
                ['name' => 'nama', 'label' => 'Kriteria', 'multiline' => true, 'required' => true, 'md' => 12],
                ['name' => 'dokumen_bukti', 'label' => 'Deskripsi Dokumen Bukti', 'multiline' => true, 'md' => 12],
                ['name' => 'bukti_dukung_ids', 'label' => 'Bukti Dukung (dapat dipilih banyak)', 'type' => 'multiselect', 'options' => $evidence, 'md' => 12],
                ...collect(['eselon_i', 'kejati', 'kejari', 'cabjari'])->map(fn ($name) => ['name' => $name, 'label' => strtoupper(str_replace('_', ' ', $name)), 'type' => 'boolean', 'default' => 1, 'md' => 3])->all(),
            ], ['kode', 'nama', 'subkomponen_id'], true),
            $this->lkeTab($year, 'buktidukung', 'Master Bukti Dukung', 'lke_buktidukung', 'id', [
                ['name' => 'dokumen', 'label' => 'Nama Dokumen', 'multiline' => true, 'required' => true, 'md' => 12],
                ['name' => 'format_nama_file', 'label' => 'Format Nama File', 'placeholder' => 'Contoh: renstra_{tahun}_{iterasi}.{ext}', 'md' => 12],
                ['name' => 'keterangan', 'label' => 'Keterangan', 'multiline' => true, 'md' => 12],
                ['name' => 'ada_di_sistem', 'label' => 'Data Tersedia di Sistem', 'type' => 'boolean', 'default' => 0],
                ['name' => 'tabel_sumber', 'label' => 'Relasi Tabel Sistem', 'type' => 'select', 'options' => $sourceTableOptions],
            ], ['dokumen', 'format_nama_file', 'ada_di_sistem', 'tabel_sumber']),
            $this->lkeTab($year, 'parameter', 'Parameter Nilai Akhir', 'lke_parameter', 'id', [
                ['name' => 'kriteria_id', 'label' => 'Kriteria', 'type' => 'select', 'options' => $criteria],
                ['name' => 'nilai', 'label' => 'Nilai / Jawaban', 'required' => true],
                ['name' => 'skor', 'label' => 'Skor Akhir', 'type' => 'number', 'required' => true],
                ['name' => 'keterangan', 'label' => 'Keterangan Penilaian', 'multiline' => true, 'md' => 12],
            ], ['kriteria_id', 'nilai', 'skor', 'keterangan']),
        ];

        return [
            'year' => $year,
            'years' => $years,
            'availableYears' => $availableYears,
            'tabs' => $tabs,
            'counts' => collect($tabs)->mapWithKeys(fn ($tab) => [$tab['key'] => count($tab['rows'])])->all(),
        ];
    }

    private function lkeTab(int $year, string $key, string $label, string $table, string $idKey, array $fields, array $columns, bool $withEvidence = false): array
    {
        $rows = DB::table($table)->where('tahun', $year)->orderBy($idKey)->get()->map(function ($row) use ($withEvidence, $year) {
            $data = (array) $row;
            if ($withEvidence) {
                $data['bukti_dukung_ids'] = DB::table('lke_gabungan')
                    ->where('tahun', $year)
                    ->where('kriteria_id', $row->kode)
                    ->pluck('buktidukung_id')
                    ->map(fn ($id) => (string) $id)
                    ->all();
            }
            return $data;
        })->all();

        return [
            'key' => $key,
            'label' => $label,
            'idKey' => $idKey,
            'fields' => $fields,
            'columns' => collect($columns)->map(fn ($column) => ['field' => $column, 'headerName' => ucwords(str_replace('_', ' ', $column)), 'flex' => 1, 'minWidth' => 130])->all(),
            'rows' => $rows,
            'routes' => ['store' => 'keloladata.lke.store', 'update' => 'keloladata.lke.update', 'destroy' => 'keloladata.lke.destroy'],
        ];
    }

    public function lkeStore(Request $request, string $type)
    {
        return $this->saveLke($request, $type);
    }

    public function lkeUpdate(Request $request, string $type, string $id)
    {
        return $this->saveLke($request, $type, $id);
    }

    private function saveLke(Request $request, string $type, ?string $id = null)
    {
        $this->ensureAdmin();
        $year = (int) $request->validate(['tahun' => ['required', 'integer', 'between:2000,2200']])['tahun'];
        [$table, $rules] = $this->lkeRules($type, $id, $year);
        $validated = $request->validate($rules);
        $validated['tahun'] = $year;
        if ($type === 'buktidukung' && ! ($validated['ada_di_sistem'] ?? false)) {
            $validated['tabel_sumber'] = null;
        }
        $evidenceIds = $validated['bukti_dukung_ids'] ?? null;
        unset($validated['bukti_dukung_ids']);

        DB::transaction(function () use ($table, $validated, $evidenceIds, $type, $id, $year) {
            $oldCriteriaCode = $type === 'kriteria' && $id !== null
                ? DB::table('lke_kriteria')->where('id', $id)->where('tahun', $year)->value('kode')
                : null;
            $oldSubcomponentCode = $type === 'subkomponen' && $id !== null
                ? DB::table('lke_subkomponen')->where('id', $id)->where('tahun', $year)->value('kode')
                : null;
            $payload = $this->existingPayload($table, [...$validated, 'updated_at' => now()]);
            if ($id === null) {
                $payload = $this->existingPayload($table, [...$payload, 'created_at' => now()]);
                $idColumn = collect(Schema::getColumns($table))->firstWhere('name', 'id');
                if ($idColumn && ! ($idColumn['auto_increment'] ?? false)) {
                    $payload['id'] = ((int) DB::table($table)->lockForUpdate()->max('id')) + 1;
                    DB::table($table)->insert($payload);
                    $id = (string) $payload['id'];
                } else {
                    $id = (string) DB::table($table)->insertGetId($payload);
                }
            } else {
                DB::table($table)->where('id', $id)->where('tahun', $year)->update($payload);
            }

            if ($type === 'subkomponen' && $oldSubcomponentCode && $oldSubcomponentCode !== $validated['kode']) {
                DB::table('lke_kriteria')->where('tahun', $year)->where('subkomponen_id', $oldSubcomponentCode)->update(['subkomponen_id' => $validated['kode']]);
                DB::table('lke_gabungan')->where('tahun', $year)->where('sub_komponen_id', $oldSubcomponentCode)->update(['sub_komponen_id' => $validated['kode']]);
            }

            if ($type === 'kriteria') {
                $row = DB::table('lke_kriteria')->where('id', $id)->where('tahun', $year)->first();
                DB::table('lke_gabungan')->where('tahun', $year)->whereIn('kriteria_id', array_filter([$oldCriteriaCode, $row->kode]))->delete();
                if ($oldCriteriaCode && $oldCriteriaCode !== $row->kode) {
                    DB::table('lke_parameter')->where('tahun', $year)->where('kriteria_id', $oldCriteriaCode)->update(['kriteria_id' => $row->kode]);
                }
                foreach (array_unique($evidenceIds ?? []) as $evidenceId) {
                    DB::table('lke_gabungan')->insert([
                        'komponen_id' => DB::table('lke_subkomponen')->where('tahun', $year)->where('kode', $row->subkomponen_id)->value('komponen_id'),
                        'sub_komponen_id' => $row->subkomponen_id,
                        'kriteria_id' => $row->kode,
                        'buktidukung_id' => $evidenceId,
                        'tahun' => $year,
                    ]);
                }
            }
        });

        return Redirect::back()->with('success', 'Master LKE berhasil disimpan.');
    }

    public function lkeDestroy(string $type, string $id)
    {
        $this->ensureAdmin();
        $year = (int) request()->validate(['tahun' => ['required', 'integer', 'between:2000,2200']])['tahun'];
        [$table] = $this->lkeRules($type, $id, $year);
        DB::transaction(function () use ($type, $table, $id, $year) {
            if ($type === 'kriteria') {
                $code = DB::table($table)->where('id', $id)->where('tahun', $year)->value('kode');
                DB::table('lke_gabungan')->where('tahun', $year)->where('kriteria_id', $code)->delete();
                DB::table('lke_parameter')->where('tahun', $year)->where('kriteria_id', $code)->delete();
            }
            if ($type === 'buktidukung') {
                DB::table('lke_gabungan')->where('tahun', $year)->where('buktidukung_id', $id)->delete();
            }
            DB::table($table)->where('id', $id)->where('tahun', $year)->delete();
        });

        return Redirect::back()->with('success', 'Master LKE berhasil dihapus.');
    }

    public function lkeCopyYear(Request $request)
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'source_year' => ['required', 'integer', 'between:2000,2200'],
            'target_year' => ['required', 'integer', 'between:2000,2200', 'different:source_year'],
            'replace' => ['nullable', 'boolean'],
        ]);
        $sourceYear = (int) $validated['source_year'];
        $targetYear = (int) $validated['target_year'];
        $replace = (bool) ($validated['replace'] ?? false);

        if (! DB::table('lke_komponen')->where('tahun', $sourceYear)->exists()) {
            throw ValidationException::withMessages(['source_year' => 'Master LKE pada tahun sumber tidak tersedia.']);
        }
        if (! $replace && DB::table('lke_komponen')->where('tahun', $targetYear)->exists()) {
            throw ValidationException::withMessages(['target_year' => 'Tahun tujuan sudah memiliki master LKE. Aktifkan opsi timpa untuk menggantinya.']);
        }

        DB::transaction(function () use ($sourceYear, $targetYear, $replace) {
            if ($replace) {
                foreach (['lke_gabungan', 'lke_parameter', 'lke_kriteria', 'lke_subkomponen', 'lke_komponen', 'lke_buktidukung'] as $table) {
                    DB::table($table)->where('tahun', $targetYear)->delete();
                }
            }

            $componentMap = [];
            foreach (DB::table('lke_komponen')->where('tahun', $sourceYear)->orderBy('id')->get() as $row) {
                $oldId = $row->id;
                $componentMap[$oldId] = $this->insertLkeMasterRow('lke_komponen', $this->copiedLkePayload($row, $targetYear));
            }

            foreach (DB::table('lke_subkomponen')->where('tahun', $sourceYear)->orderBy('id')->get() as $row) {
                $payload = $this->copiedLkePayload($row, $targetYear);
                $payload['komponen_id'] = $componentMap[$row->komponen_id];
                $this->insertLkeMasterRow('lke_subkomponen', $payload);
            }

            foreach (DB::table('lke_kriteria')->where('tahun', $sourceYear)->orderBy('id')->get() as $row) {
                $this->insertLkeMasterRow('lke_kriteria', $this->copiedLkePayload($row, $targetYear));
            }

            $evidenceMap = [];
            foreach (DB::table('lke_buktidukung')->where('tahun', $sourceYear)->orderBy('id')->get() as $row) {
                $evidenceMap[$row->id] = $this->insertLkeMasterRow('lke_buktidukung', $this->copiedLkePayload($row, $targetYear));
            }

            foreach (DB::table('lke_gabungan')->where('tahun', $sourceYear)->orderBy('id')->get() as $row) {
                $payload = $this->copiedLkePayload($row, $targetYear);
                $payload['komponen_id'] = $componentMap[$row->komponen_id] ?? null;
                $payload['buktidukung_id'] = $evidenceMap[$row->buktidukung_id];
                $this->insertLkeMasterRow('lke_gabungan', $payload);
            }

            foreach (DB::table('lke_parameter')->where('tahun', $sourceYear)->orderBy('id')->get() as $row) {
                $this->insertLkeMasterRow('lke_parameter', $this->copiedLkePayload($row, $targetYear));
            }
        });

        return Redirect::route('keloladata', ['tab' => 'lke', 'lke_year' => $targetYear])
            ->with('success', "Master LKE {$sourceYear} berhasil disalin menjadi tahun {$targetYear}.");
    }

    private function lkeRules(string $type, ?string $id, int $year): array
    {
        return match ($type) {
            'komponen' => ['lke_komponen', ['no' => ['required', 'integer', 'min:1'], 'nama' => ['required', 'string']]],
            'subkomponen' => ['lke_subkomponen', [
                'komponen_id' => ['required', 'integer', Rule::exists('lke_komponen', 'id')->where('tahun', $year)],
                'kode' => ['required', 'string', 'max:50', Rule::unique('lke_subkomponen', 'kode')->where('tahun', $year)->ignore($id)],
                'nama' => ['required', 'string'],
            ]],
            'kriteria' => ['lke_kriteria', [
                'subkomponen_id' => ['required', 'string', Rule::exists('lke_subkomponen', 'kode')->where('tahun', $year)],
                'kode' => ['required', 'string', 'max:50', Rule::unique('lke_kriteria', 'kode')->where('tahun', $year)->ignore($id)],
                'nama' => ['required', 'string'],
                'dokumen_bukti' => ['nullable', 'string'],
                'bukti_dukung_ids' => ['nullable', 'array'],
                'bukti_dukung_ids.*' => ['integer', Rule::exists('lke_buktidukung', 'id')->where('tahun', $year)],
                'eselon_i' => ['required', 'boolean'], 'kejati' => ['required', 'boolean'],
                'kejari' => ['required', 'boolean'], 'cabjari' => ['required', 'boolean'],
            ]],
            'buktidukung' => ['lke_buktidukung', [
                'dokumen' => ['required', 'string'],
                'format_nama_file' => ['nullable', 'string', 'max:255'],
                'keterangan' => ['nullable', 'string'],
                'ada_di_sistem' => ['required', 'boolean'],
                'tabel_sumber' => [
                    Rule::requiredIf(fn () => request()->boolean('ada_di_sistem')),
                    'nullable',
                    'string',
                    Rule::in(collect(app(LkeBuktiDukungService::class)->sourceTableOptions())->pluck('value')->all()),
                ],
            ]],
            'parameter' => ['lke_parameter', [
                'kriteria_id' => ['nullable', 'string', Rule::exists('lke_kriteria', 'kode')->where('tahun', $year)],
                'nilai' => ['required', 'string', 'max:255'], 'skor' => ['required', 'numeric'], 'keterangan' => ['nullable', 'string'],
            ]],
            default => abort(404),
        };
    }

    private function refreshIkssCatalogCache(): void
    {
        $cache = Cache::store('file');
        $cache->put('ikss-parameter-catalog-version', (int) $cache->get('ikss-parameter-catalog-version', 1) + 1);
    }

    private function lkeTables(): array
    {
        return ['lke_komponen', 'lke_subkomponen', 'lke_kriteria', 'lke_buktidukung', 'lke_gabungan', 'lke_parameter'];
    }

    private function copiedLkePayload(object $row, int $targetYear): array
    {
        return collect((array) $row)
            ->except(['id', 'created_at', 'updated_at'])
            ->put('tahun', $targetYear)
            ->all();
    }

    private function insertLkeMasterRow(string $table, array $payload): int
    {
        $payload = $this->existingPayload($table, [
            ...$payload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idColumn = collect(Schema::getColumns($table))->firstWhere('name', 'id');

        if ($idColumn && ! ($idColumn['auto_increment'] ?? false)) {
            $payload['id'] = ((int) DB::table($table)->lockForUpdate()->max('id')) + 1;
            DB::table($table)->insert($payload);

            return (int) $payload['id'];
        }

        return (int) DB::table($table)->insertGetId($payload);
    }

    // ---------------------------------------------------------------------
    // Data halaman utama
    // ---------------------------------------------------------------------

    private function bidangData(Request $request, int $perPage)
    {
        return Bidang::select(['id', 'bidang_nama', 'bidang_level', 'bidang_lokasi', 'rumpun', 'hide'])
            ->when($this->search($request) !== '', function ($query) use ($request) {
                $search = $this->search($request);

                $query->where(function ($q) use ($search) {
                    $q->where('bidang_nama', 'like', "%{$search}%")
                        ->orWhere('rumpun', 'like', "%{$search}%");
                });
            })
            ->orderBy('bidang_lokasi')
            ->orderBy('bidang_level')
            ->orderBy('rumpun')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'bidang_page')
            ->withQueryString();
    }

    private function sasproData(Request $request, int $perPage)
    {
        return Saspro::with('bidang:id,bidang_nama')
            ->when($this->search($request) !== '', function ($query) use ($request) {
                $search = $this->search($request);

                $query->where(function ($q) use ($search) {
                    $q->where('saspro_nama', 'like', "%{$search}%")
                        ->orWhere('saspro_penjelasan', 'like', "%{$search}%")
                        ->orWhere('target', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('tahun')
            ->orderBy('link')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'saspro_page')
            ->withQueryString();
    }

    private function indikatorData(Request $request, int $perPage)
    {
        return Indikator::with([
                'bidangById:id,bidang_nama',
                'bidangByLink:id,bidang_nama,rumpun',
                'saspro:id,saspro_nama,tahun',
            ])
            ->when($this->search($request) !== '', function ($query) use ($request) {
                $search = $this->search($request);

                $query->where(function ($q) use ($search) {
                    $q->where('indikator_nama', 'like', "%{$search}%")
                        ->orWhere('sub_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator_penjelasan', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('tahun')
            ->orderBy('link')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'indikator_page')
            ->withQueryString();
    }

    private function bidangOptions()
    {
        return Bidang::select(['id', 'bidang_nama', 'rumpun'])
            ->where('hide', 0)
            ->orderBy('bidang_lokasi')
            ->orderBy('bidang_level')
            ->orderBy('rumpun')
            ->get();
    }

    private function sasproOptions()
    {
        return Saspro::select(['id', 'saspro_nama', 'tahun', 'link'])
            ->orderByDesc('tahun')
            ->orderBy('link')
            ->orderBy('id')
            ->get();
    }

    // ---------------------------------------------------------------------
    // Data Bidang
    // ---------------------------------------------------------------------

    public function bidang(Request $request)
    {
        $this->ensureAdmin();

        return $this->storeOrUpdateBidang($request);
    }

    public function storeOrUpdateBidang(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validateBidang($request);

        try {
            DB::transaction(function () use ($validated) {
                $payload = [
                    'bidang_nama' => $validated['bidang_nama'],
                    'bidang_level' => $validated['bidang_level'],
                    'bidang_lokasi' => $validated['bidang_lokasi'],
                    'rumpun' => $validated['rumpun'],
                    'hide' => $validated['hide'],
                ];

                if (isset($validated['id'])) {
                    Bidang::findOrFail($validated['id'])->update($payload);

                    return;
                }

                Bidang::create($payload);
            });

            $message = isset($validated['id'])
                ? 'Data bidang berhasil diperbarui.'
                : 'Data bidang berhasil disimpan.';

            return Redirect::back()->with('success', $message);
        } catch (Throwable $e) {
            Log::error('Keloladata bidang save failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan data bidang.');
        }
    }

    public function edit($id)
    {
        $this->ensureAdmin();

        return response()->json(Bidang::findOrFail($id));
    }

    public function destroy($id)
    {
        $this->ensureAdmin();

        $bidang = Bidang::findOrFail($id);

        if (
            Saspro::where('link', $bidang->id)->exists()
            || Indikator::where('link', $bidang->id)->exists()
        ) {
            return Redirect::back()->with('error', 'Bidang tidak dapat dihapus karena masih dipakai Saspro atau Indikator.');
        }

        try {
            $bidang->delete();

            return Redirect::back()->with('success', 'Data bidang berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata bidang delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menghapus data bidang.');
        }
    }

    // ---------------------------------------------------------------------
    // Data Saspro lama
    // ---------------------------------------------------------------------

    public function sasproStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validateSaspro($request);

        try {
            DB::transaction(fn () => Saspro::create($this->sasproPayload($validated)));

            return Redirect::back()->with('success', 'Data Saspro berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata saspro store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan data Saspro.');
        }
    }

    public function sasproUpdate(Request $request, $id)
    {
        $this->ensureAdmin();

        $saspro = Saspro::findOrFail($id);
        $validated = $this->validateSaspro($request);

        try {
            DB::transaction(fn () => $saspro->update($this->sasproPayload($validated, $saspro)));

            return Redirect::back()->with('success', 'Data Saspro berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata saspro update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui data Saspro.');
        }
    }

    public function destroySaspro($id)
    {
        $this->ensureAdmin();

        $saspro = Saspro::findOrFail($id);

        if (Indikator::where('id_saspro', $saspro->id)->exists()) {
            return Redirect::back()->with('error', 'Saspro tidak dapat dihapus karena masih dipakai Indikator.');
        }

        try {
            $saspro->delete();

            return Redirect::back()->with('success', 'Data Saspro berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata saspro delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menghapus data Saspro.');
        }
    }

    // ---------------------------------------------------------------------
    // Data Indikator lama
    // ---------------------------------------------------------------------

    public function indikator(Request $request)
    {
        $this->ensureAdmin();

        return $this->storeIndikator($request);
    }

    public function storeIndikator(Request $request)
    {
        $this->ensureAdmin();

        $payload = $this->indikatorPayload($request);

        try {
            DB::transaction(fn () => Indikator::create($payload));

            return Redirect::back()->with('success', 'Data Indikator berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata indikator store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan data Indikator.');
        }
    }

    public function updateIndikator(Request $request, $id)
    {
        $this->ensureAdmin();

        $indikator = Indikator::findOrFail($id);
        $payload = $this->indikatorPayload($request, $indikator);

        try {
            DB::transaction(fn () => $indikator->update($payload));

            return Redirect::back()->with('success', 'Data Indikator berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata indikator update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui data Indikator.');
        }
    }

    public function deleteIndikator($id)
    {
        $this->ensureAdmin();

        try {
            Indikator::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data Indikator berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata indikator delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menghapus data Indikator.');
        }
    }

    // ---------------------------------------------------------------------
    // Tab master pohon kinerja
    // ---------------------------------------------------------------------

    private function masterDataTabs(Request $request): array
    {
        $sastraOptions = DB::table('sakip_sastra_new')
            ->select(['id_sastra', 'nama_sastra'])
            ->orderByRaw($this->orderExpression('sakip_sastra_new', 'id_sastra'))
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->id_sastra,
                'label' => "{$row->id_sastra} - {$row->nama_sastra}",
            ])
            ->values()
            ->all();

        $sasproOptions = DB::table('sakip_saspro_new')
            ->select(['id_saspro', 'nama_saspro'])
            ->orderBy('id_sastra')
            ->orderByRaw($this->orderExpression('sakip_saspro_new', 'id_saspro'))
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->id_saspro,
                'label' => "{$row->id_saspro} - {$row->nama_saspro}",
            ])
            ->values()
            ->all();

        $lingkupOptions = $this->lingkupOptions();
        $statusOptions = $this->statusOptions();
        $defaultLingkup = $this->defaultLingkupValue();

        return [
            [
                'key' => 'sastra',
                'label' => 'Sasaran Strategis',
                'addLabel' => 'Tambah Sastra',
                'idKey' => 'id_sastra',
                'pageKey' => 'pohon_sastra_page',
                'routes' => [
                    'store' => 'pohon.sastra.store',
                    'update' => 'pohon.sastra.update',
                    'destroy' => 'pohon.sastra.destroy',
                ],
                'columns' => [
                    ['field' => 'id_sastra', 'headerName' => 'Kode', 'width' => 110],
                    ['field' => 'nama_sastra', 'headerName' => 'Sasaran Strategis', 'flex' => 1, 'minWidth' => 260],
                    ['field' => 'target', 'headerName' => 'Target', 'width' => 160],
                    ['field' => 'hide', 'headerName' => 'Status', 'width' => 110],
                ],
                'fields' => [
                    ['name' => 'id_sastra', 'label' => 'Kode Sastra', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'nama_sastra', 'label' => 'Nama Sasaran Strategis', 'required' => true, 'md' => 8],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'target', 'label' => 'Target', 'md' => 4],
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonSastraData($request),
            ],
            [
                'key' => 'indikatorSastra',
                'label' => 'Indikator Sastra',
                'addLabel' => 'Tambah Indikator Sastra',
                'idKey' => 'kode_indikator',
                'pageKey' => 'pohon_indikator_sastra_page',
                'routes' => [
                    'store' => 'pohon.indikator-sastra.store',
                    'update' => 'pohon.indikator-sastra.update',
                    'destroy' => 'pohon.indikator-sastra.destroy',
                ],
                'columns' => [
                    ['field' => 'kode_indikator', 'headerName' => 'Kode', 'width' => 130],
                    ['field' => 'nama_sastra', 'headerName' => 'Nama Sastra', 'flex' => 1, 'minWidth' => 220],
                    ['field' => 'nama_indikator', 'headerName' => 'Indikator', 'flex' => 1, 'minWidth' => 280],
                ],
                'fields' => [
                    ['name' => 'kode_indikator', 'label' => 'Kode Indikator', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'kode_sastra', 'label' => 'Sasaran Strategis', 'type' => 'select', 'options' => $sastraOptions, 'required' => true, 'md' => 8],
                    ['name' => 'nama_indikator', 'label' => 'Nama Indikator', 'required' => true, 'md' => 12],
                    ['name' => 'deskripsi_indikator_sastra', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonIndikatorSastraData($request),
            ],
            [
                'key' => 'saspro',
                'label' => 'Sasaran Program',
                'addLabel' => 'Tambah Saspro',
                'idKey' => 'id_saspro',
                'pageKey' => 'pohon_saspro_page',
                'routes' => [
                    'store' => 'pohon.saspro.store',
                    'update' => 'pohon.saspro.update',
                    'destroy' => 'pohon.saspro.destroy',
                ],
                'columns' => [
                    ['field' => 'id_saspro', 'headerName' => 'Kode', 'width' => 120],
                    ['field' => 'nama_sastra', 'headerName' => 'Nama Sastra', 'flex' => 1, 'minWidth' => 220],
                    ['field' => 'nama_saspro', 'headerName' => 'Sasaran Program', 'flex' => 1, 'minWidth' => 280],
                ],
                'fields' => [
                    ['name' => 'id_saspro', 'label' => 'Kode Saspro', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'id_sastra', 'label' => 'Sasaran Strategis', 'type' => 'select', 'options' => $sastraOptions, 'required' => true, 'md' => 8],
                    ['name' => 'nama_saspro', 'label' => 'Nama Sasaran Program', 'required' => true, 'md' => 12],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonSasproData($request),
            ],
            [
                'key' => 'indikatorSaspro',
                'label' => 'Indikator Saspro',
                'addLabel' => 'Tambah Indikator Saspro',
                'idKey' => 'kode_indikator',
                'pageKey' => 'pohon_indikator_saspro_page',
                'routes' => [
                    'store' => 'pohon.indikator-saspro.store',
                    'update' => 'pohon.indikator-saspro.update',
                    'destroy' => 'pohon.indikator-saspro.destroy',
                ],
                'columns' => [
                    ['field' => 'kode_indikator', 'headerName' => 'Kode', 'width' => 130],
                    ['field' => 'nama_saspro', 'headerName' => 'Nama Saspro', 'flex' => 1, 'minWidth' => 220],
                    ['field' => 'nama_indikator', 'headerName' => 'Indikator', 'flex' => 1, 'minWidth' => 280],
                ],
                'fields' => [
                    ['name' => 'kode_indikator', 'label' => 'Kode Indikator', 'required' => true, 'disabledOnEdit' => true, 'md' => 4],
                    ['name' => 'kode_saspro', 'label' => 'Sasaran Program', 'type' => 'select', 'options' => $sasproOptions, 'required' => true, 'md' => 8],
                    ['name' => 'nama_indikator', 'label' => 'Nama Indikator', 'required' => true, 'md' => 12],
                    ['name' => 'deskripsi_indikator_saspro', 'label' => 'Deskripsi', 'multiline' => true, 'md' => 12],
                    ['name' => 'tahun', 'label' => 'Tahun', 'md' => 4],
                    ['name' => 'lingkup', 'label' => 'Lingkup', 'type' => 'select', 'options' => $lingkupOptions, 'md' => 4, 'default' => $defaultLingkup],
                    ['name' => 'hide', 'label' => 'Status', 'type' => 'select', 'options' => $statusOptions, 'required' => true, 'md' => 4, 'default' => '0'],
                ],
                'rows' => $this->pohonIndikatorSasproData($request),
            ],
        ];
    }

    private function pohonSastraData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('sakip_sastra_new')
            ->select($this->selectExistingColumns('sakip_sastra_new', [
                'id_sastra',
                'nama_sastra',
                'deskripsi',
                'link',
                'lingkup',
                'target',
                'tahun',
                'hide',
                'urutan',
            ]))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id_sastra', 'like', "%{$search}%")
                        ->orWhere('nama_sastra', 'like', "%{$search}%");
                });
            })
            ->orderByRaw($this->orderExpression('sakip_sastra_new', 'id_sastra'))
            ->paginate($this->perPage($request), ['*'], 'pohon_sastra_page')
            ->withQueryString();
    }

    private function pohonIndikatorSastraData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('indikator_sastra as indikator')
            ->leftJoin('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->select([
                'indikator.kode_indikator',
                'indikator.kode_sastra',
                'indikator.nama_indikator',
                'indikator.deskripsi_indikator_sastra',
                'sastra.nama_sastra',
                ...$this->selectExistingColumns('indikator_sastra', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'indikator'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('indikator.kode_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                        ->orWhere('sastra.nama_sastra', 'like', "%{$search}%");
                });
            })
            ->orderBy('indikator.kode_sastra')
            ->orderByRaw($this->orderExpression('indikator_sastra', 'indikator.kode_indikator', 'indikator'))
            ->paginate($this->perPage($request), ['*'], 'pohon_indikator_sastra_page')
            ->withQueryString();
    }

    private function pohonSasproData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('sakip_saspro_new as saspro')
            ->leftJoin('sakip_sastra_new as sastra', 'saspro.id_sastra', '=', 'sastra.id_sastra')
            ->select([
                'saspro.id_saspro',
                'saspro.id_sastra',
                'saspro.nama_saspro',
                'saspro.deskripsi',
                'sastra.nama_sastra',
                ...$this->selectExistingColumns('sakip_saspro_new', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'saspro'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('saspro.id_saspro', 'like', "%{$search}%")
                        ->orWhere('saspro.nama_saspro', 'like', "%{$search}%")
                        ->orWhere('sastra.nama_sastra', 'like', "%{$search}%");
                });
            })
            ->orderBy('saspro.id_sastra')
            ->orderByRaw($this->orderExpression('sakip_saspro_new', 'saspro.id_saspro', 'saspro'))
            ->paginate($this->perPage($request), ['*'], 'pohon_saspro_page')
            ->withQueryString();
    }

    private function pohonIndikatorSasproData(Request $request)
    {
        $search = $this->pohonSearch($request);

        return DB::table('indikator_saspro as indikator')
            ->leftJoin('sakip_sastra_new as sastra', 'indikator.kode_sastra', '=', 'sastra.id_sastra')
            ->leftJoin('sakip_saspro_new as saspro', 'indikator.kode_saspro', '=', 'saspro.id_saspro')
            ->select([
                'indikator.kode_indikator',
                'indikator.kode_sastra',
                'indikator.kode_saspro',
                'indikator.nama_indikator',
                'indikator.deskripsi_indikator_saspro',
                'sastra.nama_sastra',
                'saspro.nama_saspro',
                ...$this->selectExistingColumns('indikator_saspro', ['link', 'lingkup', 'tahun', 'hide', 'urutan'], 'indikator'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('indikator.kode_indikator', 'like', "%{$search}%")
                        ->orWhere('indikator.nama_indikator', 'like', "%{$search}%")
                        ->orWhere('sastra.nama_sastra', 'like', "%{$search}%")
                        ->orWhere('saspro.nama_saspro', 'like', "%{$search}%");
                });
            })
            ->orderBy('indikator.kode_sastra')
            ->orderBy('indikator.kode_saspro')
            ->orderByRaw($this->orderExpression('indikator_saspro', 'indikator.kode_indikator', 'indikator'))
            ->paginate($this->perPage($request), ['*'], 'pohon_indikator_saspro_page')
            ->withQueryString();
    }

    // ---------------------------------------------------------------------
    // CRUD pohon kinerja
    // ---------------------------------------------------------------------

    public function pohonSastraStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonSastra($request, null);

        try {
            DB::transaction(fn () => sastra_new::create($this->existingPayload('sakip_sastra_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran strategis berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon sastra store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan sasaran strategis.');
        }
    }

    public function pohonSastraUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $sastra = sastra_new::findOrFail($id);
        $validated = $this->validatePohonSastra($request, $id);

        try {
            DB::transaction(fn () => $sastra->update($this->existingPayload('sakip_sastra_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran strategis berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon sastra update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui sasaran strategis.');
        }
    }

    public function pohonSastraDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            sastra_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data sasaran strategis berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon sastra delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    public function pohonIndikatorSastraStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonIndikatorSastra($request, null);

        try {
            DB::transaction(fn () => indikator_sastra_new::create($this->existingPayload('indikator_sastra', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran strategis berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator sastra store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan indikator sasaran strategis.');
        }
    }

    public function pohonIndikatorSastraUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $indikator = indikator_sastra_new::findOrFail($id);
        $validated = $this->validatePohonIndikatorSastra($request, $id);

        try {
            DB::transaction(fn () => $indikator->update($this->existingPayload('indikator_sastra', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran strategis berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator sastra update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui indikator sasaran strategis.');
        }
    }

    public function pohonIndikatorSastraDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            indikator_sastra_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data indikator sasaran strategis berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator sastra delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    public function pohonSasproStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonSaspro($request, null);

        try {
            DB::transaction(fn () => saspro_new::create($this->existingPayload('sakip_saspro_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran program berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon saspro store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan sasaran program.');
        }
    }

    public function pohonSasproUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $saspro = saspro_new::findOrFail($id);
        $validated = $this->validatePohonSaspro($request, $id);

        try {
            DB::transaction(fn () => $saspro->update($this->existingPayload('sakip_saspro_new', $validated)));

            return Redirect::back()->with('success', 'Data sasaran program berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon saspro update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui sasaran program.');
        }
    }

    public function pohonSasproDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            saspro_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data sasaran program berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon saspro delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    public function pohonIndikatorSasproStore(Request $request)
    {
        $this->ensureAdmin();

        $validated = $this->validatePohonIndikatorSaspro($request, null);

        try {
            DB::transaction(fn () => saspro_indikator_new::create($this->existingPayload('indikator_saspro', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran program berhasil disimpan.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator saspro store failed', ['error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal menyimpan indikator sasaran program.');
        }
    }

    public function pohonIndikatorSasproUpdate(Request $request, string $id)
    {
        $this->ensureAdmin();

        $indikator = saspro_indikator_new::findOrFail($id);
        $validated = $this->validatePohonIndikatorSaspro($request, $id);

        try {
            DB::transaction(fn () => $indikator->update($this->existingPayload('indikator_saspro', $validated)));

            return Redirect::back()->with('success', 'Indikator sasaran program berhasil diperbarui.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator saspro update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Gagal memperbarui indikator sasaran program.');
        }
    }

    public function pohonIndikatorSasproDestroy(string $id)
    {
        $this->ensureAdmin();

        try {
            saspro_indikator_new::findOrFail($id)->delete();

            return Redirect::back()->with('success', 'Data indikator sasaran program berhasil dihapus.');
        } catch (Throwable $e) {
            Log::error('Keloladata pohon indikator saspro delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return Redirect::back()->with('error', 'Tidak dapat dihapus karena masih terhubung dengan data lain.');
        }
    }

    // ---------------------------------------------------------------------
    // Validasi dan payload
    // ---------------------------------------------------------------------

    private function validateBidang(Request $request): array
    {
        return $request->validate([
            'id' => ['nullable', 'integer', Rule::exists('sinori_sakip_bidang', 'id')],
            'bidang_nama' => ['required', 'string', 'max:255'],
            'bidang_level' => ['required', 'integer', 'min:0'],
            'bidang_lokasi' => ['required', 'integer', 'between:1,5'],
            'rumpun' => ['required', 'integer', 'min:0'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
        ]);
    }

    private function validateSaspro(Request $request): array
    {
        return $request->validate([
            'link' => ['required', 'integer', Rule::exists('sinori_sakip_bidang', 'id')],
            'saspro_nama' => ['required', 'string', 'max:255'],
            'penjelasan_saspro' => ['nullable', 'string'],
            'saspro_penjelasan' => ['nullable', 'string'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'target' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['nullable', 'integer', Rule::in(self::STATUS_VALUES)],
        ]);
    }

    private function sasproPayload(array $validated, ?Saspro $existing = null): array
    {
        return [
            'link' => $validated['link'] ?? $existing?->link,
            'saspro_nama' => $validated['saspro_nama'] ?? $existing?->saspro_nama,
            'saspro_penjelasan' => $validated['penjelasan_saspro']
                ?? $validated['saspro_penjelasan']
                ?? $existing?->saspro_penjelasan,
            'lingkup' => $validated['lingkup'] ?? $existing?->lingkup ?? $this->defaultLingkupValue(),
            'target' => $validated['target'] ?? $existing?->target,
            'tahun' => $validated['tahun'] ?? $existing?->tahun,
            'hide' => $validated['hide'] ?? $existing?->hide ?? 0,
        ];
    }

    private function indikatorPayload(Request $request, ?Indikator $existing = null): array
    {
        $isUpdate = $existing !== null;

        $validated = $request->validate([
            'bidang' => [$isUpdate ? 'nullable' : 'required', 'integer', Rule::exists('sinori_sakip_bidang', 'id')],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'id_saspro' => [$isUpdate ? 'nullable' : 'required', 'integer', Rule::exists('sinori_sakip_saspro', 'id')],
            'indikator_nama' => ['required', 'string', 'max:255'],
            'indikator_pembilang' => ['nullable', 'string', 'max:255'],
            'indikator_penyebut' => ['nullable', 'string', 'max:255'],
            'indikator_penjelasan' => ['nullable', 'string'],
            'sub_indikator' => ['nullable', 'string'],
            'tahun' => ['nullable', 'string', 'max:10'],
            'indikator_penghitungan' => ['nullable', 'string'],
            'tren' => ['nullable', 'string', 'max:255'],
        ]);

        $bidangId = $validated['bidang'] ?? $existing?->link;
        $sasproId = $validated['id_saspro'] ?? $existing?->id_saspro;

        if ($bidangId && $sasproId && ! Saspro::whereKey($sasproId)->where('link', $bidangId)->exists()) {
            throw ValidationException::withMessages([
                'id_saspro' => 'Saspro yang dipilih tidak sesuai dengan bidang.',
            ]);
        }

        return [
            'link' => $bidangId,
            'lingkup' => $validated['lingkup'] ?? $existing?->lingkup ?? $this->defaultLingkupValue(),
            'id_saspro' => $sasproId,
            'indikator_nama' => $validated['indikator_nama'],
            'indikator_pembilang' => $validated['indikator_pembilang'] ?? $existing?->indikator_pembilang,
            'indikator_penyebut' => $validated['indikator_penyebut'] ?? $existing?->indikator_penyebut,
            'indikator_penjelasan' => $validated['indikator_penjelasan'] ?? $existing?->indikator_penjelasan,
            'sub_indikator' => $validated['sub_indikator'] ?? $existing?->sub_indikator,
            'indikator_penghitungan' => $validated['indikator_penghitungan'] ?? $existing?->indikator_penghitungan,
            'tahun' => $validated['tahun'] ?? $existing?->tahun,
            'tren' => $validated['tren'] ?? $existing?->tren,
        ];
    }

    private function validatePohonSastra(Request $request, ?string $id): array
    {
        return $request->validate([
            'id_sastra' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('sakip_sastra_new', 'id_sastra')->ignore($id, 'id_sastra'),
            ],
            'nama_sastra' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'target' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validatePohonIndikatorSastra(Request $request, ?string $id): array
    {
        return $request->validate([
            'kode_indikator' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('indikator_sastra', 'kode_indikator')->ignore($id, 'kode_indikator'),
            ],
            'kode_sastra' => ['required', 'string', Rule::exists('sakip_sastra_new', 'id_sastra')],
            'nama_indikator' => ['required', 'string', 'max:255'],
            'deskripsi_indikator_sastra' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validatePohonSaspro(Request $request, ?string $id): array
    {
        return $request->validate([
            'id_saspro' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('sakip_saspro_new', 'id_saspro')->ignore($id, 'id_saspro'),
            ],
            'id_sastra' => ['required', 'string', Rule::exists('sakip_sastra_new', 'id_sastra')],
            'nama_saspro' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validatePohonIndikatorSaspro(Request $request, ?string $id): array
    {
        $validated = $request->validate([
            'kode_indikator' => [
                $id ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('indikator_saspro', 'kode_indikator')->ignore($id, 'kode_indikator'),
            ],
            'kode_saspro' => ['required', 'string', Rule::exists('sakip_saspro_new', 'id_saspro')],
            'nama_indikator' => ['required', 'string', 'max:255'],
            'deskripsi_indikator_saspro' => ['nullable', 'string'],
            'link' => ['nullable', 'string', 'max:255'],
            'lingkup' => ['nullable', 'string', Rule::in($this->lingkupValues())],
            'tahun' => ['nullable', 'string', 'max:10'],
            'hide' => ['required', 'integer', Rule::in(self::STATUS_VALUES)],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['kode_sastra'] = DB::table('sakip_saspro_new')
            ->where('id_saspro', $validated['kode_saspro'])
            ->value('id_sastra');

        if (! $validated['kode_sastra']) {
            throw ValidationException::withMessages([
                'kode_saspro' => 'Saspro yang dipilih tidak memiliki relasi Sastra.',
            ]);
        }

        return $validated;
    }

    // ---------------------------------------------------------------------
    // Helper umum
    // ---------------------------------------------------------------------

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    private function search(Request $request): string
    {
        return trim((string) $request->input('search', ''));
    }

    private function pohonSearch(Request $request): string
    {
        return trim((string) $request->input('pohon_search', $request->input('search', '')));
    }

    private function selectExistingColumns(string $table, array $columns, ?string $alias = null): array
    {
        return collect($columns)
            ->map(function ($column) use ($table, $alias) {
                if (! Schema::hasColumn($table, $column)) {
                    return DB::raw("NULL as {$column}");
                }

                return $alias ? "{$alias}.{$column}" : $column;
            })
            ->all();
    }

    private function orderExpression(string $table, string $fallbackColumn, ?string $alias = null): string
    {
        if (! Schema::hasColumn($table, 'urutan')) {
            return $fallbackColumn;
        }

        $urutanColumn = $alias ? "{$alias}.urutan" : 'urutan';

        return "COALESCE({$urutanColumn}, 999999), {$fallbackColumn}";
    }

    private function existingPayload(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->all();
    }

    private function lingkupOptions(): array
    {
        return Bidang::select(['rumpun', 'bidang_nama'])
            ->where('bidang_level', '1')
            ->where('hide', '0')
            ->whereNotNull('rumpun')
            ->get()
            ->filter(fn ($bidang) => trim((string) $bidang->rumpun) !== '')
            ->unique(fn ($bidang) => (string) $bidang->rumpun)
            ->sortBy(fn ($bidang) => str_pad((string) $bidang->rumpun, 10, '0', STR_PAD_LEFT))
            ->map(fn ($bidang) => [
                'value' => (string) $bidang->rumpun,
                'label' => (string) $bidang->bidang_nama,
            ])
            ->values()
            ->all();
    }

    private function lingkupValues(): array
    {
        $values = collect($this->lingkupOptions())
            ->pluck('value')
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        return $values ?: [$this->defaultLingkupValue()];
    }

    private function defaultLingkupValue(): string
    {
        $firstOption = collect($this->lingkupOptions())->first();

        return (string) ($firstOption['value'] ?? '0');
    }

    private function statusOptions(): array
    {
        return [
            ['value' => '0', 'label' => 'Tampil'],
            ['value' => '1', 'label' => 'Sembunyikan'],
        ];
    }

    private function isAdmin(): bool
    {
        return app(SatkerAccessService::class)->isAdmin();
    }

    private function ensureAdmin(): void
    {
        abort_unless($this->isAdmin(), 403);
    }
}
