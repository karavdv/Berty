<?php

namespace App\Http\Controllers;
    

use App\Services\KrakenApiServicePublic;
use App\Services\KrakenApiServicePrivate;
use App\Http\Controllers\Controller;

class TradingController extends Controller
{
    protected $krakenApi;

    public function __construct(KrakenApiServicePrivate $krakenApi)
    {
        $this->krakenApi = $krakenApi;
    }

    public function testApi()
    {
        $response = $this->krakenApi->sendRequest('/0/private/Balance');
        return response()->json($response);
    }
}


