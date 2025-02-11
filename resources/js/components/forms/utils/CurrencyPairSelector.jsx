import React, { useEffect, useState } from "react";
import { selectedCurrency, availablePairs, selectedPair, loadingPairs, errors } from "../../Signals.js";
import { useSignals } from "@preact/signals-react/runtime";

export const CurrencyPairSelector = () => {
  useSignals();
  const [searchQuery, setSearchQuery] = useState("");

  useEffect(() => {
    const fetchCurrencyPair = async () => {
      // Voer de fetch alleen uit als er een currency is geselecteerd
      if (!selectedCurrency.value) return;
      
      loadingPairs.value = true;
      errors.value = null;

      try {
        const response = await fetch(
          `http://127.0.0.1:8000/api/currency-pairs/${selectedCurrency.value}`
        );
        const data = await response.json();

        if (Array.isArray(data)) {
          // Update de beschikbare paren door een nieuwe array aan te maken
          availablePairs.value = [...data];
          loadingPairs.value = false;
        } else {
          // Indien gewenst: implementeer hier retry-logica
          throw new Error("Error fetching currency pairs");
        }
      } catch (err) {
        errors.value = { global: err.message };
        loadingPairs.value = false;
      }
    };

    fetchCurrencyPair();
  }, [selectedCurrency.value]); // Voer de fetch uit telkens wanneer de geselecteerde currency verandert

  // Filter valutaparen op basis van de zoekopdracht
  const filteredPairs =
    availablePairs.value?.filter((pair) =>
      pair.toLowerCase().includes(searchQuery.toLowerCase())
    ) || [];

  // Verberg de component als er geen currency geselecteerd is
  if (!selectedCurrency.value) return null;

  return (
    <div className="analysis form-group">
      <label htmlFor="currencyPair">Select a trading pair</label>

      {/* Zoekveld voor valutaparen */}
      <input
        type="text"
        placeholder="Search pair..."
        value={searchQuery}
        onChange={(e) => setSearchQuery(e.target.value)}
      />

      {/* Select dropdown voor valutaparen */}
      <select
        id="currencyPair"
        name="currencyPair"
        value={selectedPair.value || ""}
        onChange={(e) => (selectedPair.value = e.target.value)}
      >
        <option value="" disabled>
          Select a pair
        </option>
        {loadingPairs.value ? (
          <option>Loading...</option>
        ) : errors.value ? (
          <option disabled>Error: {errors.value.global}</option>
        ) : filteredPairs.length > 0 ? (
          filteredPairs.map((pair) => (
            <option key={pair} value={pair}>
              {pair}
            </option>
          ))
        ) : (
          <option disabled>No pairs found</option>
        )}
      </select>
    </div>
  );
};
