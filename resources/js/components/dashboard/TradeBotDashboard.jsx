
import React, { useEffect, useState } from 'react';
import { StopBotButton } from './StopBotButton.jsx';

export const TradeBotDashboard = () => {
  const [bots, setBots] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Haal de dashboard data op
  const fetchDashboardData = async () => {
    try {
      const response = await fetch('http://127.0.0.1:8000/api/trading/dashboard');
      if (!response.ok) {
        throw new Error('Failed to fetch dashboard data');
      }
      const data = await response.json();
      setBots(data);
    } catch (err) {
      setError(err.message);
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  // Toggle functie voor het wijzigen van de status van een bot
  const handleToggle = async (botId, currentStatus) => {
    const newStatus = currentStatus === 'dry-run' ? 'live' : 'dry-run';
    try {
      const response = await fetch(`http://127.0.0.1:8000/api/trading/${botId}/toggle`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: newStatus })
      });
      if (!response.ok) {
        throw new Error('Failed to toggle bot status');
      }
      const updatedBot = await response.json();
      // Update de bot in de state
      setBots(bots.map(bot => (bot.id === botId ? updatedBot : bot)));
    } catch (err) {
      console.error(err);
      setError(err.message);
    }
  };

  if (loading) return <p>Loading dashboard...</p>;
  if (error) return <p>Error: {error}</p>;

  return (
    <div>
      <h2>TradeBot Dashboard</h2>
      {bots.map(bot => (
        <div key={bot.id} style={{ border: '1px solid #ccc', padding: '1rem', marginBottom: '1rem' }}>
          <h3>{bot.pair}</h3>
          <p>Status: {bot.status}</p>
          <label>
            Toggle Dry-run / Live:
            <input
              type="checkbox"
              checked={bot.status === 'live'}
              onChange={() => handleToggle(bot.id, bot.status)}
            />
          </label>
          <StopBotButton botId={bot.id}/>
          <p>Budget: €{bot.budget}</p>
          <p>Open Trades: €{bot.openTradeVolume}</p>
          <p>Profit: €{bot.profit}</p>

          <h4>Trades</h4>
          {bot.trades && bot.trades.length > 0 ? (
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Buy Time</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Buy Price</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Volume</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Sell Time</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Sell Price</th>
                </tr>
              </thead>
              <tbody>
                {bot.trades.map((trade, index) => (
                  <tr key={index}>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.buyTime}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.buyPrice}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.volume}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.sellTime || '-'}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.sellPrice || '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <p>No trades recorded.</p>
          )}
        </div>
      ))}
    </div>
  );
};
