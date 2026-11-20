<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Port;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Tag;
use App\Models\User;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers\BundleItemsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\PortsRelationManager;
use App\Filament\Resources\ReservationResource\Pages\EditReservation;
use App\Filament\Resources\ReservationResource\RelationManagers\ReservationItemsRelationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Renders every admin (Filament) page so a framework upgrade that breaks a form,
 * table or relation manager fails here instead of in production.
 */
class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_every_admin_page_renders(): void
    {
        $this->actingAs(User::factory()->create());

        $category = Category::factory()->create();
        $port = Port::factory()->create();
        $tag = Tag::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $set = Product::factory()->create(['category_id' => $category->id]);
        $set->bundleItems()->attach($product->id, ['amount' => 2]);
        $reservation = Reservation::factory()->create([
            'phone' => '1', 'start_date' => '2027-07-06 10:00', 'end_date' => '2027-07-07 10:00',
            'is_active' => true, 'is_paid' => false,
        ]);
        $item = ReservationItem::create(['reservation_id' => $reservation->id, 'product_id' => $product->id, 'amount' => 1]);

        $pages = [
            '/admin',
            '/admin/categories', '/admin/categories/create', "/admin/categories/{$category->id}/edit",
            '/admin/ports', '/admin/ports/create', "/admin/ports/{$port->id}/edit",
            '/admin/tags', '/admin/tags/create', "/admin/tags/{$tag->id}/edit",
            '/admin/products', '/admin/products/create', "/admin/products/{$product->id}/edit", "/admin/products/{$set->id}/edit",
            '/admin/reservations', '/admin/reservations/create', "/admin/reservations/{$reservation->id}", "/admin/reservations/{$reservation->id}/edit",
            '/admin/reservation-items', '/admin/reservation-items/create', "/admin/reservation-items/{$item->id}/edit",
        ];

        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_relation_managers_render_with_their_actions(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $set = Product::factory()->create();
        $set->bundleItems()->attach($product->id, ['amount' => 2]);
        $reservation = Reservation::factory()->create([
            'phone' => '1', 'start_date' => '2027-07-06 10:00', 'end_date' => '2027-07-07 10:00',
            'is_active' => true, 'is_paid' => false,
        ]);
        ReservationItem::create(['reservation_id' => $reservation->id, 'product_id' => $product->id, 'amount' => 1]);

        Livewire::test(BundleItemsRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditProduct::class])
            ->assertSuccessful()
            ->assertSee($product->name);

        Livewire::test(PortsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->assertSuccessful();

        Livewire::test(ReservationItemsRelationManager::class, ['ownerRecord' => $reservation, 'pageClass' => EditReservation::class])
            ->assertSuccessful()
            ->assertSee($product->name);
    }

    public function test_bundle_items_can_be_added_and_edited(): void
    {
        $this->actingAs(User::factory()->create());

        $a = Product::factory()->create();
        $b = Product::factory()->create();
        $set = Product::factory()->create();
        $set->bundleItems()->attach($a->id, ['amount' => 2]);

        $rm = Livewire::test(BundleItemsRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditProduct::class]);

        $rm->callAction(TestAction::make('edit')->table($set->bundleItems()->first()), ['amount' => 5])
            ->assertHasNoErrors();
        $this->assertSame(5, (int) $set->bundleItems()->where('products.id', $a->id)->first()->pivot->amount);

        $rm->callAction(TestAction::make('attach')->table(), ['product_id' => $b->id, 'amount' => 3])
            ->assertHasNoErrors();
        $this->assertSame(3, (int) $set->bundleItems()->where('products.id', $b->id)->first()->pivot->amount);
    }
}
