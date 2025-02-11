import React from "react";
import { selectedCurrency, errors } from "../../Signals.js";
import { useSignals } from "@preact/signals-react/runtime";

export const CurrencySelector = () => {
    useSignals();

    const currencies = [
        { code: "AUD", name: "Australian Dollar" },
        { code: "BTC", name: "Bitcoin" },
        { code: "CAD", name: "Canadian Dollar" },
        { code: "CHF", name: "Swiss Franc" },
        { code: "DAI", name: "DAI Stablecoin" },
        { code: "ETH", name: "Ethereum" },
        { code: "EUR", name: "Euro" },
        { code: "GBP", name: "British Pound" },
        { code: "JPY", name: "Japanese Yen" },
        { code: "POL", name: "Polygon" },
        { code: "PYUSD", name: "PayPal USD" },
        { code: "USD", name: "US Dollar" },
        { code: "USDC", name: "USD Coin" },
        { code: "USDT", name: "Tether" }
    ];

    return (
        <div className="analysis form-group">
            <label htmlFor="currency">What currency do you use</label>
            <select
                id="currency"
                name="currency"
                value={selectedCurrency.value || ""}
                onChange={(e) => (selectedCurrency.value = e.target.value)}
            >
                <option value="" disabled>Select currency</option>
                {currencies.map(({ code, name }) => (
                    <option key={code} value={code}>
                        {code} - {name}
                    </option>
                ))}
            </select>
            {errors.value?.selectedCurrency && <p className="error-message">{errors.value.selectedCurrency}</p>}
        </div>
    );
};
