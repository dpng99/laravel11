<?php

namespace App\Http\Controllers;

use App\Services\IkssParameterService;
use App\Services\IkssReportDataService;
use App\Services\SatkerAccessService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IkssParameterController extends Controller
{
    public function catalog(Request $request, IkssParameterService $service, SatkerAccessService $access)
    {
        $year = $this->year($request);

        return response()->json([
            'data' => $service->catalog($year, $access->currentLevel()),
        ]);
    }

    public function values(Request $request, IkssParameterService $service, SatkerAccessService $access)
    {
        [$satkerId, $year, $quarter] = $this->scope($request, $access);

        return response()->json([
            'data' => $service->valuesForSatker($satkerId, $year, $quarter),
        ]);
    }

    public function store(Request $request, IkssParameterService $service, SatkerAccessService $access)
    {
        [$satkerId, $year, $quarter] = $this->scope($request, $access);
        $validated = $request->validate([
            'values' => 'required|array|min:1|max:1000',
            'values.*.parameter_id' => 'required|integer',
            'values.*.month' => 'nullable|integer|min:0|max:12',
            'values.*.value_decimal' => 'nullable|numeric',
            'values.*.value_text' => 'nullable|string|max:10000',
            'values.*.clear' => 'nullable|boolean',
            'values.*.status' => ['nullable', Rule::in(['draft', 'submitted'])],
            'values.*.metadata' => 'nullable|array',
            'values.*.items' => 'nullable|array|max:500',
            'values.*.items.*.item_key' => 'required|string|max:150',
            'values.*.items.*.item_label' => 'required|string|max:255',
            'values.*.items.*.value_decimal' => 'nullable|numeric',
            'values.*.items.*.value_text' => 'nullable|string|max:10000',
            'values.*.items.*.sort_order' => 'nullable|integer|min:0',
            'values.*.items.*.metadata' => 'nullable|array',
        ]);

        $result = $service->storeValues(
            $satkerId,
            $year,
            $quarter,
            $validated['values'],
            (string) $access->currentSatkerId()
        );

        return response()->json(['message' => 'Nilai parameter dan agregasi wilayah berhasil diperbarui.', 'data' => $result]);
    }

    public function recalculate(Request $request, IkssParameterService $service, SatkerAccessService $access)
    {
        [$satkerId, $year, $quarter] = $this->scope($request, $access);
        $satker = $access->satker($satkerId);
        $level = (int) $satker?->id_sakip_level;
        $result = $level === 2
            ? $service->recalculateKejati((string) $satker->id_kejati, $year, $quarter)
            : $service->recalculateSatker($satkerId, $year, $quarter);

        if (in_array($level, [3, 4], true) && $satker?->id_kejati) {
            $result['regional'] = $service->recalculateKejati((string) $satker->id_kejati, $year, $quarter);
        }

        return response()->json(['message' => 'Perhitungan IKSS selesai.', 'data' => $result]);
    }

    public function summary(Request $request, IkssParameterService $service, SatkerAccessService $access)
    {
        [$satkerId, $year, $quarter] = $this->scope($request, $access);

        return response()->json(['data' => $service->summary($satkerId, $year, $quarter)]);
    }

    public function reportData(Request $request, IkssReportDataService $service, SatkerAccessService $access)
    {
        [$satkerId, $year, $quarter] = $this->scope($request, $access);
        $satker = $access->satker($satkerId);

        return response()->json([
            'data' => $service->build($satkerId, $year, $quarter, (int) $satker?->id_sakip_level),
        ]);
    }

    public function saveDefinition(Request $request, IkssParameterService $service, SatkerAccessService $access)
    {
        $access->abortUnlessAdmin();
        $validated = $request->validate([
            'ikss_id' => 'required|string|max:100',
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:ikss_parameters,id',
            'group_id' => 'nullable|integer|exists:ikss_parameter_groups,id',
            'parameter_role' => ['nullable', Rule::in(['input', 'component', 'numerator', 'denominator', 'result', 'context', 'narrative'])],
            'input_mode' => ['nullable', Rule::in(['scalar', 'list', 'table'])],
            'source_type' => ['nullable', Rule::in(['manual', 'legacy', 'target', 'system', 'formula'])],
            'source_reference' => 'nullable|string|max:150',
            'legacy_indicator_id' => 'nullable|string|max:100',
            'value_type' => ['required', Rule::in(['number', 'integer', 'percentage', 'currency', 'boolean', 'text'])],
            'unit' => 'nullable|string|max:50',
            'period_type' => ['required', Rule::in(['monthly', 'quarterly', 'annual'])],
            'calculation_method' => ['required', Rule::in(['input', 'sum', 'average', 'weighted_average', 'ratio', 'percentage', 'min', 'max', 'latest'])],
            'aggregation_method' => ['required', Rule::in(['sum', 'average', 'weighted_average', 'ratio', 'percentage', 'min', 'max', 'latest'])],
            'aggregation_scope' => ['nullable', Rule::in(['children', 'self_and_children'])],
            'entry_levels' => 'nullable|array',
            'entry_levels.*' => 'integer|in:1,2,3,4',
            'aggregate_to_levels' => 'nullable|array',
            'aggregate_to_levels.*' => 'integer|in:1,2,3,4',
            'formula_config' => 'nullable|array',
            'decimal_places' => 'nullable|integer|min:0|max:6',
            'sort_order' => 'nullable|integer|min:0',
            'is_result' => 'nullable|boolean',
            'is_required' => 'nullable|boolean',
            'include_in_report' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'valid_from_year' => 'nullable|integer|min:2000|max:2200',
            'valid_until_year' => 'nullable|integer|min:2000|max:2200',
            'dependencies' => 'nullable|array',
            'dependencies.*.source_parameter_id' => 'required|integer|exists:ikss_parameters,id',
            'dependencies.*.role' => ['required', Rule::in(['component', 'numerator', 'denominator', 'weight'])],
            'dependencies.*.weight' => 'nullable|numeric',
            'dependencies.*.sort_order' => 'nullable|integer|min:0',
        ]);

        return response()->json([
            'message' => 'Definisi parameter IKSS berhasil disimpan.',
            'data' => $service->saveDefinition($validated),
        ]);
    }

    private function scope(Request $request, SatkerAccessService $access): array
    {
        $validated = $request->validate([
            'satker_id' => 'nullable|string|max:50',
            'year' => 'nullable|integer|min:2000|max:2200',
            'quarter' => 'required|integer|min:1|max:4',
        ]);
        $satkerId = (string) ($validated['satker_id'] ?? $access->currentSatkerId());
        $access->abortUnlessCanAccessSatker($satkerId);

        return [$satkerId, (int) ($validated['year'] ?? $this->year($request)), (int) $validated['quarter']];
    }

    private function year(Request $request): int
    {
        return (int) $request->input('year', session('tahun_terpilih', date('Y')));
    }
}
