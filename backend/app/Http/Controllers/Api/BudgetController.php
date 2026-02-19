<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Update campaign budget.
     */
    public function updateBudget(UpdateBudgetRequest $request, Campaign $campaign): JsonResponse
    {
        $campaign = $this->budgetService->updateBudget(
            $campaign,
            $request->new_budget
        );

        $latestHistory = $campaign->budgetHistories()
            ->orderBy('changed_at', 'desc')
            ->first();

        return response()->json([
            'data' => new CampaignResource($campaign),
            'history' => $latestHistory,
            'message' => 'Budget updated successfully',
        ]);
    }

    /**
     * Get budget change history.
     */
    public function getBudgetHistory(Request $request, Campaign $campaign): JsonResponse
    {
        $startDate = $request->from ? Carbon::parse($request->from) : null;
        $endDate = $request->to ? Carbon::parse($request->to) : null;

        $history = $this->budgetService->getBudgetHistory(
            $campaign,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $history,
            'meta' => [
                'total' => $history->count(),
            ],
        ]);
    }
}
