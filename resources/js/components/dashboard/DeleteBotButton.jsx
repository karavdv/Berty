import React from 'react';

export const DeleteBotButton = ({ botId, setBots, setError }) => {
  const handleDelete = async () => {
    if (!window.confirm("Are you sure you want to delete this bot? Once deleted the data can not be recovered!")) {
      return;
    }

    setBots(prevBots => prevBots.filter(bot => bot.id !== botId));

    try {
      const response = await fetch(`http://127.0.0.1:8000/api/trading/${botId}/delete`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' }
      });

      if (!response.ok) {
        throw new Error(`Failed to delete bot: ${response.status}`);
      }

      console.log(`Bot ${botId} succesfully deleted.`);
    } catch (error) {
      console.error('Error occured when deleting bot:', error);
      setError(error.message);
    }
  };

  return (
    <button onClick={handleDelete} className='red-button'>
      Delete Bot
    </button>
  );
};
