<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CostGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateCampaignCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:generate-costs
                            {campaign? : The campaign ID}
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--all : Generate for all campaigns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate random costs for AdWords campaigns following budget rules';

    /**
     * Execute the console command.
     */
    public function handle(CostGenerationService $service): int
    {
        $campaignId = $this->argument('campaign');
        $generateAll = $this->option('all');

        // Determine date range
        $startDate = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : Carbon::now()->subMonths(3);

        $endDate = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::now();

        $this->info("Generating costs from {$startDate->toDateString()} to {$endDate->toDateString()}");

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
