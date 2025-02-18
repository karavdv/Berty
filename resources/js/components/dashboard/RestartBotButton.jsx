import React from 'react';

export const RestartBotButton = ({ botId, setBots, setError }) => {
  const handleStart = async () => {
    try {
      const response = await fetch(`http://127.0.0.1:8000/api/trading/${botId}/restart`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });

      if (!response.ok) {
        throw new Error(`Failed to start bot: ${response.status}`);
      }

      const updatedBot = await response.json();

      // Update de botstatus in het dashboard
      setBots(prevBots =>
        prevBots.map(bot => (bot.id === botId ? updatedBot.bot : bot))
      );

      console.log('Bot gestart:', updatedBot);
    } catch (error) {
      console.error('Fout bij starten bot:', error);
      setError(error.message);
    }
  };

  return (
    <button onClick={handleStart} className='green-button'>
      Start Bot
    </button>
  );
};
