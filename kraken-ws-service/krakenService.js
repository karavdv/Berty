//krakenService.js
import WebSocket from 'ws';
import dotenv from 'dotenv';
import axios from 'axios';
import { getSubscriptions } from './subscriptionManager.js';



dotenv.config();

const krakenWsUrl = process.env.KRAKEN_WS_URL || 'wss://ws.kraken.com';

/**
 * Start een WebSocket-verbinding met Kraken en abonneer je op de OHLC-feed.
 * @param {string} pair - Het valutapaar.
 * @param {function} onMessageCallback - Callback functie die de ontvangen data doorgeeft.
 * @param {number} interval - Interval in minuten (1, 5, 15, 30, ...).
 */
export function subscribeToPair(pair, onMessageCallback, interval = 1) { // Interval in minuten (1, 5, 15, 30...)
    const subscriptions = getSubscriptions();

    if (!pair) {
        console.error("⚠️ Geen valutapaar opgegeven voor WebSocket-abonnement.");
        return null;
    }

    console.log(`🔄 Start WebSocket-verbinding voor ${pair}...`);

    const krakenWs = new WebSocket(krakenWsUrl);

    krakenWs.on('open', () => {
        console.log(`✅ Verbonden met Kraken WebSocket voor ${pair}`);

        const subscribeMessage = {
            event: "subscribe",
            pair: [pair],
            subscription: { name: "ohlc", interval: interval }
        };
        krakenWs.send(JSON.stringify(subscribeMessage));
        console.log(`📡 OHLC-abonnement verstuurd voor ${pair} met interval ${interval} min`);
    });

    krakenWs.on('message', (message) => {
        try {
            const data = JSON.parse(message);

            // Controleer of het een OHLC update is
            if (Array.isArray(data) && data.length > 1 && data[1].length >= 7) {
                const [time, open, high, low, close, vwap, volume, count] = data[1];
                console.log(`📊 OHLC update voor ${pair} - Open: ${open}, High: ${high}, Low: ${low}, Close: ${close}`);

                subscriptions[pair].bots.forEach(botId  => {
                    
                // Stuur de prijs (close) naar Laravel API
                axios.post('http://127.0.0.1:8000/api/price-update', {
                    pair: pair,
                    price: close, // Stuur de slotprijs
                    top: high, //stuur high mee om top van grafiek te bepalen
                    botId: botId
                }).then(() => {
                    console.log(`📡 Prijsupdate voor ${pair}:`, close);
                }).catch(error => {
                    console.error("❌ Fout bij verzenden OHLC update naar Laravel:", error);
                });

            });

                // Callback-functie uitvoeren
                if (onMessageCallback) {
                    onMessageCallback({ pair, time, open, high, low, close, vwap, volume, count });
                }
            } else {
                console.log(`🔍 Ontvangen niet-OHLC bericht: `, data);
            }
        } catch (err) {
            console.error("❌ Fout bij het parsen van bericht: ", err);
        }
    });

    krakenWs.on('error', (err) => {
        console.error(`🚨 Fout in Kraken WS voor ${pair}:`, err);
    });

    krakenWs.on('close', () => {
        console.log(`🔴 WebSocket gesloten voor ${pair}`);
    });

    return krakenWs;
}

