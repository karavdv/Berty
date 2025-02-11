import React from "react";
import { useSignals } from "@preact/signals-react/runtime";
import { selectedPair, tradeSize, drop, profit, startBuy, maxBuys, accumulate, topEdge, stopLoss, showOverviewPage} from "../../components/Signals.js";
import '../../../css/TradeBotOverview.css';

export const TradeBotOverview = () => {
    useSignals();

    if (!showOverviewPage.value) {
        return null;
    }

    return (
        <section className="tradeBot-overview">
            <p>Check your settings and then dubbel check them!</p>
            <p> Clicking the confirm button will start the trading.</p>

            <ul>
                <li>Currency pair: {selectedPair.value}</li>
                <li>Trade Amount: €{tradeSize.value}</li>
                <li>Buy at Drop: {drop.value}%</li>
                <li>Sell at Profit: {profit.value}%</li>
                <li>Starting Price: €{startBuy.value}</li>
                <li>Max Budget: €{maxBuys.value}</li>
                <li>Accumulate Profit: {accumulate.value ? "Yes" : "No"}</li>
                <li>Stay Below Top: {topEdge.value}%</li>
                <li>Stop Loss: {stopLoss.value || "None"}</li>
            </ul>

            <button className="confirm-button" onClick={() => {
                showOverviewPage.value = false;
                console.log("Trading bot started with the above settings!");
            }}>
            Confirm & Start Bot
            </button>
        </section>
    );
};
