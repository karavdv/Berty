<?php

namespace App\Services;

class CalculateGoalService
{
    public function calculate($capital, $days, $margin)
    {
        // Calculating growth capital
        $dailyRate = $margin / 100; // transform to decimal
        $weeksPerYear = 52;
        $totalDays = $days * $weeksPerYear;

        $finalCapital = $capital;
        for ($i = 0; $i < $totalDays; $i++) {
            $finalCapital *= (1 + $dailyRate);
        }

        // Sending results to CalculateGoalController
        return [
            'initial_capital' => $capital,
            'final_capital' => number_format($finalCapital, 2),
            'days_traded' => $totalDays,
            'profit_margin' => $dailyRate * 100,
        ];
    }
}
