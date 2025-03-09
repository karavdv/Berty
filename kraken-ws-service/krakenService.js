//krakenService.js
import WebSocket from 'ws';
import dotenv from 'dotenv';
import axios from 'axios';
import { getSubscriptions } from './subscriptionManager.js';

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

        krakenWs.on('message', (message) => {
            try {
                const data = JSON.parse(message);

                // Check if it is an OHLC update
                if (Array.isArray(data) && data.length > 1 && data[1].length >= 7) {
                    const [time, open, high, low, close, vwap, volume, count] = data[1];
                    console.log(`📊 OHLC update for ${pair} - Open: ${open}, High: ${high}, Low: ${low}, Close: ${close}`);

                    subscriptions[pair].bots.forEach(botId => {
                        // Send the price (close) to Laravel API
                        axios.post('http://127.0.0.1:8000/api/price-update', {
                            pair: pair,
                            price: close, // Send the closing price
                            top: high, // Send high to determine the top of the chart
                            botId: botId
                        }).then(() => {
                            console.log(`📡 Price update for ${pair}:`, close);
                        }).catch(error => {
                            console.error("❌ Error sending OHLC update to Laravel:", error);
                        });
                    });

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