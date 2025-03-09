//restartSubscriptions.js

import axios from 'axios';
import { getSubscriptions, setSubscriptions, saveSubscriptions } from './subscriptionManager.js';
import { subscribeToPair } from './krakenService.js';

const subscriptions = getSubscriptions();

export async function restartSubscriptions() {
    try {
        // Fetch the list of active bot IDs from the database (Laravel API)
        const response = await axios.get('http://127.0.0.1:8000/api/active-bots');
        const activeBotIds = response.data.botIds; // Example: [1, 2, 3, 4]

        console.log("📌 Active bot IDs from database:", activeBotIds, typeof activeBotIds);

        // Loop through the saved subscriptions
        Object.keys(subscriptions).forEach(pair => {
            subscriptions[pair].bots.forEach(botId => {
                if (!activeBotIds.includes(botId)) {
                    console.log(`🗑️ Verwijder verouderde bot ${botId} uit ${pair}`);
                    subscriptions[pair].bots = subscriptions[pair].bots.filter(id => id !== botId);
                }
            });

            // If no bots remain, close the WebSocket and remove the subscription
            if (subscriptions[pair].bots.length === 0) {
                console.log(`🛑 No active bots left for ${pair}, closing WebSocket.`);
                if (typeof subscriptions[pair].websocket.close === 'function') {
                    subscriptions[pair].websocket.close();
                }
                delete subscriptions[pair];
            } else {
                // If there are still bots for this pair, resubscribe to the pair
                console.log(`♻️ Resubscribe for ${pair}...`);
                subscriptions[pair].websocket = subscribeToPair(pair, (price) => {
                    console.log(`📡 Price update for ${pair}: ${price}`);
                });
            }
        });

        setSubscriptions(subscriptions);  // Update memory
        saveSubscriptions(subscriptions); // Save changes for server restart
    } catch (error) {
        console.error("❌ Error fetching active bots from database:", error);
    }
}