import React from 'react';

export const DryRunToggle = ({ botId, dryRun, setBots, setError }) => {

  const handleToggle = async () => {
    const newStatus = dryRun ? false : true;

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

      // Update only the changed bot in the state
      setBots(prevBots =>
        prevBots.map(bot => (bot.id === botId ? { ...bot, dry_run: updatedBot.dry_run } : bot))
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
        onChange={handleToggle} 
      />
    </label>
  );
};
