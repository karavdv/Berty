//index.js
import dotenv from 'dotenv';
dotenv.config();

import express from 'express';
import http from 'http';
import { WebSocketServer } from 'ws'; // Use named import
import { subscribeToPair } from './krakenService.js';
import { getSubscriptions, setSubscriptions, saveSubscriptions } from './subscriptionManager.js';
import { restartSubscriptions } from './restartSubscriptions.js';


const app = express();
const port = process.env.PORT || 3000;
const server = http.createServer(app);
const subscriptions = getSubscriptions();

// Use express.json() to process JSON in the body
app.use(express.json());

// Restore all active subscriptions on restart
restartSubscriptions();

app.post('/subscribe', (req, res) => {
    const { pair, botId } = req.body;
    if (!pair || !botId) {
        return res.status(400).json({ error: 'Currency pair and bot ID are required' });
    }

    // Check if there already is a WebSocket connection for this currency pair
    if (!subscriptions[pair]) {
        console.log(`🆕 Starting new WebSocket subscription for ${pair}`);
        subscriptions[pair] = {
            websocket: subscribeToPair(pair, (price) => {
                console.log(`📡 Price update for ${pair}`);
            }),
            bots: [botId]
        };
    }

    // Add botId if it is not already in the array
    if (!subscriptions[pair].bots.includes(botId)) {
    subscriptions[pair].bots.push(botId);
  }
  console.log(`✅ Bot ${botId} added to updates for ${pair}`);

    setSubscriptions(subscriptions);  // Update memory
    saveSubscriptions(subscriptions); // Save changes for server restart


    res.json({ message: `Subscription started for bot ${botId} with currency pair ${pair}` });
});

app.post('/unsubscribe', (req, res) => {
    const { pair, botId } = req.body;
    if (!pair || !botId) {
        return res.status(400).json({ error: 'Currency pair and bot ID are required' });
    }

    if (subscriptions[pair]) {

        subscriptions[pair].bots = subscriptions[pair].bots.filter(id => id !== botId);
        console.log(`🚫 Bot ${botId} removed from updates for ${pair}`);

        // If there are no more bots subscribed to this currency pair, close the WebSocket
        if (subscriptions[pair].bots.length === 0) {
            console.log(`🛑 Geen actieve bots meer voor ${pair}, WebSocket wordt gesloten.`);
            subscriptions[pair].websocket.close();
            delete subscriptions[pair];
        }
    }
    setSubscriptions(subscriptions);  // Update memory
    saveSubscriptions(subscriptions); // Save changes for server restart

    res.json({ message: `Subscription stopped for bot ${botId} with currency pair ${pair}` });
});

// Start a WebSocket server for clients
const wss = new WebSocketServer({ server });

wss.on('connection', (ws) => {
    console.log('Client connected');
    ws.send(JSON.stringify({ message: 'Connected to Kraken WS relay' }));
});

// Function to send messages to all connected clients
function broadcast(data) {
    wss.clients.forEach(client => {
        if (client.readyState === client.OPEN) {
            client.send(JSON.stringify(data));
        }
    });
}

// Start server
server.listen(port, () => {
    console.log(`Server running on port ${port}`);
});
