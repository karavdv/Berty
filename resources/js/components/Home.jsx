import React from 'react';
//import '../../css/Home.css';
import { GoalForm } from './forms/GoalForm.jsx';



export const Home = () => {
    return (
        <>
            <section className='top'>
                <h1>Welcome to the Berty trading bot</h1>
                <p>Let's get you started with some goal setting.</p>
                <p>“Setting goals is the first step in turning the invisible into the visible.” – Tony Robbins</p>
                <GoalForm />
            </section>

        </>
    );
};
