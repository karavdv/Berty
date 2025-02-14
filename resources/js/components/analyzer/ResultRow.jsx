import React from "react";

export const ResultRow = ({ result }) => {

    return (
        <tr>
            <td>{result.pair}</td>
            <td>{result.rises}</td>
            <td>{result.declines}</td>
            <td>
                <button
                    className="chart-link"
                    onClick={() => {
                        const url = `https://pro.kraken.com/app/trade/${result.pair.replace(
                            "/",
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

