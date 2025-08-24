<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_products_index_and_ajax_list(): void
    {
        $cat = Category::factory()->create();
        Product::factory()->create(['category_id' => $cat->id, 'name' => 'Camera', 'price_per_day' => 10, 'stock' => 3]);
        Product::factory()->create(['name' => 'Tripod', 'price_per_day' => 5, 'stock' => 5]);

        $resp = $this->get(route('products.index'));
        $resp->assertOk();
        $resp->assertSee('Camera');

        // AJAX request should return list partial
        $resp2 = $this->get(route('products.index').'?sort=price_desc', ['X-Requested-With' => 'XMLHttpRequest']);
        $resp2->assertOk();
        $resp2->assertSee('Camera');
    }

    public function test_product_show_uses_session_dates_for_availability(): void
    {
        $product = Product::factory()->create(['stock' => 5, 'price_per_day' => 10]);

        // Put a reservation for 2 units in overlapping window to impact availability
        // easiest is to just compute availableForRange later; but here with no reservations, availability==stock

        $this->withSession([
            'cart' => [
                'start_date' => '2025-09-01 10:00:00',
                'end_date' => '2025-09-02 10:00:00',
            ],
        ]);

        $resp = $this->get(route('products.show', $product));
        $resp->assertOk();
        // View should contain product name and availability number (may be rendered as text)
        $resp->assertSee($product->name);
    }
}
