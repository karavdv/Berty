// index.js
import dotenv from 'dotenv';
dotenv.config();

import express from 'express';
import http from 'http';
import { WebSocketServer } from 'ws'; // Gebruik de named import
import { startKrakenWebSocket } from './krakenService.js';

const app = express();
const port = process.env.PORT || 3000;
const server = http.createServer(app);

// Start een WebSocket-server voor je clients
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

// Start de Kraken WebSocket service en verstuur updates naar de clients
startKrakenWebSocket((currentPrice) => {
    broadcast({ pair: process.env.SUBSCRIBE_PAIR || 'XBT/USD', price: currentPrice });
});

server.listen(port, () => {
    console.log(`Server draait op poort ${port}`);
});
