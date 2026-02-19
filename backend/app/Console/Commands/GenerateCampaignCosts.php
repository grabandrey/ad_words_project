<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CostGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateCampaignCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:generate-costs
                            {campaign? : The campaign ID}
                            {--from= : Start datetime (Y-m-d or "Y-m-d H:i:s")}
                            {--to= : End datetime (Y-m-d or "Y-m-d H:i:s")}
                            {--all : Generate for all campaigns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate random costs for AdWords campaigns following budget rules';

    protected const MAX_DAILY_RUNS = 10;

    /**
     * Execute the console command.
     */
    public function handle(CostGenerationService $service): int
    {
        $cacheKey = 'campaign:generate-costs:runs:' . now()->toDateString();
        $runsToday = (int) Cache::get($cacheKey, 0);

        if ($runsToday >= self::MAX_DAILY_RUNS) {
            $this->error("Daily limit reached: this command can only run " . self::MAX_DAILY_RUNS . " times per day ({$runsToday} runs used today).");
            return self::FAILURE;
        }

        Cache::put($cacheKey, $runsToday + 1, now()->endOfDay());

        $this->info("Run " . ($runsToday + 1) . " of " . self::MAX_DAILY_RUNS . " allowed today.");

        $campaignId = $this->argument('campaign');
        $generateAll = $this->option('all');

        // Determine datetime range
        $startDate = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : Carbon::now()->subMonths(3)->startOfDay();

        $endDate = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::now();

        $this->info("Generating costs from {$startDate->toDateTimeString()} to {$endDate->toDateTimeString()}");

        // Determine which campaigns to process
        if ($generateAll) {
            $campaigns = Campaign::all();
            $this->info("Processing all campaigns ({$campaigns->count()})");
        } elseif ($campaignId) {
            $campaigns = Campaign::where('id', $campaignId)->get();

            if ($campaigns->isEmpty()) {
                $this->error("Campaign with ID {$campaignId} not found");
                return self::FAILURE;
            }

            $this->info("Processing campaign ID: {$campaignId}");
        } else {
            $this->error('Please specify a campaign ID or use --all flag');
            return self::FAILURE;
        }

        $totalGenerated = 0;

        // Process each campaign
        foreach ($campaigns as $campaign) {
            $this->info("Generating costs for campaign: {$campaign->name} (ID: {$campaign->id})");

            $generated = $service->generateCostsForPeriod($campaign, $startDate, $endDate);

            $this->info("  Generated {$generated} costs");
            $totalGenerated += $generated;
        }

        $this->newLine();
        $this->info("✓ Total costs generated: {$totalGenerated}");

        return self::SUCCESS;
    }
}
