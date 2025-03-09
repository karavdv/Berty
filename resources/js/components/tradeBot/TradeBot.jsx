import React from 'react';
import { useSignals } from "@preact/signals-react/runtime";
import { showOverviewPage } from "../../components/Signals.js";
import { TradeBotForm } from '../forms/TradeBotForm.jsx';
import { TradeBotOverview } from './TradeBotOverview.jsx';

export const TradeBot = () => {
    useSignals();

    return (
        <section className='top' >
            <h2>Ready to start your bot?</h2>
            {!showOverviewPage.value ?
                <>
                    <p>Take your time to do your research! Then fill out this form to start your bot!</p>
                    <TradeBotForm />
                </>
                :
                <>
                    <TradeBotOverview />
                </>
            }
        </section>
    );
};

