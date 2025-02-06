<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @viteReactRefresh 
        @vite(['resources/js/app.jsx'], ['resources/css/app.css'] )


        <title>Laravel en react</title>

    </head>
    <body>
    <div id="app"></div>
        
    </body>
</html>
