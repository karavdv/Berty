import React from "react";
import { useSignals } from "@preact/signals-react/runtime";
import { selectedPair, tradeSize, drop, profit, startBuy, budget, accumulate, topEdge, stopLoss, showOverviewPage } from "../../components/Signals.js";
import '../../../css/TradeBotOverview.css';

export const StartBotButton = () => {
    useSignals();

    const sendBotData = async () => {
        try {
            const response = await fetch("http://127.0.0.1:8000/api/trading-bot/start", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', },
                body: JSON.stringify({
                    pair: selectedPair.value,
                    tradeSize: parseFloat(tradeSize.value),
                    drop: parseFloat(drop.value),
                    profit: parseFloat(profit.value),
                    startBuy: parseFloat(startBuy.value),
                    budget: parseFloat(budget.value),
                    accumulate: accumulate.value,
                    topEdge: topEdge.value ? parseFloat(topEdge.value) : null,
                    stopLoss: stopLoss.value ? parseFloat(stopLoss.value) : null,
                }),
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }

            const result = await response.json();
            console.log("Trading bot succesvol gestart:", result);
            showOverviewPage.value = false;  // Optioneel: terug naar overzicht
        } catch (error) {
            console.error("Fout bij starten trading bot:", error);
        }
    };

    return (

        <button className="confirm-button" onClick={sendBotData}>
            Confirm & Start Bot
        </button>

    );
};