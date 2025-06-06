import React from "react";
import { useSignals } from "@preact/signals-react/runtime";
import { selectedPair, tradeSize, drop, profit, startBuy, budget, accumulate, topEdge, bottom, peak, showOverviewPage} from "../../components/Signals.js";
import '../../../css/TradeBotOverview.css';
import { StartBotButton } from "./StartBotButton.jsx";

export const TradeBotOverview = () => {
    useSignals();

    if (!showOverviewPage.value) {
        return null;
    }

    return (
        <section className="tradeBot-overview">
            <p>Check your settings and then double check them!</p>
            <p> Clicking the confirm button will start the trading.</p>

            <ul>
                <li>Currency pair: {selectedPair.value}</li>
                <li>Trade Amount: €{tradeSize.value}</li>
                <li>Buy at Drop: {drop.value}%</li>
                <li>Sell at Profit: {profit.value}%</li>
                <li>Starting Price: €{startBuy.value}</li>
                <li>Max Budget: €{budget.value}</li>
                <li>Accumulate Profit: {accumulate.value ? "Yes" : "No"}</li>
                <li>Stay Below Top: {topEdge.value}%</li>
                <li>Bottom limit: {bottom.value || "None"}</li>
                <li>Peak limit: {peak.value || "None"}</li>
            </ul>

            < StartBotButton/>
        </section>
    );
};
