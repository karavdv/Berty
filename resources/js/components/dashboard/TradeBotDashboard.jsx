import React, { useEffect, useState } from 'react';
import '../../../css/TradeBotDashboard.css';
import { StopBotButton } from './StopBotButton.jsx';
import { DryRunToggle } from './DryRunToggle.jsx';
import { RestartBotButton } from './RestartBotButton.jsx';
import { DeleteBotButton } from './DeleteBotButton.jsx';

export const TradeBotDashboard = () => {
  const [bots, setBots] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Haal de dashboard data op
  const fetchDashboardData = async () => {
    try {
      const response = await fetch('http://127.0.0.1:8000/api/trading/dashboard');
      if (!response.ok) {
        throw new Error(`Failed to fetch dashboard data: ${response.status}`);
      }
      const data = await response.json();
      setBots(data);
    } catch (err) {
      setError(err.message);
      console.error("Fetch error:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);



  if (loading) return <p>Loading dashboard...</p>;
  if (error) return <p>Error: {error}</p>;

  return (
    <section className='top tradebot-dashboard'>
      <h2>TradeBot Dashboard</h2>
      {bots.map(bot => (
        <div key={bot.id} className='tradebot-card'>
          <h3>{bot.pair}</h3>
          <p>ID {bot.id}</p>
          <div className="tradebot-status">
            <p>Status: {bot.status}</p>
            <p><DryRunToggle botId={bot.id} dryRun={bot.dry_run} setBots={setBots} setError={setError} /></p>
          </div>

          {bot.status === 'active' ? (
            <StopBotButton botId={bot.id} setBots={setBots} setError={setError} />
          ) : (
            <RestartBotButton botId={bot.id} setBots={setBots} setError={setError} />
          )}   

          <DeleteBotButton botId={bot.id} setBots={setBots} setError={setError} />       
          
          <p>Budget: €{bot.budget}</p>
          <p>Open Trades: €{bot.openTradeVolume ?? 0}</p>
          <p>Profit: €{bot.profit ?? 0}</p>

          <div className='bot-settings'>
            <h3>Bot settings</h3>
            <p>Trade amount: €{bot.trade_size}</p>
            <p>Drop: {bot.drop_threshold}%</p>
            <p>Profit: {bot.profit_threshold}%</p>
            <p>Start buy: {bot.start_buy}</p>
            <p>Accumulate: {bot.accumulate ? 'Yes' : 'No'}</p>
            <p>Top: {bot.top_edge ?? 'N/A'}%</p>
            <p>Stop-loss: {bot.stop_loss ?? 'N/A'}</p>
          </div>
          <h4>Trades</h4>
          {bot.trades && bot.trades.length > 0 ? (
            <table className='trade-table'>
              <thead>
                <tr>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Buy Time</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Buy Price</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Volume</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Sell Time</th>
                  <th style={{ border: '1px solid #ddd', padding: '8px' }}>Sell Amount</th>
                </tr>
              </thead>
              <tbody>
                {bot.trades.map((trade, index) => (
                  <tr key={index}>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.buyTime}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.buyPrice}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.volume}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.sellTime || '-'}</td>
                    <td style={{ border: '1px solid #ddd', padding: '8px' }}>{trade.sellAmount || '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <p>No trades recorded yet.</p>
          )}
        </div>
      ))}
    </section>
  );
};
