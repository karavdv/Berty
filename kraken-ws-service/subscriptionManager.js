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