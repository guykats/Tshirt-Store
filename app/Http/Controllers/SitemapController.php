<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Render an XML sitemap covering the static marketing pages plus every
     * active (non-draft, non-archived) product's canonical URL.
     */
    public function index(): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        $urls = [
            [
                'loc' => $baseUrl.'/',
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl.'/about',
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
        ];

        Product::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->each(function (Product $product) use (&$urls, $baseUrl): void {
                $urls[] = [
                    'loc' => $baseUrl.'/products/'.$product->slug,
                    'lastmod' => optional($product->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
