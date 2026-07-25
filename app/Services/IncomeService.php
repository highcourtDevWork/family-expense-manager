<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Income;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class IncomeService
{
    /**
     * Build the filtered, paginated income list for a family.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForFamily(Family $family, array $filters = []): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($family, $filters)
            ->with(['source', 'user'])
            ->orderByDesc('income_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Total income across every row matching the filters (not just the page).
     *
     * @param  array<string, mixed>  $filters
     */
    public function totalForFamily(Family $family, array $filters = []): string
    {
        return (string) $this->buildFilteredQuery($family, $filters)->sum('amount');
    }

    /**
     * Monthly income report: total per source for the given month, plus the
     * grand total. Satisfies the spec's "Monthly income report" requirement.
     *
     * @return array{month: Carbon, rows: Collection<int, object>, total: string}
     */
    public function monthlyReport(Family $family, ?string $month = null): array
    {
        $target = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $rows = Income::query()
            ->forFamily($family->id)
            ->selectRaw('income_source_id, SUM(amount) as total')
            ->with('source')
            ->whereBetween('income_date', [$target->copy()->startOfMonth(), $target->copy()->endOfMonth()])
            ->groupBy('income_source_id')
            ->orderByDesc('total')
            ->get();

        return [
            'month' => $target,
            'rows' => $rows,
            'total' => (string) $rows->sum('total'),
        ];
    }

    /**
     * Shared query used by both the list and the total, so the filtering logic
     * lives in exactly one place.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Income>
     */
    private function buildFilteredQuery(Family $family, array $filters): Builder
    {
        return Income::query()
            ->forFamily($family->id)
            ->when(
                ! empty($filters['income_source_id']),
                fn (Builder $query) => $query->where('income_source_id', $filters['income_source_id'])
            )
            ->when(
                ! empty($filters['user_id']),
                fn (Builder $query) => $query->where('user_id', $filters['user_id'])
            )
            ->when(
                ! empty($filters['search']),
                fn (Builder $query) => $query->where('notes', 'like', '%'.$filters['search'].'%')
            )
            ->tap(fn (Builder $query) => $this->applyDateFilter($query, $filters));
    }

    /**
     * Translate the "period" filter into a date-range constraint.
     *
     * @param  Builder<Income>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilter(Builder $query, array $filters): void
    {
        $period = $filters['period'] ?? null;

        match ($period) {
            'today' => $query->whereDate('income_date', Carbon::today()),
            'week' => $query->whereBetween('income_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'month' => $query->whereBetween('income_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'custom' => $this->applyCustomRange($query, $filters),
            default => null,
        };
    }

    /**
     * @param  Builder<Income>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyCustomRange(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('income_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('income_date', '<=', $filters['date_to']);
        }
    }

    /**
     * Create a new income entry for the family.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Family $family, User $user, array $data): Income
    {
        return $family->incomes()->create([
            ...$data,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Income $income, array $data): Income
    {
        $income->update($data);

        return $income;
    }

    public function delete(Income $income): void
    {
        $income->delete();
    }
}
