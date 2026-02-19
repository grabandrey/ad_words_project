<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Cost;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CostGenerationService
{
    /**
     * Generate costs for a campaign over a date range.
     *
     * @param Campaign $campaign
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int Number of costs generated
     */
    public function generateCostsForPeriod(Campaign $campaign, Carbon $startDate, Carbon $endDate): int
    {
        $totalGenerated = 0;

        $budgetHistory = $this->getBudgetHistoryForPeriod($campaign, $startDate, $endDate);

        // Iterate through each day in the period
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $day) {
            // Clamp the time window for this day to the overall period bounds
            $dayFrom = $day->isSameDay($startDate) ? $startDate->copy() : $day->copy()->startOfDay();
            $dayTo   = $day->isSameDay($endDate)   ? $endDate->copy()   : $day->copy()->endOfDay();

            // Use the budget that was active at the start of this day's window
            $dayBudget = $this->getBudgetAtTime($budgetHistory, $dayFrom);
            $generated = $this->generateCostsForDay($campaign, $day, $budgetHistory, $dayBudget, $dayFrom, $dayTo);
            $totalGenerated += $generated;
        }

        return $totalGenerated;
    }

    /**
     * Generate costs for a single day.
     *
     * @param Campaign $campaign
     * @param Carbon $day
     * @param Collection $budgetHistory
     * @return int Number of costs generated for the day
     */
    protected function generateCostsForDay(Campaign $campaign, Carbon $day, Collection $budgetHistory, ?float $dayBudget, Carbon $from, Carbon $to): int
    {
        $generated = 0;

        // If no budget history exists for this day, skip (campaign not yet started)
        if ($dayBudget === null) {
            return 0;
        }

        // Budget explicitly set to 0: record a zero-cost entry and stop
        if ($dayBudget == 0) {
            Cost::create([
                'campaign_id'              => $campaign->id,
                'amount'                   => 0,
                'generated_at'             => $from->toDateTimeString(),
                'budget_at_generation'     => 0,
                'daily_limit_at_generation' => 0,
            ]);
            return 1;
        }

        // Rule 1: daily cumulative cost cannot exceed 2x the budget active on this day
        $dailyLimit = $dayBudget * 2;

        // Include any costs already recorded for this day before this run
        $dailyCumulativeCost = $this->getDailyCumulativeCost($campaign, $day);

        if ($dailyCumulativeCost >= $dailyLimit) {
            return 0; // Daily limit already reached
        }

        // Randomly determine cost generation count (1-10 per day)
        $costCount = rand(1, 10);

        // Generate random timestamps within the given time window
        $timestamps = $this->generateRandomTimestamps($from, $to, $costCount);

        // Sort timestamps chronologically
        sort($timestamps);

        foreach ($timestamps as $timestamp) {
            // Rule 1: remaining daily capacity
            $remainingDailyCapacity = $dailyLimit - $dailyCumulativeCost;

            if ($remainingDailyCapacity <= 0) {
                break; // Daily limit exhausted, no point continuing
            }

            // Rule 2: check monthly constraint (sum of max daily budgets)
            $ts = Carbon::parse($timestamp);
            $monthStart = $ts->copy()->startOfMonth();
            $monthCumulativeCost = $this->getMonthlyCumulativeCost($campaign, $monthStart, $ts);
            $monthMaxBudget = $this->calculateMonthMaxBudget($budgetHistory, $monthStart, $ts);
            $remainingMonthlyCapacity = $monthMaxBudget - $monthCumulativeCost;

            if ($remainingMonthlyCapacity <= 0) {
                break; // Monthly limit reached, no point continuing
            }

            // Generate cost respecting both constraints
            $maxPossibleCost = min($remainingDailyCapacity, $remainingMonthlyCapacity);

            if ($maxPossibleCost < 0.01) {
                break;
            }

            // Generate random cost (10% to 100% of available capacity)
            $minCost = $maxPossibleCost * 0.1;
            $costAmount = round($minCost + (mt_rand() / mt_getrandmax()) * ($maxPossibleCost - $minCost), 2);

            if ($costAmount <= 0) {
                continue;
            }

            // Create cost record
            Cost::create([
                'campaign_id' => $campaign->id,
                'amount' => $costAmount,
                'generated_at' => $timestamp,
                'budget_at_generation' => $dayBudget,
                'daily_limit_at_generation' => $dailyLimit,
            ]);

            $dailyCumulativeCost += $costAmount;
            $generated++;
        }

        return $generated;
    }

    /**
     * Get budget value at a specific timestamp.
     *
     * @param Collection $budgetHistory
     * @param Carbon $timestamp
     * @return float
     */
    protected function getBudgetAtTime(Collection $budgetHistory, Carbon $timestamp): ?float
    {
        // Find the most recent budget change before or at timestamp
        $relevantHistory = $budgetHistory->filter(function ($history) use ($timestamp) {
            return Carbon::parse($history->changed_at)->lte($timestamp);
        })->sortByDesc('changed_at');

        // No history means the campaign had not started yet
        if ($relevantHistory->isEmpty()) {
            return null;
        }

        return (float) $relevantHistory->first()->new_budget;
    }

    /**
     * Calculate max monthly cost: sum of max daily budgets for each day.
     *
     * @param Collection $budgetHistory
     * @param Carbon $monthStart
     * @param Carbon $currentDate
     * @return float
     */
    protected function calculateMonthMaxBudget(Collection $budgetHistory, Carbon $monthStart, Carbon $currentDate): float
    {
        $total = 0;

        // For each day in the month up to current date
        $period = CarbonPeriod::create($monthStart, $currentDate->copy()->startOfDay());

        foreach ($period as $day) {
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();

            // Budget in effect at the start of this day (last change before the day began)
            $budgetAtDayStart = $this->getBudgetAtTime($budgetHistory, $dayStart);

            // All budget changes that occurred within this day
            $changesOnDay = $budgetHistory->filter(function ($history) use ($dayStart, $dayEnd) {
                $changedAt = Carbon::parse($history->changed_at);
                return $changedAt->gte($dayStart) && $changedAt->lte($dayEnd);
            })->pluck('new_budget');

            // Max budget for this day: highest value active or set during the day
            $maxDayBudget = $changesOnDay->prepend($budgetAtDayStart)->max();

            $total += $maxDayBudget ?? 0;
        }

        return $total;
    }

    /**
     * Get cumulative cost for a specific day.
     *
     * @param Campaign $campaign
     * @param Carbon $day
     * @return float
     */
    protected function getDailyCumulativeCost(Campaign $campaign, Carbon $day): float
    {
        return (float) $campaign->costs()
            ->whereDate('generated_at', $day->toDateString())
            ->sum('amount');
    }

    /**
     * Get cumulative cost for a month up to a specific date.
     *
     * @param Campaign $campaign
     * @param Carbon $monthStart
     * @param Carbon $upToDate
     * @return float
     */
    protected function getMonthlyCumulativeCost(Campaign $campaign, Carbon $monthStart, Carbon $upToDate): float
    {
        return (float) $campaign->costs()
            ->whereBetween('generated_at', [$monthStart, $upToDate])
            ->sum('amount');
    }

    /**
     * Generate random timestamps throughout a day.
     *
     * @param Carbon $day
     * @param int $count
     * @return array
     */
    protected function generateRandomTimestamps(Carbon $from, Carbon $to, int $count): array
    {
        $timestamps = [];
        $totalSeconds = max(0, (int) $to->diffInSeconds($from));

        for ($i = 0; $i < $count; $i++) {
            $randomSeconds = rand(0, $totalSeconds);
            $timestamps[] = $from->copy()->addSeconds($randomSeconds)->toDateTimeString();
        }

        return array_unique($timestamps);
    }

    /**
     * Get budget history for a campaign within a date range.
     *
     * @param Campaign $campaign
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getBudgetHistoryForPeriod(Campaign $campaign, Carbon $startDate, Carbon $endDate): Collection
    {
        // Get all budget histories up to end date
        // We need history before start date to know the budget at start
        return $campaign->budgetHistories()
            ->where('changed_at', '<=', $endDate)
            ->orderBy('changed_at')
            ->get();
    }

    /**
     * Clear all costs for a campaign within a date range.
     *
     * @param Campaign $campaign
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int Number of costs deleted
     */
    public function clearCostsForPeriod(Campaign $campaign, Carbon $startDate, Carbon $endDate): int
    {
        return $campaign->costs()
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->delete();
    }
}
