<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public auth routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Campaign Management
    Route::apiResource('campaigns', CampaignController::class);

    // Campaign-specific actions
    Route::post('campaigns/{campaign}/pause', [CampaignController::class, 'pause']);
    Route::post('campaigns/{campaign}/resume', [CampaignController::class, 'resume']);

    // Budget Management
    Route::post('campaigns/{campaign}/budget', [BudgetController::class, 'updateBudget']);
    Route::get('campaigns/{campaign}/budget-history', [BudgetController::class, 'getBudgetHistory']);

    // Cost Data
    Route::get('campaigns/{campaign}/costs', [CostController::class, 'getCosts']);
    Route::get('campaigns/{campaign}/daily-summary', [CostController::class, 'getDailySummary']);
    Route::post('campaigns/{campaign}/generate-costs', [CostController::class, 'generateCosts']);

    // Statistics
    Route::get('campaigns/{campaign}/stats', [CostController::class, 'getStatistics']);
});
