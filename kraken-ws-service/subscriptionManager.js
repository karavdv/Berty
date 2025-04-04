//subscriptionManager.js
import fs from 'fs';
import { startHistoricalData } from './startHistoricalData.js';

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
    const currentTime = Math.floor(Date.now() / 1000); // Current time in seconds
    const currentDay = Math.floor(currentTime / 86400); // Calculate the current day (days since epoch)

    // Ensure historicalData exists for the pair
    if (!subscriptions[pair].historicalData) {
        (async () => {
            await startHistoricalData(pair);
        })();
    }

    // Find the entry for the current day
    let dailyEntry = subscriptions[pair].historicalData.find(entry => entry.day === currentDay);

    if (dailyEntry) {
        // Update the max price for the current day if the new high is greater
        if (high > dailyEntry.maxPrice) {
            dailyEntry.maxPrice = high;
            console.log(`📈 Updated max price for ${pair} on day ${currentDay}: ${high}`);
        } else {
            console.log(`📉 No update needed. Current max price for ${pair} on day ${currentDay}: ${dailyEntry.maxPrice}`);
        }
    } else {
        // Create a new entry for the current day
        subscriptions[pair].historicalData.push({
            day: currentDay,
            maxPrice: high,
        });
        console.log(`🆕 Added new max price for ${pair} on day ${currentDay}: ${high}`);
    }

    // Calculate timestamp for 7 days ago
    const sevenDaysAgo = currentDay - 7;

    // Filter historical data to only include entries from the last 7 days
    const dataArray = subscriptions[pair].historicalData;
    console.log(`📈 Data array before filtering: ${JSON.stringify(dataArray)}`);

    const recentData = dataArray.filter(entry => entry.day >= sevenDaysAgo);
    console.log(`📈 Data array after filtering: ${JSON.stringify(recentData)}`);

    subscriptions[pair].historicalData = recentData;
    console.log(`📈 New price history for ${pair} after converting; ${JSON.stringify(subscriptions[pair].historicalData)}`);

    // Find the maximum price from the recent data
    const maxPrice = Math.max(...recentData.map(entry => entry.maxPrice));
    console.log(`📈 Recent max price for ${pair}: ${maxPrice}`);

    return maxPrice;
}

/*

    subscriptions[pair].historicalData.push({
        time: Math.floor(Date.now() / 1000), // current time in seconds.Date.now() gives milliseconds
        price: high
    });

    // Calculate timestamp for 7 days ago
    const sevenDaysAgo = Date.now() / 1000 - 7 * 24 * 60 * 60;
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
*/


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