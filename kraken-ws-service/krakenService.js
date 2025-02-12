// krakenService.js
import WebSocket from 'ws';
import dotenv from 'dotenv';
import redis from './redisClient.js';

dotenv.config();

const krakenWsUrl = process.env.KRKEN_WS_URL || 'wss://ws.kraken.com';
const subscribePair = process.env.SUBSCRIBE_PAIR || 'XBT/EUR';

/**
 * Start een WebSocket-verbinding met Kraken en abonneer je op de ticker.
 * De callback wordt aangeroepen met de huidige prijs en het hele data-object.
 */
export function startKrakenWebSocket(onMessageCallback) {
    const krakenWs = new WebSocket(krakenWsUrl);

    krakenWs.on('open', () => {
        console.log('Verbonden met Kraken WebSocket');
        const subscribeMessage = {
            event: "subscribe",
            pair: [subscribePair],
            subscription: { name: "ticker" }
        };
        krakenWs.send(JSON.stringify(subscribeMessage));
        console.log(`Abonnement verstuurd voor ${subscribePair}`);
    });

    krakenWs.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            // Controleer of het bericht een ticker update is
            if (Array.isArray(data) && data[1] && data[1].c) {
                const currentPrice = parseFloat(data[1].c[0]);
                console.log(`Nieuwe prijs voor ${subscribePair}: ${currentPrice}`);
                // Schrijf de prijs naar Redis
                redis.set('kraken_latest_price', currentPrice);
                // Roep de callback aan, zodat de index.js de update kan verwerken
               // Publiceer de update op het kanaal 'kraken_updates'
            redis.publish('kraken_updates', JSON.stringify({
                pair: subscribePair,
                price: currentPrice
            }));
                onMessageCallback(currentPrice, data);
            } else {
                console.log('Ontvangen bericht (geen ticker update): ', data);
            }
        } catch (err) {
            console.error("Fout bij het parsen van bericht: ", err);
        }
    });

    krakenWs.on('error', (err) => {
        console.error('Fout in Kraken WS:', err);
    });

    krakenWs.on('close', () => {
        console.log('Kraken WebSocket gesloten');
    });

    return krakenWs;
}
