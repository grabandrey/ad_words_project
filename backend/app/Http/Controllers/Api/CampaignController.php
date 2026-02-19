<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Display a listing of campaigns.
     */
    public function index(): AnonymousResourceCollection
    {
        $campaigns = Campaign::with(['costs' => function ($query) {
            $query->whereDate('generated_at', today());
        }])
            ->where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->paginate(20);

        return CampaignResource::collection($campaigns);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = Campaign::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'current_daily_budget' => $request->current_daily_budget ?? 0,
            'is_active' => true,
        ]);

        return response()->json([
            'data' => new CampaignResource($campaign),
            'message' => 'Campaign created successfully',
        ], 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        $campaign->load([
            'costs' => function ($query) {
                $query->whereDate('generated_at', today());
            },
            'budgetHistories' => function ($query) {
                $query->orderBy('changed_at', 'desc')->limit(5);
            }
        ]);

        return response()->json([
            'data' => new CampaignResource($campaign),
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        if ($request->has('name')) {
            $campaign->name = $request->name;
        }

        if ($request->has('current_daily_budget')) {
            $campaign->current_daily_budget = $request->current_daily_budget;
        }

        $campaign->save();

        return response()->json([
            'data' => new CampaignResource($campaign->fresh()),
            'message' => 'Campaign updated successfully',
        ]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Campaign $campaign): JsonResponse
    {
        $campaign->delete();

        return response()->json([
            'message' => 'Campaign deleted successfully',
        ]);
    }

    /**
     * Pause the specified campaign.
     */
    public function pause(Campaign $campaign): JsonResponse
    {
        $campaign = $this->budgetService->pauseCampaign($campaign);

        return response()->json([
            'data' => new CampaignResource($campaign),
            'message' => 'Campaign paused successfully',
        ]);
    }

    /**
     * Resume the specified campaign.
     */
    public function resume(Campaign $campaign): JsonResponse
    {
        $budget = request('daily_budget');

        $campaign = $this->budgetService->resumeCampaign($campaign, $budget);

        return response()->json([
            'data' => new CampaignResource($campaign),
            'message' => 'Campaign resumed successfully',
        ]);
    }
}
