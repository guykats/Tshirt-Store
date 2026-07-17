<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Minimalist streetwear carrying quiet Jewish cultural signal — no ornamentation, just the mark itself.">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <title>{{ config('app.name', 'Tshirt-Store') }}</title>

        {{-- Static, since this is a client-rendered SPA and most social crawlers (Facebook,
             WhatsApp, X, etc.) don't execute JavaScript — they only ever see this initial
             HTML, so per-page dynamic OG tags via JS would never actually reach a shared
             link's preview card. Every shared URL gets this same site-level preview. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'Tshirt-Store') }}">
        <meta property="og:title" content="Jewish Identity, Understated — {{ config('app.name', 'Tshirt-Store') }}">
        <meta property="og:description" content="Minimalist streetwear carrying quiet Jewish cultural signal — no ornamentation, just the mark itself.">
        <meta property="og:image" content="{{ config('app.url') }}/og-image.png">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Jewish Identity, Understated — {{ config('app.name', 'Tshirt-Store') }}">
        <meta name="twitter:description" content="Minimalist streetwear carrying quiet Jewish cultural signal — no ornamentation, just the mark itself.">
        <meta name="twitter:image" content="{{ config('app.url') }}/og-image.png">
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
