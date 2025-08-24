<?php

it('can open cart page', function () {
    $this->get(route('cart.index'))->assertStatus(200);
});

it('can add product to cart and see it on cart page', function () {
    $product = \App\Models\Product::factory()->create([
        'stock' => 10,
        'price_per_day' => 100,
    ]);

    $this->post(route('cart.add'), [
        'product_id' => $product->id,
        'amount' => 1,
    ])->assertRedirect(route('cart.index'));

    $this->get(route('cart.index'))
        ->assertStatus(200)
        ->assertSee($product->name);
});
