<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\TradingBotService;

class TradingBotController extends Controller
{
    protected TradingBotService $tradingBotService;

    public function __construct(TradingBotService $tradingBotService)
    {
        $this->tradingBotService = $tradingBotService;
    }

    public function startBot(Request $request)
    {
        try {
            $validated = $request->validate([
                'pair'       => 'required|string',
                'tradeSize'  => 'required|numeric|min:0.01',
                'drop'       => 'required|numeric|min:0.1',
                'profit'     => 'required|numeric|min:0.1',
                'startBuy'   => 'required|numeric|min:0.00000001',
                'budget'     => 'required|numeric|min:0.01',
                'accumulate' => 'sometimes|boolean',
                'topEdge'    => 'nullable|numeric|min:0.1',
                'stopLoss'   => 'nullable|numeric|min:0'
            ]);

            $bot = $this->tradingBotService->createBot($validated);
            $this->tradingBotService->subscribeBot($bot->pair, $bot->id);

            Log::info("Trading bot created with ID: " . $bot->id);

            return response()->json(['message' => 'Trading bot started', 'bot' => $bot], 201);
        } catch (\Exception $e) {
            Log::error("Error creating trading bot: " . $e->getMessage());
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        return response()->json($this->tradingBotService->dashboard());
    }

    public function toggle(Request $request, $botId)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        return response()->json($this->tradingBotService->toggle($validated['status'], $botId));
    }

    public function stopBot($botId)
    {
        return response()->json($this->tradingBotService->stopBot($botId));
    }

    public function restartBot($botId)
    {
        return response()->json($this->tradingBotService->restartBot($botId));
    }

    public function deleteBot($botId)
    {
        $this->tradingBotService->deleteBot($botId);
        return response()->json(['message' => 'Bot deleted successfully']);
    }

    public function getActiveBots()
    {
        return response()->json($this->tradingBotService->getActiveBots(), 200);
    }
}
