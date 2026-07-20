<?php

namespace Tests\Feature\Mail;

use App\Mail\OrderConfirmationMail;
use App\Mail\OrderRefundedMail;
use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderEmailCurrencyLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrderForLocale(string $locale)
    {
        $user = User::factory()->create(['preferred_locale' => $locale]);

        $address = $user->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Test Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);

        $design = Design::create(['title' => 'Test Design', 'status' => 'approved']);

        $product = Product::create([
            'design_id' => $design->id,
            'name' => 'Test Tee',
            'slug' => 'test-tee-'.uniqid(),
            'base_price' => 19.99,
            'sku' => 'TT-'.uniqid(),
            'status' => 'active',
        ]);

        $variant = $product->variants()->create([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ]);

        $order = $user->orders()->create([
            'order_number' => 'ORD-CURRENCY-'.strtoupper($locale).'-'.uniqid(),
            'subtotal' => 19.99,
            'total_amount' => 19.99,
            'currency' => 'USD',
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 19.99,
            'subtotal' => 19.99,
        ]);

        return $order;
    }

    public function test_order_confirmation_email_formats_currency_per_locale(): void
    {
        $enOrder = $this->makeOrderForLocale('en');
        $heOrder = $this->makeOrderForLocale('he');

        $enHtml = (new OrderConfirmationMail($enOrder))->locale('en')->render();
        $heHtml = (new OrderConfirmationMail($heOrder))->locale('he')->render();

        // The old hand-rolled "{currency} {amount}" pattern should be gone entirely.
        $this->assertStringNotContainsString('USD 19.99', $enHtml);
        $this->assertStringNotContainsString('USD 19.99', $heHtml);

        // Hebrew rendering must use real locale-aware (RTL bidi-marked) currency
        // formatting, distinct from the English rendering, mirroring
        // resources/js/lib/formatPrice.js's Intl.NumberFormat behavior.
        $this->assertNotSame($enHtml, $heHtml);
        $this->assertStringContainsString("\u{200F}", $heHtml);
        $this->assertStringNotContainsString("\u{200F}", $enHtml);

        $this->assertStringContainsString('$19.99', $enHtml);
    }

    public function test_order_refunded_email_formats_currency_per_locale(): void
    {
        $enOrder = $this->makeOrderForLocale('en');
        $heOrder = $this->makeOrderForLocale('he');

        $enHtml = (new OrderRefundedMail($enOrder))->locale('en')->render();
        $heHtml = (new OrderRefundedMail($heOrder))->locale('he')->render();

        $this->assertStringNotContainsString('USD 19.99', $enHtml);
        $this->assertStringNotContainsString('USD 19.99', $heHtml);
        $this->assertNotSame($enHtml, $heHtml);
        $this->assertStringContainsString("\u{200F}", $heHtml);
        $this->assertStringNotContainsString("\u{200F}", $enHtml);
    }
}
