import React from "react";

export const ResultRow = ({ result }) => {
    const formatPair = (pair, divider) =>
        `${pair.slice(0, -3)}${divider}${pair.slice(-3)}`;

    return (
        <tr>
            <td>{formatPair(result.pair, "/")}</td>
            <td>{result.rises}</td>
            <td>{result.declines}</td>
            <td>
                <button
                    className="chart-link"
                    onClick={() => {
                        const url = `https://pro.kraken.com/app/trade/${formatPair(
                            result.pair,
                            "-"
                        ).toLowerCase()}`;
                        window.open(url, "_blank");
                    }}
                >
                    Bekijk grafiek
                </button>
            </td>
        </tr>
    );
};

