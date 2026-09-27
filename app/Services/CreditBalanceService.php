<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class CreditBalanceService
{
    /**
     * Calculate the running credit balance for a faculty member.
     */
    public function getBalance(Faculty $faculty): float
    {
        return $faculty->transactions()
            ->where('status', Transaction::STATUS_ACTIVE)
            ->sum('total_amount');
    }

    /**
     * Get the remaining credit limit for a faculty member.
     */
    public function getRemainingCredit(Faculty $faculty): float
    {
        $balance = $this->getBalance($faculty);
        return max(0, $faculty->credit_limit - $balance);
    }

    /**
     * Check if a transaction amount would exceed the credit limit.
     */
    public function wouldExceedLimit(Faculty $faculty, float $amount): bool
    {
        $remaining = $this->getRemainingCredit($faculty);
        return $amount > $remaining;
    }

    /**
     * Get balance details for display.
     */
    public function getBalanceDetails(Faculty $faculty): array
    {
        $balance = $this->getBalance($faculty);
        $remaining = $this->getRemainingCredit($faculty);

        return [
            'credit_limit' => $faculty->credit_limit,
            'current_balance' => $balance,
            'remaining_credit' => $remaining,
            'percentage_used' => $faculty->credit_limit > 0
                ? round(($balance / $faculty->credit_limit) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get monthly total for a faculty member within a date range.
     */
    public function getMonthlyTotal(Faculty $faculty, string $startDate, string $endDate): float
    {
        return $faculty->transactions()
            ->where('status', Transaction::STATUS_ACTIVE)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('total_amount');
    }

    /**
     * Get department monthly totals within a date range.
     */
    public function getDepartmentMonthlyTotals(string $startDate, string $endDate): Collection
    {
        return Faculty::where('is_active', true)
            ->with(['transactions' => function ($query) use ($startDate, $endDate) {
                $query->where('status', Transaction::STATUS_ACTIVE)
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            }])
            ->get()
            ->groupBy('department')
            ->map(function ($facultyGroup) {
                return $facultyGroup->sum(function ($faculty) {
                    return $faculty->transactions->sum('total_amount');
                });
            })
            ->sortDesc()
            ->map(fn($total) => round($total, 2));
    }
}