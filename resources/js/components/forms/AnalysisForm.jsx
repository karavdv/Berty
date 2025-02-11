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

        // Validatie voor currency 
        if (!selectedCurrency.value) {
            formErrors.selectedCurrency = 'Currency must be chosen.';
        }

        // Validatie voor numberMovements (moet een integer en minstens 1 zijn )
        const numberMovementsInt = parseInt(numberMovements, 10);
        if (!numberMovements || isNaN(numberMovementsInt) || numberMovementsInt < 1) {
            formErrors.numberMovements = 'Trading days must be a whole number and at least 1.';
        }

        // Validatie voor change (moet een nummer hoger dan 0 zijn)
        if (!change || isNaN(change) || parseFloat(change) <= 0) {
            formErrors.change = 'give a % higher than 0. The program will calculate the negative variant';
        }

        errors.value = formErrors;
        return Object.keys(formErrors).length === 0;
    };

    const pollStatus = async () => {

        let attempts = 0;
        const maxAttempts = 20; // Stop na 20 pogingen (2 minuten met interval 6 sec is)
        const interval = 6000; // 6 seconden


        const checkStatus = async () => {
            console.log(`Polling attempt ${attempts}`); // Debugging

            try {
                const response = await fetch('http://127.0.0.1:8000/api/analyze/results');
                const data = await response.json();

                console.log("Fetched results:", data); // Debug output


                if (data.status === 'complete') {
                    if (data.results.length === 0) {
                        throw new Error('There are no currency pairs that meet these criteria.');
                        loading.value = false; //button resetten
                    }
                    results.value = [...data.results]; // Resultaten laten updaten door nieuwe array aan te maken
                    loading.value = false; //button resetten
                } else if (attempts < maxAttempts) {
                    attempts++;
                    setTimeout(checkStatus, interval); // Probeer opnieuw
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
        errors.value = {}; // Reset eventuele eerdere fouten
        results.value = [];

        try {

            // Start de analyse
            const startResponse = await fetch('http://127.0.0.1:8000/api/analyze', {

                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    /*'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),// Verkrijg het CSRF-token uit de meta-tag in de blade-view*/
                },
                body: JSON.stringify({ currency: selectedCurrency.value, numberMovements, change })
            });

            if (!startResponse.ok) {
                throw new errors('Error starting analysis');
            }

            // Start polling voor de status
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
                            value={numberMovements}
                            onChange={(e) => setNumberMovements(e.target.value)}
                        />
                        {errors.value.numberMovements && <p className="error-message">{errors.value.numberMovements}</p>}
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
                        {errors.value.change && <p className="error-message">{errors.value.change}</p>}
                    </div>

                    {/* Submit Button */}
                    <button
                        className="analysis form-button"
                        type="submit"
                        disabled={loading.value}>
                        {loading.value ? "Analysis in progress..." : "Start analysis"}
                    </button>
                    {errors.value.global && <p className="error-message">{errors.value.global}</p>}
                    {loading.value ? "Bear with us. The data from almost 350 currency pairs is being collected and analysed for you. This takes an average of 35 seconds " : ""}
                </form>

            </div>
        </section>
    );
};
