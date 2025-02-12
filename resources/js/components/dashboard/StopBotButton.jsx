import React from 'react';

export const StopBotButton = ({ botId }) => {
  const handleStop = async () => {
    try {
      const response = await fetch(`http://127.0.0.1:8000/api/trading/${botId}/stop`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
      });
      const data = await response.json();
      console.log('Bot gestopt:', data);
      // Update eventueel de dashboard state om de status aan te passen
    } catch (error) {
      console.error('Fout bij stoppen bot:', error);
    }
  };

  return (
    <button onClick={handleStop}>
      Stop Bot
    </button>
  );
};
