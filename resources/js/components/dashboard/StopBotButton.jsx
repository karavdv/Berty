import React from 'react';

export const StopBotButton = ({ botId, setBots, setError }) => {
  const handleStop = async () => {
    try {
      const response = await fetch(`http://127.0.0.1:8000/api/trading/${botId}/stop`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });

      if (!response.ok) {
        throw new Error(`Failed to stop bot: ${response.status}`);
      }

      const updatedBot = await response.json();
      
      // Update bot status in het dashboard
      setBots(prevBots =>
        prevBots.map(bot => (bot.id === botId ? updatedBot : bot))
      );

      console.log('Bot gestopt:', updatedBot);
    } catch (error) {
      console.error('Fout bij stoppen bot:', error);
      setError(error.message);
    }
  };

  return (
    <button onClick={handleStop} className='stop-bot-button'>
      Stop Bot
    </button>
  );
};
