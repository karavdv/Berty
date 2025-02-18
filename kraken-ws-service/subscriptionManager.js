//subscriptionManager.js
import fs from 'fs';

const subscriptionsFile = 'subscriptions.json';
let subscriptions = {};

// ✅ Functie om subscriptions te laden bij het starten van de server
export function loadSubscriptions() {
    try {
        if (fs.existsSync(subscriptionsFile)) {
            const data = fs.readFileSync(subscriptionsFile);
            console.log("✅ Subscriptions geladen vanaf bestand.");
            subscriptions = JSON.parse(data);
        }
    } catch (error) {
        console.error("❌ Fout bij laden van subscriptions:", error);
    }
}

// ✅ Functie om de huidige subscriptions op te halen (getter)
export function getSubscriptions() {
    return subscriptions;
}

// ✅ Functie om de huidige subscriptions in te stellen (setter)
export function setSubscriptions(newSubscriptions) {
    subscriptions = newSubscriptions;
}

// ✅ Functie om actieve subscriptions op te slaan bij afsluiten
export function saveSubscriptions() {
    try {
        fs.writeFileSync(subscriptionsFile, JSON.stringify(subscriptions, null, 2));
        console.log("💾 Actieve subscriptions opgeslagen.");
    } catch (error) {
        console.error("❌ Fout bij opslaan van subscriptions:", error);
    }
}

// 🛑 Zorg ervoor dat subscriptions worden opgeslagen bij afsluiten
process.on('SIGINT', () => {
    saveSubscriptions();
    process.exit();
});

process.on('SIGTERM', () => {
    saveSubscriptions();
    process.exit();
});

// 🚀 Laad subscriptions bij het starten
loadSubscriptions();
