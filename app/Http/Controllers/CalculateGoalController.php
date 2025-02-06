<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CalculateGoalController extends Controller
{
    public function calculateInvestment(Request $request)
    {
        // Validatie van de invoer
        $validated = $request->validate([
            'capital' => 'required|numeric|min:0.01',
            'days' => 'required|integer|min:1|max:7',
            'margin' => 'required|numeric|min:0.01',
        ]);

        // Gegevens uit React formulier ophalen
        $startCapital = $validated['capital'];
        $dailyRate = $validated['margin'] / 100; // Omzetten naar decimale waarde
        $daysPerWeek = $validated['days'];
        $weeksPerYear = 52;

        // Berekening van de groei
        $capital = $startCapital;
        $totalDays = $daysPerWeek * $weeksPerYear;
        for ($i = 0; $i < $totalDays; $i++) {
            $capital *= (1 + $dailyRate);
        }

        // Resultaat sturen naar React
        return response()->json([
            'initial_capital' => $startCapital,
            'final_capital' => number_format($capital, 2),
            'days_traded' => $totalDays,
            'profit_margin' => $dailyRate * 100,
        ]);
    }
}
