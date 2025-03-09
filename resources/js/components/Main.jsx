import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import '../../css/app.css';
import { Home } from './Home.jsx';
import { Navbar } from './Navbar.jsx';
import { Analyse } from './analyzer/Analyse.jsx';
import { TradeBot } from './tradeBot/TradeBot.jsx';
import { TradeBotDashboard } from './dashboard/TradeBotDashboard.jsx';

export const Main = () => {
    return (
    <>
        <Router>
            <Navbar />
            <Routes>
                <Route path="/" element={<Home />} />
                <Route path="/analyse" element={<Analyse />} />
                <Route path="/tradebot" element={<TradeBot />} />
                <Route path="/dashboard" element={<TradeBotDashboard />} />
            </Routes>
        </Router>
        </>
    );
};

