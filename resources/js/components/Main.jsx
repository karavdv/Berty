import React from 'react';
import ReactDOM from 'react-dom/client'; // Updated import for React 18
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import '../../css/app.css';
import { Home } from './Home.jsx';
import {Navbar} from './Navbar.jsx';
import {Analyse} from './analyzer/Analyse.jsx';


export const Main = () => {
    return (
        <Router>
            <Navbar />
            <Routes>
                <Route path="/" element={<Home />} />
                <Route path="/analyse" element={<Analyse />} />
                <Route path="/about" element={<h1>About Page</h1>} />
                <Route path="/contact" element={<h1>Contact Page</h1>} />
            </Routes>
        </Router>
    );
};

