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
      
      // Update bot status in state
      setBots(prevBots =>
        prevBots.map(bot => (bot.id === botId ? { ...bot, status: updatedBot.status } : bot))
      );

      console.log('Bot stopped:', updatedBot);
    } catch (error) {
      console.error('Error stopping bot:', error);
      setError(error.message);
    }
  };

  return (
    <button onClick={handleStop} className='red-button'>
      Stop Bot
    </button>
  );
};
