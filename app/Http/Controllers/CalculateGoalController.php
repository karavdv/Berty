<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\CalculateGoalService;

class CalculateGoalController extends Controller
{
    protected $calculatorService;

    public function __construct(CalculateGoalService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    public function calculateInvestment(Request $request)
    {
        // Validation form input
        $validated = $request->validate([
            'capital' => 'required|numeric|min:0.01',
            'days' => 'required|integer|min:1|max:7',
            'margin' => 'required|numeric|min:0.01',
        ]);

        //CalculateGoalsService calculates capital growth
        $result = $this->calculatorService->calculate(
            $validated['capital'],
            $validated['days'],
            $validated['margin']
        );

        // Sending results to front-end
        return response()->json($result);
    }
}
