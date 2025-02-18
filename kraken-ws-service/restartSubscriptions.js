//restartSubscriptions.js
import axios from 'axios';
import { getSubscriptions, setSubscriptions, saveSubscriptions } from './subscriptionManager.js';
import { subscribeToPair } from './krakenService.js';


const subscriptions = getSubscriptions();

export async function restartSubscriptions() {
    try {
        // Vraag de lijst van actieve bot-ID's op uit de database (Laravel API)
        const response = await axios.get('http://127.0.0.1:8000/api/active-bots');
        const activeBotIds = response.data.botIds; // Voorbeeld: [1, 2, 3, 4]

        console.log("📌 Actieve bot-ID's uit database:", activeBotIds);

        // Loop door de opgeslagen subscriptions
        Object.keys(subscriptions).forEach(pair => {
            subscriptions[pair].bots.forEach(botId => {
                if (!activeBotIds.includes(botId)) {
                    console.log(`🗑️ Verwijder verouderde bot ${botId} uit ${pair}`);
                    subscriptions[pair].bots = subscriptions[pair].bots.filter(id => id !== botId);
                }
            });

            // Als er geen bots meer overblijven, sluit de WebSocket en verwijder het abonnement
            if (subscriptions[pair].bots.length === 0) {
                console.log(`🛑 Geen actieve bots meer voor ${pair}, WebSocket wordt gesloten.`);
                if (typeof subscriptions[pair].websocket.close === 'function') {
                    subscriptions[pair].websocket.close();
                }
                delete subscriptions[pair];
            }

            console.log(`♻️ Herabonneren op ${pair}...`);
            subscriptions[pair].websocket = subscribeToPair(pair, (price) => {
                console.log(`📡 Prijsupdate voor ${pair}: ${price}`);
        
            });

        });
        setSubscriptions(subscriptions);  // Update de memory
        saveSubscriptions(subscriptions); // sla wijzigingen op voor bij herstart server
    } catch (error) {
        console.error("❌ Fout bij ophalen van actieve bots uit database:", error);
    }
}


