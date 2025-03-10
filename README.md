# Crypto trading bot for Kraken in Laravel + React

***This project is still a work in progress. You are free to test the code, but only using dry-run mode. Live trading is not yet implemented, and any live trading code is commented out for safety.***

The goal of the project was to learn a framework in 30 days.
I chose the combination of Laravel and React to create a full-stack project. Additionally, I use Node.js to establish a WebSocket connection with Kraken to receive OHLC (Open-High-Low-Close) data for the trading bot.

The project is a work in progress.

- The lay-out at this point is extremely basic, no more than is needed to show the results with just some colors to make it less boring to look at.

- Live trading does not work yet. The API connection to place the trades and the toggle that handles the status in the databse and back-end work, so the basic bot functions of buying and selling will work. So leave the dry-run toggle alone when you test the code. As extra security the live-code that already exists is commented out. 
I need to implement or improve several features like first-buy, Top, stop-loss and better error handling before it should be used. The buys and sells at the moment are 'market'-type. This will be changed to 'profit-limit'. The existing code for 'live' is just to test the API calls.

## Dependencies
- React 18.3.1
- React DOM 18.2.0
- React Router DOM 7.1.5
- Axios 1.7.4
- Laravel Mix 6.0.49
- @preact/signals-react 3.0.1

## Development Tools
- Vite 6.1.1
- @vitejs/plugin-react 1.3.2
- Laravel Vite Plugin 1.2.0
- Node 20.18.1
- PostCSS 8.4.47
- Autoprefixer 10.4.20
- Sass 1.56.1
- Concurrently 9.0.1

## Getting started
After cloning the repository
- Install Node.js (if not installed) from https://nodejs.org/
- npm install
- composer install
- create .env file with .env.example and add your public and privat Kraken API keys.
- php artisan migrate (set up database)
- npm run dev-all
    - this command starts the vite, laravel and node servers all at once.
      You can also start them seperately - npm run dev
                                         - php artisan serve
                                         - pm2 start kraken-ws-service/index.js --name kraken-ws
- php artisan queue:work (start the job worker; Handles queued tasks in Laravel)                                        
- View the logs for the websocket service: - cd kraken-ws-service 
                                           - pm2 logs kraken-ws

## author
Van de Velde Kara
vandeveldekara4@gmail.com
https://cv-kara-van-de-velde.vercel.app/
www.linkedin.com/in/karavandevelde/