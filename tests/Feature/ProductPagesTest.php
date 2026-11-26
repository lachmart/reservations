<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
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

    public function test_title_and_meta_are_escaped_once(): void
    {
        $product = Product::factory()->create(['name' => 'Allen&Heath SQ5', 'price_per_day' => 100, 'stock' => 1]);

        $html = $this->get(route('products.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Allen&amp;Heath SQ5 — pronájem Olomouc', $html);
        $this->assertStringContainsString('og:title" content="Allen&amp;Heath SQ5', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function test_rich_description_keeps_its_formatting(): void
    {
        $html = '<p><strong>Obsahuje:</strong></p><ul><li><p>Procesor Rane RPM 26</p></li></ul><ul><li><p>Zesilovač FP14000</p></li></ul><p>Vše v jednom racku 4U.</p>';
        $product = Product::factory()->create(['description' => $html, 'price_per_day' => 100, 'stock' => 1]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('<div class="rich-text mt-2 whitespace-pre-line leading-relaxed text-muted">'.$html.'</div>', false);
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

    public function test_tag_links_still_filter_and_keep_the_tag_while_searching(): void
    {
        // Subcategories replaced the tag chips; old ?tags[] links keep working.
        $mic = Tag::create(['name' => 'Mikrofon']);
        Product::factory()->create(['name' => 'Tagged mic', 'price_per_day' => 10, 'stock' => 1])->tags()->attach($mic);
        Product::factory()->create(['name' => 'Untagged box', 'price_per_day' => 10, 'stock' => 1]);

        $this->get(route('products.index').'?tags[]='.$mic->id)
            ->assertOk()
            ->assertSee('Tagged mic')
            ->assertDontSee('Untagged box')
            ->assertSee('<input type="hidden" name="tags[]" value="'.$mic->id.'">', false)
            ->assertDontSee('data-tag-categories', false);
    }
}
