<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Family;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    /**
     * Where uploaded receipts are stored on the "public" disk.
     */
    private const ATTACHMENT_DIR = 'expense-attachments';

    /**
     * Build the filtered, paginated expense list for a family.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForFamily(Family $family, array $filters = []): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($family, $filters)
            ->with(['category', 'user'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * The total amount across every expense matching the filters (not just the
     * current page), so the UI can show a meaningful running total.
     *
     * @param  array<string, mixed>  $filters
     */
    public function totalForFamily(Family $family, array $filters = []): string
    {
        return (string) $this->buildFilteredQuery($family, $filters)->sum('amount');
    }

    /**
     * Shared query used by both the paginated list and the total, so the
     * filtering logic lives in exactly one place.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Expense>
     */
    private function buildFilteredQuery(Family $family, array $filters): Builder
    {
        return Expense::query()
            ->forFamily($family->id)
            ->when(
                ! empty($filters['category_id']),
                fn (Builder $query) => $query->where('expense_category_id', $filters['category_id'])
            )
            ->when(
                ! empty($filters['user_id']),
                fn (Builder $query) => $query->where('user_id', $filters['user_id'])
            )
            ->when(
                ! empty($filters['search']),
                fn (Builder $query) => $query->where('description', 'like', '%'.$filters['search'].'%')
            )
            ->tap(fn (Builder $query) => $this->applyDateFilter($query, $filters));
    }

    /**
     * Translate the "period" filter into a date-range constraint.
     *
     * @param  Builder<Expense>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilter(Builder $query, array $filters): void
    {
        $period = $filters['period'] ?? null;

        match ($period) {
            'today' => $query->whereDate('expense_date', Carbon::today()),
            'week' => $query->whereBetween('expense_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]),
            'month' => $query->whereBetween('expense_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'custom' => $this->applyCustomRange($query, $filters),
            default => null,
        };
    }

    /**
     * @param  Builder<Expense>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyCustomRange(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }
    }

    /**
     * Create a new expense for the family, storing the receipt if provided.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Family $family, User $user, array $data, ?UploadedFile $attachment = null): Expense
    {
        if ($attachment) {
            $data['attachment_path'] = $attachment->store(self::ATTACHMENT_DIR, 'public');
        }

        return $family->expenses()->create([
            ...$data,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Update an expense, replacing the receipt if a new one is uploaded.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Expense $expense, array $data, ?UploadedFile $attachment = null): Expense
    {
        if ($attachment) {
            $this->deleteAttachment($expense);
            $data['attachment_path'] = $attachment->store(self::ATTACHMENT_DIR, 'public');
        }

        $expense->update($data);

        return $expense;
    }

    /**
     * Soft-delete the expense. The receipt file is intentionally kept so the
     * row can be restored later without a broken attachment link.
     */
    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    /**
     * Remove the currently stored receipt file, if any.
     */
    private function deleteAttachment(Expense $expense): void
    {
        if ($expense->attachment_path) {
            Storage::disk('public')->delete($expense->attachment_path);
        }
    }
}
