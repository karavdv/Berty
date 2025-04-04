//krakenService.js
import WebSocket from 'ws';
import dotenv from 'dotenv';
import axios from 'axios';
import { getSubscriptions, updatePriceHistory } from './subscriptionManager.js';

dotenv.config();

const krakenWsUrl = process.env.KRAKEN_WS_URL || 'wss://ws.kraken.com';

/**
 * Start a WebSocket connection with Kraken and subscribe to the OHLC feed.
 * @param {string} pair - The currency pair.
 * @param {function} onMessageCallback - Callback function that passes the received data.
 * @param {number} interval - Interval in minutes (1, 5, 15, 30, ...).
 */
export function subscribeToPair(pair, onMessageCallback, interval = 1) {
    const subscriptions = getSubscriptions();

    if (!pair) {
        console.error("⚠️ No currency pair provided for WebSocket subscription.");
        return null;
    }

    console.log(`🔄 Starting WebSocket connection for ${pair}...`);

    const krakenWs = new WebSocket(krakenWsUrl);

    krakenWs.on('open', () => {
        console.log(`✅ Connected to Kraken WebSocket for ${pair}`);

        const subscribeMessage = {
            event: "subscribe",
            pair: [pair],
            subscription: { name: "ohlc", interval: interval }
        };
        krakenWs.send(JSON.stringify(subscribeMessage));
        console.log(`📡 OHLC subscription sent for ${pair} with interval ${interval} min`);
    });

    const lastData = {};
    krakenWs.on('message', (message) => {
        try {
            const data = JSON.parse(message);

            // Check if it is an OHLC update
            if (Array.isArray(data) && data.length > 1 && data[1].length >= 7) {
                const [time, open, high, low, close, vwap, volume, count] = data[1];
                console.log(`📊 OHLC update for ${pair} - Open: ${open}, High: ${high}, Low: ${low}, Close: ${close}`);

                // Check if the closing price has changed before sending the update for processing
                if (lastData[pair] !== close) {
                    lastData[pair] = close;
                    console.log(`📡 lastData ${lastData[pair]}`);

                    const maxPrice = updatePriceHistory(pair, high);
                    console.log(`📈 Current Max Price: ${maxPrice}`);

                    subscriptions[pair].bots.forEach(botId => {
                        axios.post('http://127.0.0.1:8000/api/price-update', {
                            pair: pair,
                            price: close, // Send the closing price
                            top: maxPrice, // Send max price from last 7 days to determine the top of the chart
                            botId: botId
                        }).catch(error => {
                            console.error("❌ Error sending OHLC update to back-end:", error);
                        });
                    });
                } else {
                    console.log(`⚡ Skipped duplicate price update for ${pair}`);
                }

                // Execute callback function
                if (onMessageCallback) {
                    onMessageCallback({ pair, time, open, high, low, close, vwap, volume, count });
                }
            } else {
                console.log(`🔍 Received non-OHLC message: `, data);
            }
        } catch (err) {
            console.error("❌ Error parsing message: ", err);
        }
    });

    krakenWs.on('error', (err) => {
        console.error(`🚨 Error in Kraken WS for ${pair}:`, err);
    });

    krakenWs.on('close', () => {
        console.log(`🔴 WebSocket closed for ${pair}`);
    });

    return krakenWs;
}