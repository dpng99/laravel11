<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeasurementConfigurationController;
use App\Http\Controllers\Api\MeasurementEvaluationController;
use App\Http\Controllers\Api\MeasurementAuditController;
use App\Http\Controllers\Api\LkeEvaluationController;

/**
 * API Routes untuk LKJIP-LKE Precision Measurement Framework
 * Base: /api/v1
 */

// ============ MEASUREMENT CONFIGURATION ============
Route::prefix('measurement/config')->group(function () {
    // Threshold management
    Route::get('/thresholds/{level}', [MeasurementConfigurationController::class, 'getThresholds']);
    Route::post('/thresholds/{level}', [MeasurementConfigurationController::class, 'updateThresholds']);

    // Indicator metadata
    Route::get('/indicators/{level}', [MeasurementConfigurationController::class, 'getIndicators']);
    Route::post('/indicators', [MeasurementConfigurationController::class, 'createIndicator']);

    // Framework versions
    Route::get('/frameworks', [MeasurementConfigurationController::class, 'getFrameworks']);
    Route::get('/frameworks/{version}', [MeasurementConfigurationController::class, 'getFrameworkDetail']);
});

// ============ MEASUREMENT EVALUATION ============
Route::prefix('measurement')->group(function () {
    // Single evaluation
    Route::post('/evaluate', [MeasurementEvaluationController::class, 'evaluate']);

    // Batch evaluation
    Route::post('/batch-evaluate', [MeasurementEvaluationController::class, 'batchEvaluate']);

    // Performance data
    Route::get('/performance/{indikator_id}', [MeasurementEvaluationController::class, 'getPerformanceHistory']);
    Route::get('/weighted-average/{level}', [MeasurementEvaluationController::class, 'calculateWeightedAverage']);
    Route::get('/comparison/{indikator_id}', [MeasurementEvaluationController::class, 'periodComparison']);
    Route::get('/status/{indikator_id}', [MeasurementEvaluationController::class, 'getStatus']);

    // Audit trails
    Route::get('/audit-log/{indikator_id}', [MeasurementEvaluationController::class, 'getAuditLog']);
});

// ============ MEASUREMENT AUDIT ============
Route::prefix('audit')->group(function () {
    // Audit log queries
    Route::get('/log', [MeasurementAuditController::class, 'getLog']);
    Route::get('/changes/{indikator_id}', [MeasurementAuditController::class, 'traceChanges']);
    Route::get('/status-transitions/{indikator_id}', [MeasurementAuditController::class, 'statusTransitions']);

    // User activity
    Route::get('/user-activity', [MeasurementAuditController::class, 'userActivity']);

    // Compliance reporting
    Route::get('/compliance-report', [MeasurementAuditController::class, 'complianceReport']);

    // Export
    Route::post('/export', [MeasurementAuditController::class, 'export']);
});

// ============ LKE EVALUATION ============
Route::prefix('lke')->group(function () {
    // Evidence scoring
    Route::get('/evidence/{bukti_id}', [LkeEvaluationController::class, 'scoreEvidence']);

    // Criteria compliance
    Route::get('/criteria/{criteria_code}/satker/{satker_code}', [LkeEvaluationController::class, 'criteriaCompliance']);

    // Component compliance
    Route::get('/subkomponen/{subkomponen_code}/satker/{satker_code}', [LkeEvaluationController::class, 'subkomponenCompliance']);
    Route::get('/komponen/{komponen_id}/satker/{satker_code}', [LkeEvaluationController::class, 'komponenCompliance']);

    // Comprehensive report
    Route::get('/report/satker/{satker_code}', [LkeEvaluationController::class, 'generateReport']);
    Route::get('/summary', [LkeEvaluationController::class, 'summaryAll']);

    // Verification
    Route::post('/verify', [LkeEvaluationController::class, 'recordVerification']);

    // Audit log
    Route::get('/audit-log/satker/{satker_code}', [LkeEvaluationController::class, 'getAuditLog']);
});
