import React from 'react';
//import '../../../css/Analyse.css';
import { AnalysisForm } from '../forms/AnalysisForm.jsx';
import { ResultTable } from './ResultTable.jsx';

export const Analyse = () => {

    return (
        <section className='top' >
            <h2>Ready to find the best currency pair for your trading bot?</h2>
            <p>Let's get you started.</p>
            <AnalysisForm />
            <ResultTable />
        </section>
    );
};

