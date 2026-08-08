<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Expense::class);

        $filters = $request->only(['period', 'date_from', 'date_to', 'category_id', 'user_id', 'search']);

        $expenses = $this->expenseService->paginateForFamily(
            $request->user()->currentFamily(),
            $filters,
        );

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenseService->create(
            $request->user()->currentFamily(),
            $request->user(),
            $request->safe()->except('attachment'),
        );

        return ExpenseResource::make($expense->load(['category', 'user']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        $this->authorize('view', $expense);

        return ExpenseResource::make($expense->load(['category', 'user']));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        $this->expenseService->update($expense, $request->safe()->except('attachment'));

        return ExpenseResource::make($expense->load(['category', 'user']));
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        $this->expenseService->delete($expense);

        return response()->json(['message' => 'Expense deleted.']);
    }
}
