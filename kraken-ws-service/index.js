//index.js
import dotenv from 'dotenv';
dotenv.config();

import express from 'express';
import http from 'http';
import { WebSocketServer } from 'ws'; // Gebruik de named import
import { subscribeToPair } from './krakenService.js';
import { getSubscriptions, setSubscriptions, saveSubscriptions } from './subscriptionManager.js';
import { restartSubscriptions } from './restartSubscriptions.js';


const app = express();
const port = process.env.PORT || 3000;
const server = http.createServer(app);
const subscriptions = getSubscriptions();

// Gebruik express.json() om JSON in de body te verwerken
app.use(express.json());

// Herstel alle actieve subscriptions bij een herstart
restartSubscriptions();

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
            }),
            bots: [botId]
        };
    }

// Voeg botId toe als die nog niet in de array voorkomt
if (!subscriptions[pair].bots.includes(botId)) {
    subscriptions[pair].bots.push(botId);
  }
    console.log(`✅ Bot ${botId} toegevoegd aan updates voor ${pair}`);

    setSubscriptions(subscriptions);  // Update de memory
    saveSubscriptions(subscriptions); // sla wijzigingen op voor bij herstart server


    res.json({ message: `Abonnement gestart voor bot ${botId} met valutapaar ${pair}` });
});

app.post('/unsubscribe', (req, res) => {
    const { pair, botId } = req.body;
    if (!pair || !botId) {
        return res.status(400).json({ error: 'Valutapaar en bot-ID zijn vereist' });
    }

    if (subscriptions[pair]) {

        subscriptions[pair].bots = subscriptions[pair].bots.filter(id => id !== botId);
        console.log(`🚫 Bot ${botId} verwijderd uit updates voor ${pair}`);

        // Als er geen bots meer zijn die dit valutapaar volgen, sluit de WebSocket
        if (subscriptions[pair].bots.length === 0) {
            console.log(`🛑 Geen actieve bots meer voor ${pair}, WebSocket wordt gesloten.`);
            subscriptions[pair].websocket.close();
            delete subscriptions[pair];
        }
    }

    setSubscriptions(subscriptions);  // Update de memory
    saveSubscriptions(subscriptions); // sla wijzigingen op voor bij herstart server


    res.json({ message: `Abonnement gestopt voor bot ${botId} met valutapaar ${pair}` });
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
