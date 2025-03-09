import React, { useState } from 'react';
import { handleNumberInput } from "../forms/utils/handleNumberInput.js";
import '../../../css/Forms.css';

export const GoalForm = () => {
    const [capital, setCapital] = useState('');
    const [days, setDays] = useState('');
    const [margin, setMargin] = useState('');
    const [results, setResults] = useState(null);
    const [errors, setErrors] = useState({});

    const validateForm = () => {
        let formErrors = {};

        // Validation for capital (must be a float higher than 0)
        if (!capital || isNaN(capital) || parseFloat(capital) <= 0) {
            formErrors.capital = 'Capital must be a positive number.';
        }

        // Validation for days (must be an integer between 1 and 7)
        const daysInt = parseInt(days, 10);
        if (!days || isNaN(daysInt) || daysInt < 1 || daysInt > 7) {
            formErrors.days = 'Trading days must be an integer between 1 and 7.';
        }

        // Validation for margin (must be a float higher than 0)
        if (!margin || isNaN(margin) || parseFloat(margin) <= 0) {
            formErrors.margin = 'Profit margin must be a positive number.';
        }

        setErrors(formErrors);
        return Object.keys(formErrors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) return;

        try {
            const response = await fetch('http://127.0.0.1:8000/api/goal', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', "Accept": "application/json", },
                body: JSON.stringify({ capital, days, margin })
            });

            if (!response.ok) {
                throw new Error('Error fetching calculation data');
            }

            const data = await response.json();
            setResults(data);
        } catch (error) {
            console.error('Error:', error);
        }
    };

    return (
        <section className="goal form-section">
            <div className='goal form-wrapper'>
                <form className="goal form" onSubmit={handleSubmit}>
                    {/* Capital Input */}
                    <div className="goal form-group">
                        <label htmlFor="capital">Starting capital</label>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="capital"
                            name="capital"
                            placeholder="The capital you start investing with"
                            value={capital}
                            onChange={handleNumberInput(setCapital)}
                        />
                        {errors?.capital && <p className="error-message">{errors.capital}</p>}
                    </div>

                    {/* Days Input */}
                    <div className="goal form-group">
                        <label htmlFor="days">Number of trading days per week</label>
                        <input
                            type="number"
                            inputMode="decimal"
                            id="days"
                            name="days"
                            placeholder="How many days do you intend to trade per week?"
                            value={days}
                            onChange={(e) => setDays(e.target.value)}
                        />
                        {errors?.days && <p className="error-message">{errors.days}</p>}
                    </div>

                    {/* Margin Input */}
                    <div className="goal form-group">
                        <label htmlFor="margin">Intended profit margin</label>
                        <span className='small'>Keep it real ;)</span>
                        <input
                            type="number"
                            step="any"
                            inputMode="decimal"
                            id="margin"
                            name="margin"
                            placeholder="The average profit margin you set for trades"
                            value={margin}
                            onChange={handleNumberInput(setMargin)}
                        />
                        {errors?.margin && <p className="error-message">{errors.margin}</p>}
                    </div>

                    {/* Submit Button */}
                    <button type="submit" className="goal form-button">Calculate</button>
                </form>

                {/* Resultaten weergeven */}
                {results && (
                    <div className="results">
                        <h3>Results after 1 year of trading</h3>
                        <p>Trading Days: {results.days_traded}</p>
                        <p>In 1 year you will turn €{results.initial_capital} into €{results.final_capital} </p>
                        <p className='small'>Remember that these are possible results. Mathematically, these results are correct. However, there is no guarantee that all your trades will result in a wished for profit. In fact, on some trades, you will lose money. Your trade may also take longer to sell than you expected. Use these calculations as a guideline and motivation, not as a promise of results!</p>

                    </div>
                )}
            </div>
        </section>
    );
};
