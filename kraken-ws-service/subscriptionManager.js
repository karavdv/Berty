//subscriptionManager.js
import fs from 'fs';

const subscriptionsFile = 'subscriptions.json';
let subscriptions = {};

// Function to load subscriptions when the server starts
export function loadSubscriptions() {
    try {
        if (fs.existsSync(subscriptionsFile)) {
            const data = fs.readFileSync(subscriptionsFile);
            console.log("✅ Subscriptions loaded from file.");
            subscriptions = JSON.parse(data);
        }
    } catch (error) {
        console.error("❌ Error loading subscriptions:", error);
    }
}

// Function to get the current subscriptions (getter)
export function getSubscriptions() {
    return subscriptions;
}

// Function to set the current subscriptions (setter)
export function setSubscriptions(newSubscriptions) {
    subscriptions = newSubscriptions;
}

// Function to save active subscriptions when shutting down
export function saveSubscriptions() {
    try {
        fs.writeFileSync(subscriptionsFile, JSON.stringify(subscriptions, null, 2));
        console.log("💾 Active subscriptions saved.");
    } catch (error) {
        console.error("❌ Error saving subscriptions:", error);
    }
}

export function updatePriceHistory(pair, high) {
    console.log(`📈 Old price history for ${pair}; ${JSON.stringify(subscriptions[pair].historicalData)}`);
    subscriptions[pair].historicalData.push({
        time: Math.floor(Date.now() / 1000), // current time in seconds.Date.now() gives milliseconds
        price: high
    });
    console.log(`📈 New price history for ${pair} after adding; ${JSON.stringify(subscriptions[pair].historicalData)}`);


    // Calculate timestamp for 7 days ago
    const sevenDaysAgo = Date.now()/1000 - 7 * 24 * 60 * 60;
    console.log(`📈 Seven days ago timestamp: ${sevenDaysAgo}`);

    // Filter historical data to only include entries from the last 7 days
    const dataArray = subscriptions[pair].historicalData;
    console.log(`📈 Data array before filtering: ${JSON.stringify(dataArray)}`);
    const recentData = dataArray.filter(entry => entry.time >= sevenDaysAgo);
    console.log(`📈 Data array after filtering: ${JSON.stringify(recentData)}`);
    subscriptions[pair].historicalData = recentData;
    console.log(`📈 New price history for ${pair} after converting; ${JSON.stringify(subscriptions[pair].historicalData)}`);
    // Find the maximum price from the recent data
    const maxPrice = Math.max(...recentData.map(entry => entry.price));
    console.log(`📈 Recent max price for ${pair}: ${maxPrice}`);

    return maxPrice;
}

// Ensure subscriptions are saved on exit
process.on('SIGINT', () => {
    saveSubscriptions();
    process.exit();
});

process.on('SIGTERM', () => {
    saveSubscriptions();
    process.exit();
});

// Load subscriptions when starting
loadSubscriptions();