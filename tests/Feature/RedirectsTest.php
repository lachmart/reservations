<?php

namespace Tests\Feature;

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
}
