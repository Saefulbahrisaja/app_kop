<?php

namespace App\Services;

use App\Models\ModelLoanRule;

class LoanLimitService
{
    public function getLimitByYears(int $years): array
    {
        $rule = ModelLoanRule::where('min_year', '<=', $years)
            ->where(function ($q) use ($years) {
                $q->whereNull('max_year')
                  ->orWhere('max_year', '>=', $years);
            })
            ->orderByDesc('min_year')
            ->first();

        if (!$rule) {
            // fallback aman
            return [
                'max_nominal' => 0,
                'max_tenor'   => 0,
            ];
        }

        return [
            'max_nominal' => (int) $rule->max_nominal,
            'max_tenor'   => (int) $rule->max_tenor,
        ];
    }
}
