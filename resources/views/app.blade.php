<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Minimalist streetwear carrying quiet Jewish cultural signal — no ornamentation, just the mark itself.">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <title>{{ config('app.name', 'Tshirt-Store') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
