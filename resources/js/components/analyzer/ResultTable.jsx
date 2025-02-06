import React from "react";
import { useSignals } from "@preact/signals-react/runtime";
import { results } from "../../components/Signals.js";
import { ResultRow } from "./ResultRow.jsx";
import "../../../css/ResultTable.css";
import { useSignalEffect } from "@preact/signals-react";

export const ResultTable = () => {
    useSignals();

        // Verberg de tabel als er geen resultaten zijn
        if (!results.value || results.value.length === 0) {
            return null;
        }

    return (
        <div id="resultsTable">
            <h3>Results</h3>
            <div>These currency pairs meet the analysis criteria.</div>
            <table id="crypto-table">
                <thead>
                    <tr>
                        <th>Currency Pair</th>
                        <th>Increases <span className="arrow">↑</span></th>
                        <th>Decreases <span className="arrow">↓</span></th>
                        <th>Chart</th>
                    </tr>
                </thead>
                <tbody>
                    {results.value.map((result) => (
                        <ResultRow key={result.pair} result={result} />
                    ))}
                </tbody>
            </table>
        </div>
    );
};

