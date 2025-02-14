import dotenv from 'dotenv';
dotenv.config();

import express from 'express';
import http from 'http';
import { WebSocketServer } from 'ws'; // Gebruik de named import
import { subscribeToPair } from './krakenService.js';

const app = express();
const port = process.env.PORT || 3000;
const server = http.createServer(app);

// Gebruik express.json() om JSON in de body te verwerken
app.use(express.json());

// Globale map voor actieve abonnementen
const subscriptions = {};

app.post('/subscribe', (req, res) => {
    const { pair, botId } = req.body;
    if (!pair || !botId) {
        return res.status(400).json({ error: 'Valutapaar en bot-ID zijn vereist' });
    }

    // Controleer of er al een WebSocket-verbinding voor dit valutapaar is
    if (!subscriptions[pair]) {
        console.log(`🆕 Nieuwe WebSocket-abonnement gestart voor ${pair}`);
        subscriptions[pair] = {
            websocket: subscribeToPair(pair, (price) => {
                console.log(`📡 Prijsupdate voor ${pair}: ${price}`);
                // Stuur de prijsupdate naar ALLE bots die dit valutapaar volgen
                if (subscriptions[pair].bots) {
                    subscriptions[pair].bots.forEach(botId => {
                        axios.post('http://127.0.0.1:8000/api/price-update', {
                            pair: pair,
                            price: price,
                            botId: botId
                        }).catch(error => console.error(`❌ Fout bij verzenden update naar bot ${botId}:`, error));
                    });
                }
            }),
            bots: new Set() // Set met alle bot-ID's die dit valutapaar volgen
        };
    }

    // Voeg de bot-ID toe aan de lijst van bots die dit valutapaar volgen
    subscriptions[pair].bots.add(botId);
    console.log(`✅ Bot ${botId} toegevoegd aan updates voor ${pair}`);

    res.json({ message: `Abonnement gestart voor bot ${botId} met valutapaar ${pair}` });
});


// Start een WebSocket-server voor clients
const wss = new WebSocketServer({ server });

wss.on('connection', (ws) => {
    console.log('Client verbonden');
    ws.send(JSON.stringify({ message: 'Verbonden met Kraken WS relay' }));
});

// Functie om berichten naar alle verbonden clients te sturen
function broadcast(data) {
    wss.clients.forEach(client => {
        if (client.readyState === client.OPEN) {
            client.send(JSON.stringify(data));
        }
    });
}

// Start de server
server.listen(port, () => {
    console.log(`Server draait op poort ${port}`);
});
