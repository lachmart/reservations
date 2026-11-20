<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_page_links_to_its_sets_and_catalog_lists_sets_first(): void
    {
        $speaker = Product::factory()->create(['name' => 'Bedna X', 'stock' => 4, 'price_per_day' => 900]);
        Product::factory()->create(['name' => 'Drahý mixpult', 'stock' => 1, 'price_per_day' => 5000]);
        $set = Product::factory()->create(['name' => 'Set Oslava', 'stock' => 1, 'price_per_day' => 1500]);
        $set->bundleItems()->attach($speaker->id, ['amount' => 2]);

        $this->get(route('products.show', $speaker))
            ->assertOk()
            ->assertSee('Je součástí setu')
            ->assertSee(route('products.show', $set), false);

        // The set's own page doesn't claim to be part of a set.
        $this->get(route('products.show', $set))->assertOk()->assertDontSee('Je součástí');

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSeeInOrder(['Set Oslava', 'Drahý mixpult']);
    }

    public function test_cart_badge_counts_a_set_once_and_messages_are_czech(): void
    {
        $a = Product::factory()->create(['stock' => 10, 'price_per_day' => 100]);
        $b = Product::factory()->create(['stock' => 10, 'price_per_day' => 100]);
        $set = Product::factory()->create(['stock' => 1, 'price_per_day' => 150]);
        $set->bundleItems()->attach($a->id, ['amount' => 2]);
        $set->bundleItems()->attach($b->id, ['amount' => 3]);

        $this->postJson(route('cart.add'), ['product_id' => $set->id, 'amount' => 1])
            ->assertOk()
            ->assertJsonPath('cart_count', 1)
            ->assertJsonPath('message', 'Přidáno do košíku.');

        // One more unit of a component on its own: set (1) + 1 unit.
        $this->postJson(route('cart.add'), ['product_id' => $a->id, 'amount' => 1])
            ->assertJsonPath('cart_count', 2);

        $this->get(route('home'))->assertSee('aria-label="Košík (2 položky)"', false);
    }
}
