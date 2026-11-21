<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_products_redirect_permanently(): void
    {
        foreach (config('redirects.products') as $old => $target) {
            $this->get('/products/'.$old)->assertStatus(301)->assertRedirect($target);
        }
    }

    public function test_unknown_product_still_404s(): void
    {
        $this->get('/products/neexistujici-produkt-xyz')->assertNotFound();
    }

    public function test_deleted_product_redirects_to_its_category(): void
    {
        $cat = Category::factory()->create(['slug' => 'svetla']);
        $p = Product::factory()->create(['name' => 'Stara mlhovka', 'category_id' => $cat->id]);
        $slug = $p->slug;
        $p->delete();

        $this->get('/products/'.$slug)->assertStatus(301)->assertRedirect('/products?category=svetla');
    }

    public function test_renamed_product_redirects_old_slug_to_new_url(): void
    {
        $p = Product::factory()->create(['name' => 'Bedna A']);
        $old = $p->slug;
        $p->update(['slug' => 'bedna-a-nova']);

        $this->get('/products/'.$old)->assertStatus(301)->assertRedirect('/products/bedna-a-nova');
        $this->get('/products/bedna-a-nova')->assertOk();
    }

    public function test_a_new_product_reusing_an_old_slug_is_served_normally(): void
    {
        $p = Product::factory()->create(['name' => 'Repro X', 'stock' => 1, 'price_per_day' => 100]);
        $slug = $p->slug;
        $p->delete();
        Product::factory()->create(['name' => 'Repro X', 'slug' => $slug, 'stock' => 1, 'price_per_day' => 100]);

        $this->get('/products/'.$slug)->assertOk();
        $this->assertDatabaseMissing('product_redirects', ['old_slug' => $slug]);
    }
}
