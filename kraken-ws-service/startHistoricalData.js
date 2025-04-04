/**
 * Retreive historical data from Kraken API for a given pair to determine a top when a newsubscrition is opened.
 */

import axios from 'axios';

export async function startHistoricalData(pair) {
    const currentTime = Math.floor(Date.now() / 1000); // current time in seconds
    const sevenDaysAgo = currentTime - 7 * 24 * 60 * 60; // 7 days ago in seconds
    const historicalData = [];

    try {
        // Kraken REST API endpoint for OHLC data
        const response = await axios.get('https://api.kraken.com/0/public/OHLC', {
            params: {
                pair: pair,
                interval: 1440, // 1440 minuts = 1 day, so we receive one data point per day
                since: sevenDaysAgo
            }
        });

        if (response.data && response.data.result) {
            // De API returns an object with the pair as a key
            const pairData = response.data.result[pair];
            if (pairData && Array.isArray(pairData)) {
                pairData.forEach(ohlc => {
                    // ohlc array: [time, open, high, low, close, vwap, volume, count]
                    const [time, open, high, low, close, vwap, volume, count] = ohlc;
                    historicalData.push({
                        day: time/ 86400, // Convert time to days since epoch
                        maxPrice: high
                    });
                });
                console.log(`✅ Historical data for ${pair} loaded.  ${JSON.stringify(historicalData)}`);
            }
        }

        if (historicalData.length === 0) {
            console.log(`⚠️ No historical data found for ${pair}`);
        } else {
            return historicalData;
        }
    } catch (error) {
        console.error(`❌ Error while retreiving historical data for ${pair}:`, error);
    }
}