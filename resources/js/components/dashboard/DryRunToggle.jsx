import React from 'react';

export const DryRunToggle = ({ botId, dryRun, setBots, setError }) => {

  const handleToggle = async () => {
    const newStatus = dryRun ? false : true; // Wissel tussen true/false

    try {
      const response = await fetch(`http://127.0.0.1:8000/api/trading/${botId}/toggle`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
      });

      if (!response.ok) {
        throw new Error(`Failed to toggle bot status: ${response.status}`);
      }

      const updatedBot = await response.json();

      // Update alleen de gewijzigde bot in de state
      setBots(prevBots =>
        prevBots.map(bot => (bot.id === botId ? updatedBot : bot))
      );

    } catch (err) {
      console.error("Toggle error:", err);
      setError(err.message);
    }
  };

  return (
    <label className="tradebot-toggle">
      Dry-run / Live
      <input
        type="checkbox"
        checked={!dryRun} // true = unchecked (dry-run mode)
        onChange={handleToggle} // Direct de toggle-functie aanroepen
      />
    </label>
  );
};
