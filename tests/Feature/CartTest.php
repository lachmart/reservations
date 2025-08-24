<?php

namespace Tests\Feature;

use App\Mail\ReservationCreated;
use Illuminate\Support\Facades\Mail;

use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run migrations
        $this->artisan('migrate');
    }

    public function test_set_dates_stores_in_session_and_validates(): void
    {
        // valid
        $resp = $this->post(route('cart.setDates'), [
            'start_date' => '2025-09-01 10:00:00',
            'end_date' => '2025-09-03 09:00:00',
        ]);
        $resp->assertSessionHasNoErrors();
        $resp->assertSessionHas('cart.start_date', '2025-09-01 10:00:00');
        $resp->assertSessionHas('cart.end_date', '2025-09-03 09:00:00');

        // invalid (end before start)
        $resp2 = $this->post(route('cart.setDates'), [
            'start_date' => '2025-09-03 10:00:00',
            'end_date' => '2025-09-01 09:00:00',
        ]);
        $resp2->assertSessionHasErrors('end_date');
    }

    public function test_add_simple_product_via_json_respects_availability(): void
    {
        // Create simple product with stock
        $product = Product::factory()->create([
            'stock' => 5,
            'price_per_day' => 100,
        ]);

        // Set dates in session so availability is checked
        $this->withSession([
            'cart' => [
                'start_date' => '2025-09-01 10:00:00',
                'end_date' => '2025-09-02 10:00:00',
                'items' => [],
                'bundles' => [],
            ],
        ]);

        // Add within availability
        $resp = $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'amount' => 3,
        ]);
        $resp->assertOk()->assertJsonPath('status', 'ok')
            ->assertJsonPath('product_id', $product->id)
            ->assertJsonPath('product_count', 3);
        $this->assertEquals(3, session('cart.items.' . $product->id));

        // Exceed availability
        $resp2 = $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'amount' => 3,
        ]);
        $resp2->assertStatus(422);
        $resp2->assertJsonPath('status', 'error');
    }

    public function test_update_and_remove_keep_bundle_contribution(): void
    {
        // Create child product and bundle product
        $child = Product::factory()->create([
            'stock' => 10,
            'price_per_day' => 50,
        ]);
        $bundle = Product::factory()->create([
            'stock' => 999,
            'price_per_day' => 70,
        ]);
        // Attach child to bundle with amount 2
        \DB::table('bundle_items')->insert([
            'bundle_product_id' => $bundle->id,
            'product_id' => $child->id,
            'amount' => 2,
        ]);

        // Put one bundle and 1 standalone child in cart
        $this->withSession([
            'cart' => [
                'start_date' => '2025-09-01 10:00:00',
                'end_date' => '2025-09-02 10:00:00',
                'items' => [
                    $child->id => 3, // 2 from bundle + 1 standalone
                ],
                'bundles' => [
                    $bundle->id => 1,
                ],
            ],
        ]);

        // Update child standalone amount to 2 (should set total to bundle(2)+standalone(2)=4)
        $resp = $this->patch(route('cart.update', $child), [
            'amount' => 2,
        ]);
        $resp->assertSessionHasNoErrors();
        $this->assertEquals(4, session('cart.items.' . $child->id));

        // Remove child: should keep bundle contribution (2)
        $resp2 = $this->delete(route('cart.remove', $child));
        $resp2->assertSessionHasNoErrors();
        $this->assertEquals(2, session('cart.items.' . $child->id));

        // Remove bundle: child should be reduced accordingly (down to 0 and removed)
        $resp3 = $this->delete(route('cart.removeBundle', $bundle));
        $resp3->assertSessionHasNoErrors();
        $this->assertNull(session('cart.items.' . $child->id));
        $this->assertNull(session('cart.bundles.' . $bundle->id));
    }

    public function test_checkout_creates_reservation_and_clears_cart(): void
    {
        $product = Product::factory()->create([
            'stock' => 5,
            'price_per_day' => 100,
        ]);

        // 2 days duration (ceil rule in controller): from Sep 1 10:00 to Sep 3 09:00 -> 2 days
        $this->withSession([
            'cart' => [
                'start_date' => '2025-09-01 10:00:00',
                'end_date' => '2025-09-03 09:00:00',
                'items' => [
                    $product->id => 2,
                ],
                'bundles' => [],
            ],
        ]);

        Mail::fake();

        $resp = $this->post(route('cart.checkout'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123',
            'note' => 'test',
        ]);

        $resp->assertRedirect(route('cart.thankyou'));
        $this->assertDatabaseCount('reservations', 1);
        $this->assertDatabaseHas('reservation_items', [
            'product_id' => $product->id,
            'amount' => 2,
        ]);

        // Price = 100 * 2 (qty) * 2 (days) = 400.00
        $reservation = Reservation::first();
        $this->assertEquals(400.00, (float) $reservation->price);

        // Cart cleared
        $this->assertNull(session('cart'));

        // Email sent
        Mail::assertSent(ReservationCreated::class, function ($mail) {
            return $mail->hasTo('john@example.com');
        });
    }
}
