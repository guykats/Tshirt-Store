<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaController extends Controller
{
    /**
     * Catch-all entry point for the client-rendered SPA (see routes/web.php). Almost
     * every path just renders resources/views/app.blade.php with the site-level default
     * Open Graph tags — the one exception is a product detail URL
     * (/products/{slug}), where we look the product up server-side and pass its real
     * name/description/image into the view instead.
     *
     * This has to happen here, server-side, rather than in ProductDetail.jsx (which
     * already sets document.title/JSON-LD once the product loads): most social-media
     * crawlers (Facebook, WhatsApp, X, etc.) never execute JavaScript, so by the time a
     * client-side update would land, the crawler has already read and cached whatever
     * static HTML this route returned.
     */
    public function index(Request $request): View
    {
        $og = $this->defaultOg($request);

        $slug = $this->productSlugFrom($request->path());

        if ($slug !== null) {
            // status=active only, same rule as the public API's ProductController@show
            // and the sitemap — a draft/archived product shouldn't get a crawlable
            // preview any more than it should get an API response or a sitemap entry.
            $product = Product::query()
                ->where('slug', $slug)
                ->where('status', 'active')
                ->with('images')
                ->first();

            if ($product !== null) {
                $og = [
                    'title' => "{$product->name} — {$og['siteName']}",
                    'description' => (string) $product->description,
                    'image' => $this->productImage($product, $og['image']),
                    'url' => $request->fullUrl(),
                    'siteName' => $og['siteName'],
                ];
            }
        }

        return view('app', ['og' => $og]);
    }

    /**
     * Matches "/products/{slug}" (no trailing segments) against the same route-key
     * Product::getRouteKeyName() already uses for the public API and the sitemap, so
     * this stays correct if that ever changes from "slug" to something else.
     */
    private function productSlugFrom(string $path): ?string
    {
        $segments = explode('/', trim($path, '/'));

        if (count($segments) !== 2 || $segments[0] !== 'products' || $segments[1] === '') {
            return null;
        }

        return $segments[1];
    }

    /**
     * @return array{title: string, description: string, image: string, url: string, siteName: string}
     */
    private function defaultOg(Request $request): array
    {
        $siteName = config('app.name', 'Tshirt-Store');

        return [
            'title' => "Jewish Identity, Understated — {$siteName}",
            'description' => 'Minimalist streetwear carrying quiet Jewish cultural signal — no ornamentation, just the mark itself.',
            'image' => rtrim((string) config('app.url'), '/').'/og-image.png',
            'url' => rtrim((string) config('app.url'), '/'),
            'siteName' => $siteName,
        ];
    }

    /**
     * A product's gallery images (product_images.url) are free text: either a real
     * external image URL, or one of the DesignArt motif keywords (see the migration
     * comment on product_images.url and ProductGallery.jsx) used to render an inline
     * SVG mockup rather than a photo — this catalog has no real product photography
     * yet (same reasoning ProductDetail.jsx's JSON-LD already documents for its own
     * `image` field). Only use a gallery image here if it actually looks like a
     * fetchable URL; otherwise keep the brand default so crawlers never get handed a
     * bare motif keyword as an "image".
     */
    private function productImage(Product $product, string $default): string
    {
        $first = $product->images->first();

        if ($first !== null && preg_match('#^https?://#i', (string) $first->url) === 1) {
            return $first->url;
        }

        return $default;
    }
}
