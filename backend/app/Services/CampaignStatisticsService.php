<?php

namespace App\Services;

use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignStatisticsService
{
    /**
     * Get comprehensive statistics for a campaign.
     *
     * @param Campaign $campaign
     * @param string $period 'today'|'week'|'month'|'custom'
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getStatistics(
        Campaign $campaign,
        string $period = 'today',
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        [$start, $end] = $this->resolveDateRange($period, $startDate, $endDate);

        $dailySpent = $this->getDailySpent($campaign);
        $monthlySpent = $this->getMonthlySpent($campaign);
        $dailyLimit = $campaign->current_daily_budget * 2;
        $monthlyLimit = $this->getMonthlyLimit($campaign);

        return [
            'daily_spent' => $dailySpent,
            'daily_budget' => (float) $campaign->current_daily_budget,
            'daily_limit' => $dailyLimit,
            'daily_remaining' => max(0, $dailyLimit - $dailySpent),
            'monthly_spent' => $monthlySpent,
            'monthly_limit' => $monthlyLimit,
            'monthly_remaining' => max(0, $monthlyLimit - $monthlySpent),
            'period_spent' => $this->getPeriodSpent($campaign, $start, $end),
            'period_costs_count' => $this->getPeriodCostsCount($campaign, $start, $end),
            'average_cost' => $this->getAverageCost($campaign, $start, $end),
            'cost_trend' => $this->getCostTrend($campaign, $start, $end),
        ];
    }

    /**
     * Get daily summary data for a campaign.
     *
     * @param Campaign $campaign
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getDailySummary(Campaign $campaign, Carbon $startDate, Carbon $endDate): array
    {
        $dailySummary = DB::table('costs')
            ->select(
                DB::raw('DATE(generated_at) as date'),
                DB::raw('COUNT(*) as cost_count'),
                DB::raw('SUM(amount) as total_cost'),
                DB::raw('MAX(daily_limit_at_generation) as daily_limit'),
                DB::raw('MAX(budget_at_generation) as max_budget')
            )
            ->where('campaign_id', $campaign->id)
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(generated_at)'))
            ->orderBy('date')
            ->get();

        return $dailySummary->map(function ($item) {
            $utilization = $item->daily_limit > 0
                ? ($item->total_cost / $item->daily_limit) * 100
                : 0;

            return [
                'date' => $item->date,
                'max_budget' => (float) $item->max_budget,
                'total_cost' => (float) $item->total_cost,
                'cost_count' => (int) $item->cost_count,
                'daily_limit' => (float) $item->daily_limit,
                'utilization' => round($utilization, 2),
            ];
        })->toArray();
    }

    /**
     * Get total spent today.
     *
     * @param Campaign $campaign
     * @return float
     */
    protected function getDailySpent(Campaign $campaign): float
    {
        return (float) $campaign->costs()
            ->whereDate('generated_at', Carbon::today())
            ->sum('amount');
    }

    /**
     * Get total spent this month.
     *
     * @param Campaign $campaign
     * @return float
     */
    protected function getMonthlySpent(Campaign $campaign): float
    {
        $monthStart = Carbon::now()->startOfMonth();
        $now = Carbon::now();

        return (float) $campaign->costs()
            ->whereBetween('generated_at', [$monthStart, $now])
            ->sum('amount');
    }

    /**
     * Get monthly spending limit based on max daily budgets.
     *
     * @param Campaign $campaign
     * @return float
     */
    protected function getMonthlyLimit(Campaign $campaign): float
    {
        $monthStart = Carbon::now()->startOfMonth();
        $now = Carbon::now();

        $budgetHistory = $campaign->budgetHistories()
            ->where('changed_at', '<=', $now)
            ->orderBy('changed_at')
            ->get();

        if ($budgetHistory->isEmpty()) {
            return 0;
        }

        $total = 0;
        $currentDate = $monthStart->copy();

        while ($currentDate->lte($now)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            // Find max budget for this day
            $dayBudgets = $budgetHistory->filter(function ($history) use ($dayEnd) {
                return Carbon::parse($history->changed_at)->lte($dayEnd);
            })->pluck('new_budget');

            $maxDayBudget = $dayBudgets->isEmpty() ? 0 : $dayBudgets->max();
            $total += $maxDayBudget;

            $currentDate->addDay();
        }

        return $total;
    }

    /**
     * Get total spent in a period.
     *
     * @param Campaign $campaign
     * @param Carbon $start
     * @param Carbon $end
     * @return float
     */
    protected function getPeriodSpent(Campaign $campaign, Carbon $start, Carbon $end): float
    {
        return (float) $campaign->costs()
            ->whereBetween('generated_at', [$start, $end])
            ->sum('amount');
    }

    /**
     * Get number of costs in a period.
     *
     * @param Campaign $campaign
     * @param Carbon $start
     * @param Carbon $end
     * @return int
     */
    protected function getPeriodCostsCount(Campaign $campaign, Carbon $start, Carbon $end): int
    {
        return $campaign->costs()
            ->whereBetween('generated_at', [$start, $end])
            ->count();
    }

    /**
     * Get average cost in a period.
     *
     * @param Campaign $campaign
     * @param Carbon $start
     * @param Carbon $end
     * @return float
     */
    protected function getAverageCost(Campaign $campaign, Carbon $start, Carbon $end): float
    {
        return (float) $campaign->costs()
            ->whereBetween('generated_at', [$start, $end])
            ->avg('amount') ?? 0;
    }

    /**
     * Get cost trend (daily aggregation).
     *
     * @param Campaign $campaign
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    protected function getCostTrend(Campaign $campaign, Carbon $start, Carbon $end): array
    {
        $trend = DB::table('costs')
            ->select(
                DB::raw('DATE(generated_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->where('campaign_id', $campaign->id)
            ->whereBetween('generated_at', [$start, $end])
            ->groupBy(DB::raw('DATE(generated_at)'))
            ->orderBy('date')
            ->get();

        return $trend->map(function ($item) {
            return [
                'date' => $item->date,
                'total' => (float) $item->total,
            ];
        })->toArray();
    }

    /**
     * Resolve date range based on period.
     *
     * @param string $period
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    protected function resolveDateRange(string $period, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return [Carbon::today(), $now];

            case 'week':
                return [Carbon::now()->startOfWeek(), $now];

            case 'month':
                return [Carbon::now()->startOfMonth(), $now];

            case 'custom':
                return [
                    $startDate ?? Carbon::now()->subMonth(),
                    $endDate ?? $now
                ];

            default:
                return [Carbon::today(), $now];
        }
    }
}
