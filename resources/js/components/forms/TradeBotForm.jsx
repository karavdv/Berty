import React from 'react';
import { useSignals } from "@preact/signals-react/runtime";
import { selectedCurrency, selectedPair, availablePairs, tradeSize, drop, profit, startBuy, budget, accumulate, topEdge, stopLoss, showOverviewPage, errors } from "../../components/Signals.js";
import { CurrencySelector } from "../forms/utils/CurrencySelector";
import { CurrencyPairSelector } from "../forms/utils/CurrencyPairSelector.jsx";
import { handleNumberInput } from "../forms/utils/handleNumberInput.js";
import '../../../css/Forms.css';

export const TradeBotForm = () => {
    useSignals();

    const validateForm = (e) => {
        e.preventDefault();

        let formErrors = {};

        if (!selectedCurrency.value) {
            formErrors.selectedCurrency = 'Currency must be chosen.';
        }

        if (selectedCurrency.value) {

            if (!selectedPair.value) {
                formErrors.selectedPair = "Please select a currency pair.";
            }

            if (availablePairs.value && availablePairs.value.length > 0 && !availablePairs.value.includes(selectedPair.value)) {
                formErrors.selectedPair = "Invalid currency pair selected.";
            }
        }

        if (!tradeSize.value || tradeSize.value <= 0) {
            formErrors.tradeSize = "Enter a valid trade amount.";
        }
        if (!drop.value || drop.value <= 0) {
            formErrors.drop = "Enter a valid drop percentage.";
        }
        if (!profit.value || profit.value <= 0) {
            formErrors.profit = "Enter a valid profit percentage.";
        }
        if (!startBuy.value || startBuy.value <= 0) {
            formErrors.startBuy = "Enter a valid starting price.";
        }
        if (!budget.value || budget.value <= 0) {
            formErrors.budget = "Enter a valid budget amount.";
        }
        if (topEdge.value && topEdge.value < 0) {
            formErrors.topEdge = "Top edge percentage cannot be negative.";
        }
        if (stopLoss.value && stopLoss.value < 0) {
            formErrors.stopLoss = "Stop loss percentage cannot be negative.";
        }

        if (Object.keys(formErrors).length > 0) {
            errors.value = formErrors; // Update errors if they occur
            return; // Stop function if errors occur
        }

        errors.value = {}; // Reset previous errors
        showOverviewPage.value = true;
    };



    return (
        <section className="analysis form-section">
            <div className='analysis form-wrapper'>
                <form className="analysis form" onSubmit={validateForm}>
                    {/* Currency Input */}
                    <CurrencySelector />
                    {/* Currencypair Input */}
                    <CurrencyPairSelector />

                    {/* Trade size Input */}
                    <div className="analysis form-group">
                        <label htmlFor="tradeSize">Trade amount</label>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="tradeSize"
                            name="tradeSize"
                            placeholder="How much do you want to invest per trade?"
                            value={tradeSize.value}
                            onChange={handleNumberInput((val) => (tradeSize.value = val))}
                        />
                        {errors.value?.tradeSize && <p className="error-message">{errors.value.tradeSize}</p>}
                    </div>



                    {/* drop % Input */}
                    <div className="analysis form-group">
                        <label htmlFor="drop">At what drop do you want to buy? in %</label>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="drop"
                            name="drop"
                            placeholder="When the chart drops by *% I want to buy"
                            value={drop.value}
                            onChange={handleNumberInput((val) => (drop.value = val))}
                        />
                        {errors.value?.drop && <p className="error-message">{errors.value.drop}</p>}
                    </div>

                    {/* Profit Input */}
                    <div className="analysis form-group">
                        <label htmlFor="profit">How much profit do you want to make on a trade? in %</label>
                        <span className='small'>Keep it real. If you set this to 20% will make the chances of making the trade any time soon very small. On the other hand, don't forget about transaction costs. These are usually between 0.25% and 0.4% So if you set it at 1%, now that the actual profit will be 0.75% to 0.6%. </span>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="profit"
                            name="profit"
                            placeholder="The % you want the value to rise before you sell."
                            value={profit.value}
                            onChange={handleNumberInput((val) => (profit.value = val))}
                        />
                        {errors.value?.profit && <p className="error-message">{errors.value.profit}</p>}
                    </div>

                    {/* start buy Input */}
                    <div className="analysis form-group">
                        <label htmlFor="startBuy">At what price do you want to make the first trade and thus start the bot?</label>
                        <span className='small'>Study the chart. Ideally you start the bot half way down or at the bottom of a downward movement. </span>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="startBuy"
                            name="startBuy"
                            placeholder="Enter the price at which you want the bot to start trading."
                            value={startBuy.value}
                            onChange={handleNumberInput((val) => (startBuy.value = val))}
                        />
                        {errors.value?.startBuy && <p className="error-message">{errors.value.startBuy}</p>}
                    </div>

                    {/* budget Input */}
                    <div className="analysis form-group">
                        <label htmlFor="budget">What is the budget for the bot?</label>
                        <span className='small'>This is the maximum amount the bot can have in open trades. Make this a meervoud of your trade amount. By example; trade is €10, bot budget is €100. The bot can make 10 buys without making a sell. After that it will wait for a sell before making another buy. </span>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="budget"
                            name="budget"
                            placeholder="The maximum amount you want the bot to be able to buy without making sales."
                            value={budget.value}
                            onChange={handleNumberInput((val) => (budget.value = val))}
                        />
                        {errors.value?.budget && <p className="error-message">{errors.value.budget}</p>}
                    </div>

                    {/* accumulative budget Input */}
                    <div className="analysis form-group">
                        <label htmlFor="accumulate">Do you want the profit to be added to the budget?</label>
                        <input
                            type="checkbox"
                            id="accumulate"
                            name="accumulate"
                            checked={accumulate.value}
                            onChange={(e) => (accumulate.value = e.target.checked)}
                        />
                        {errors.value?.accumulate && <p className="error-message">{errors.value.accumulate}</p>}
                    </div>

                    {/* Upper limit Input */}
                    <div className="analysis form-group">
                        <label htmlFor="topEdge">How far do you want to stay below the top of the chart? in %</label>
                        <span className='small'>You reduce your risk of not selling by staying below the top of the chart. By example; you want to sell at 5% profit and you buy at every 3% drop. If you don't stay at least 5% below the top of the chart when you buy, the chance is you will wait a long time before selling. </span>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="topEdge"
                            name="topEdge"
                            placeholder="The distance you want to keep from the top of the chart"
                            value={topEdge.value}
                            onChange={handleNumberInput((val) => (topEdge.value = val))}
                        />
                        {errors.value?.topEdge && <p className="error-message">{errors.value.topEdge}</p>}
                    </div>

                    {/* Stop Loss Input */}
                    <div className="analysis form-group">
                        <label htmlFor="stopLoss">Stop Loss - in % (optional)</label>
                        <span className='small'>realise how volatile the market can be. Often the best strategy is patience. But you can also work with a limit. If the price drops by this % from your buy price, the bot will automatically sell to minimize losses.</span>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="stopLoss"
                            name="stopLoss"
                            placeholder="Enter a percentage to auto-sell to minimise losses"
                            value={stopLoss.value}
                            onChange={handleNumberInput((val) => (stopLoss.value = val))}
                        />
                        {errors.value?.stopLoss && <p className="error-message">{errors.value.stopLoss}</p>}
                    </div>


                    {/* Submit Button */}
                    <button
                        className="tradeBot form-button"
                        type="submit">
                        Start Trading Bot
                    </button>
                    {errors.value?.global && <p className="error-message">{errors.value.global}</p>}
                </form>

            </div>
        </section>
    );
};
