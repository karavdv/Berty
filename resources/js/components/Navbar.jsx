import React, { useState, useEffect } from 'react';
import { NavLink } from 'react-router-dom';
import '../../css/Navbar.css';

export const Navbar = () => {
    const [isOpen, setIsOpen] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);

    const toggleMenu = () => {
        setIsOpen(!isOpen);
    };

    // Detect scrolling and change navbar position accordingly
    useEffect(() => {
        const handleScroll = () => {
            if (window.scrollY > 50) {
                setIsScrolled(true);
            } else {
                setIsScrolled(false);
            }
        };

        window.addEventListener('scroll', handleScroll);
        return () => {
            window.removeEventListener('scroll', handleScroll);
        };
    }, []);

    return (
        <nav className={`navbar ${isScrolled ? 'navbar-top' : 'navbar-bottom'}`}>
            <div className="navbar-container">
                <NavLink to="/" className="navbar-logo">Berty</NavLink>

                {/* Hamburger Menu */}
                <div className="hamburger" onClick={toggleMenu}>
                    <div className={`bar ${isOpen ? 'open' : ''}`}></div>
                    <div className={`bar ${isOpen ? 'open' : ''}`}></div>
                    <div className={`bar ${isOpen ? 'open' : ''}`}></div>
                </div>

                {/* Nav Links - Dynamic dropdown menu */}
                <ul className={`navbar-links ${isOpen ? 'open' : ''} ${isScrolled ? 'dropdown-down' : 'dropdown-up'}`}>
                    <li><NavLink to="/" onClick={toggleMenu}>Home</NavLink></li>
                    <li><NavLink to="/analyse" onClick={toggleMenu}>Analyse</NavLink></li>
                    <li><NavLink to="/tradebot" onClick={toggleMenu}>Tradebot</NavLink></li>
                    <li><NavLink to="/dashboard" onClick={toggleMenu}>Dashboard</NavLink></li>

                </ul>
            </div>
        </nav>
    );
};