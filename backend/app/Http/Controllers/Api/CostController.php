<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignStatisticsService;
use App\Services\CostGenerationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CostController extends Controller
{
    public function __construct(
        protected CostGenerationService $costGenerationService,
        protected CampaignStatisticsService $statisticsService
    ) {}

    /**
     * Get cost history for a campaign.
     */
    public function getCosts(Request $request, Campaign $campaign): JsonResponse
    {
        $query = $campaign->costs();

        if ($request->from) {
            $query->where('generated_at', '>=', Carbon::parse($request->from));
        }

        if ($request->to) {
            $query->where('generated_at', '<=', Carbon::parse($request->to));
        }

        $granularity = $request->granularity ?? 'daily';

        if ($granularity === 'daily') {
            $costs = $query->orderBy('generated_at', 'desc')->paginate(100);
        } else {
            $costs = $query->orderBy('generated_at', 'desc')->paginate(500);
        }

        $summary = [
            'total' => $costs->sum('amount'),
            'avg' => $costs->avg('amount'),
            'count' => $costs->count(),
        ];

        return response()->json([
            'data' => $costs->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $costs->currentPage(),
                'total' => $costs->total(),
                'per_page' => $costs->perPage(),
            ],
        ]);
    }

    /**
     * Get daily summary for a campaign.
     */
    public function getDailySummary(Request $request, Campaign $campaign): JsonResponse
    {
        $startDate = Carbon::parse($request->from ?? now()->subMonths(3));
        $endDate = Carbon::parse($request->to ?? now());

        $summary = $this->statisticsService->getDailySummary(
            $campaign,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $summary,
        ]);
    }

    /**
     * Generate costs for a campaign (dev/testing).
     */
    public function generateCosts(Request $request, Campaign $campaign): JsonResponse
    {
        $startDate = Carbon::parse($request->from_date ?? now()->subMonths(3));
        $endDate = Carbon::parse($request->to_date ?? now());

        $costsGenerated = $this->costGenerationService->generateCostsForPeriod(
            $campaign,
            $startDate,
            $endDate
        );

        return response()->json([
            'message' => 'Costs generated successfully',
            'costs_generated' => $costsGenerated,
            'period' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * Get comprehensive statistics for a campaign.
     */
    public function getStatistics(Request $request, Campaign $campaign): JsonResponse
    {
        $period = $request->period ?? 'today';
        $startDate = $request->from ? Carbon::parse($request->from) : null;
        $endDate = $request->to ? Carbon::parse($request->to) : null;

        $stats = $this->statisticsService->getStatistics(
            $campaign,
            $period,
            $startDate,
            $endDate
        );

        return response()->json($stats);
    }
}
