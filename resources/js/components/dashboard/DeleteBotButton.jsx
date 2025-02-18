import React from 'react';

export const DeleteBotButton = ({ botId, setBots, setError }) => {
  const handleDelete = async () => {
    if (!window.confirm("Weet je zeker dat je deze bot wilt verwijderen? Dit kan niet ongedaan gemaakt worden!")) {
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

      console.log(`Bot ${botId} succesvol verwijderd.`);
    } catch (error) {
      console.error('Fout bij verwijderen bot:', error);
      setError(error.message);
    }
  };

  return (
    <button onClick={handleDelete} className='delete-bot-button'>
      Delete Bot
    </button>
  );
};
