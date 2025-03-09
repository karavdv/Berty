import React, { useState, useEffect } from 'react';
import { useSignals } from "@preact/signals-react/runtime";
import { selectedCurrency, results, loading, errors } from "../../components/Signals.js";
import { CurrencySelector } from "../forms/utils/CurrencySelector";
import { handleNumberInput } from "../forms/utils/handleNumberInput.js";
import '../../../css/Forms.css';

export const AnalysisForm = () => {
    useSignals();
    const [numberMovements, setNumberMovements] = useState('');
    const [change, setChange] = useState('');

    const validateForm = () => {
        let formErrors = {};

        if (!selectedCurrency.value) {
            formErrors.selectedCurrency = 'Currency must be chosen.';
        }

        const numberMovementsInt = parseInt(numberMovements, 10);
        if (!numberMovements || isNaN(numberMovementsInt) || numberMovementsInt < 1) {
            formErrors.numberMovements = 'Trading days must be a whole number and at least 1.';
        }

        if (!change || isNaN(change) || parseFloat(change) <= 0) {
            formErrors.change = 'give a % higher than 0. The program will calculate the negative variant';
        }

        errors.value = formErrors;
        return Object.keys(formErrors).length === 0;
    };

    const pollStatus = async () => {

        let attempts = 0;
        const maxAttempts = 20; // Stop after 20 attempts (2 minutes with interval of 6 sec )
        const interval = 6000; // 6 seconds


        const checkStatus = async () => {
            console.log(`Polling attempt ${attempts}`);

            try {
                const response = await fetch('http://127.0.0.1:8000/api/analyze/results');
                const data = await response.json();

                console.log("Fetched results:", data);


                if (data.status === 'complete') {
                    if (data.results.length === 0) {
                        throw new Error('There are no currency pairs that meet these criteria.');
                        loading.value = false; //button reset
                    }
                    results.value = [...data.results]; // Update results by creating a new array
                    loading.value = false; //button reset
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(checkStatus, interval); // Try again after interval
                } else {
                    throw new Error('Analysis timed out');
                }
            } catch (err) {
                errors.value = { global: err.message };
                loading.value = false;
            }
        };

        checkStatus();
    };

    const startAnalysis = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        loading.value = true;
        errors.value = {}; // Reset any previous errors
        results.value = [];

        try {

            // Start the analysis
            const startResponse = await fetch('http://127.0.0.1:8000/api/analyze', {

                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ currency: selectedCurrency.value, numberMovements, change })
            });

            if (!startResponse.ok) {
                throw new errors('Error starting analysis');
            }

            // Start polling for the status
            pollStatus();
        } catch (err) {
            errors.value = { global: err.message };
            loading.value = false;
        }
    };



    return (
        <section className="analysis form-section">
            <div className='analysis form-wrapper'>
                <form className="analysis form" onSubmit={startAnalysis}>
                    {/* Currency Input */}
                    <CurrencySelector />

                    <span className='small'>A trading bot works best for a currency pair that is moving sideways. This means it often goes up and down, creating many opportunities to trade. Below you can choose how many times you want a currency pair to go up AND down for a certain percentage in the last 30 days. </span>

                    {/* # of changes Input */}
                    <div className="analysis form-group">
                        <label htmlFor="numberMovements">Number of ups and downs</label>
                        <input
                            type="number"
                            inputMode="decimal"
                            id="numberMovements"
                            name="numberMovements"
                            placeholder="How many ups and downs should a currency pair have made in the last 30 days?"
                            value={numberMovements ?? ''}
                            onChange={(e) => setNumberMovements(e.target.value)}
                        />
                        {errors.value?.numberMovements && <p className="error-message">{errors.value.numberMovements}</p>}
                    </div>

                    {/* CChanges Input */}
                    <div className="analysis form-group">
                        <label htmlFor="change">Change in %</label>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="change"
                            name="change"
                            placeholder="The minimum % the up&down movements need to be"
                            value={change}
                            onChange={handleNumberInput(setChange)}
                        />
                        {errors.value?.change && <p className="error-message">{errors.value.change}</p>}
                    </div>

                    {/* Submit Button */}
                    <button
                        className="analysis form-button"
                        type="submit"
                        disabled={loading.value}>
                        {loading.value ? "Analysis in progress..." : "Start analysis"}
                    </button>
                    {errors.value?.global && <p className="error-message">{errors.value.global}</p>}
                    {loading.value ? "Bear with us. The data from almost 350 currency pairs is being collected and analysed for you. This takes an average of 35 seconds " : ""}
                </form>

            </div>
        </section>
    );
};
