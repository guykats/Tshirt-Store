<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Minimalist streetwear carrying quiet Jewish cultural signal — no ornamentation, just the mark itself.">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <title>{{ config('app.name', 'Tshirt-Store') }}</title>

        @php
            // $og is set per-request by App\Http\Controllers\SpaController — a product
            // detail URL (/products/{slug}) gets that product's real name/description/
            // image; every other path (including an unknown/invalid product slug) falls
            // back to this same site-level default so a bad share link never 500s or
            // shows a blank preview. Still server-rendered rather than set from JS: most
            // social crawlers (Facebook, WhatsApp, X, etc.) don't execute JavaScript —
            // they only ever see this initial HTML, so a per-page update made client-side
            // (see ProductDetail.jsx's useDocumentMeta/JSON-LD) would never reach a shared
            // link's preview card.
            $og ??= [
                'title' => 'Jewish Identity, Understated — '.config('app.name', 'Tshirt-Store'),
                'description' => 'Minimalist streetwear carrying quiet Jewish cultural signal — no ornamentation, just the mark itself.',
                'image' => rtrim((string) config('app.url'), '/').'/og-image.png',
                'url' => rtrim((string) config('app.url'), '/'),
                'siteName' => config('app.name', 'Tshirt-Store'),
            ];
        @endphp
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $og['siteName'] }}">
        <meta property="og:title" content="{{ $og['title'] }}">
        <meta property="og:description" content="{{ $og['description'] }}">
        <meta property="og:image" content="{{ $og['image'] }}">
        <meta property="og:url" content="{{ $og['url'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $og['title'] }}">
        <meta name="twitter:description" content="{{ $og['description'] }}">
        <meta name="twitter:image" content="{{ $og['image'] }}">
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
