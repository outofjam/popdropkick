<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTitleReignsRequest;
use App\Http\Requests\StoreTitleReignRequest;
use App\Http\Requests\UpdateTitleReignRequest;
use App\Http\Resources\TitleReignResource;
use App\Models\TitleReign;
use App\Models\Wrestler;
use App\Services\ChampionshipService;
use App\Services\TitleReignService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class TitleReignController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected TitleReignService $service,
        protected ChampionshipService $championships,
    ) {}

    /**
     * List a championship's title reigns
     *
     * Returns the championship's reigns oldest-first, paginated. Filter with
     * `wrestler_id`, `win_type`, `current`, `vacated`, `won_on_from` and `won_on_to`.
     */
    public function index(IndexTitleReignsRequest $request, string $identifier): JsonResponse
    {
        $championship = $this->championships->findByIdOrSlug($identifier);

        if (! $championship) {
            return $this->error('Championship not found', 404);
        }

        $reigns = $this->service->paginateForChampionship($championship, $request->filters(), $request->perPage());

        return $this->ok(TitleReignResource::collection($reigns));
    }

    /**
     * Get a title reign
     *
     * Returns a single title reign with its championship, team and participants.
     */
    public function show(TitleReign $reign): JsonResponse
    {
        return $this->ok(new TitleReignResource($this->service->loadDetail($reign)));
    }

    /**
     * Record a title reign for a wrestler
     */
    public function store(StoreTitleReignRequest $request, Wrestler $wrestler): JsonResponse
    {
        $data = $request->validated();

        $reign = $this->service->storeForWrestler($data, $wrestler);

        return $this->success(new TitleReignResource($reign), 'Title Reign Created', null, 201);
    }

    /**
     * Update a title reign
     */
    public function update(UpdateTitleReignRequest $request, TitleReign $reign): JsonResponse
    {
        $reign = $this->service->updateReign($reign, $request->validated());

        return $this->success(new TitleReignResource($reign), 'Title Reign Updated');
    }

    /**
     * Delete a title reign
     */
    public function destroy(TitleReign $reign): JsonResponse
    {
        $this->service->deleteReign($reign);

        return $this->ok(null, 'Title Reign Deleted');
    }
}
