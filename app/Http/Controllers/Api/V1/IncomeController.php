<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Income\StoreIncomeRequest;
use App\Http\Requests\Income\UpdateIncomeRequest;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use App\Services\IncomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncomeController extends Controller
{
    public function __construct(
        private readonly IncomeService $incomeService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Income::class);

        $filters = $request->only(['period', 'date_from', 'date_to', 'income_source_id', 'user_id', 'search']);

        $incomes = $this->incomeService->paginateForFamily(
            $request->user()->currentFamily(),
            $filters,
        );

        return IncomeResource::collection($incomes);
    }

    public function store(StoreIncomeRequest $request): JsonResponse
    {
        $income = $this->incomeService->create(
            $request->user()->currentFamily(),
            $request->user(),
            $request->validated(),
        );

        return IncomeResource::make($income->load(['source', 'user']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Income $income): IncomeResource
    {
        $this->authorize('view', $income);

        return IncomeResource::make($income->load(['source', 'user']));
    }

    public function update(UpdateIncomeRequest $request, Income $income): IncomeResource
    {
        $this->incomeService->update($income, $request->validated());

        return IncomeResource::make($income->load(['source', 'user']));
    }

    public function destroy(Income $income): JsonResponse
    {
        $this->authorize('delete', $income);

        $this->incomeService->delete($income);

        return response()->json(['message' => 'Income deleted.']);
    }
}
