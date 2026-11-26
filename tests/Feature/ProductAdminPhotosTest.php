<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAdminPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function photo(Product $product, string $collection): void
    {
        $product->addMedia(UploadedFile::fake()->image('photo.jpg', 120, 90))->toMediaCollection($collection);
    }

    public function test_admin_main_photo_field_shows_the_avatar_photo(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create();
        $this->photo($product, 'avatar');
        $this->photo($product, 'product-images');

        $page = Livewire::test(EditProduct::class, ['record' => $product->getKey()])->assertSuccessful();

        $this->assertCount(1, $page->get('data.avatar'));
        $this->assertCount(1, $page->get('data.gallery'));
    }

    public function test_admin_saves_a_product_without_a_status(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Allen&Heath ZEDi-10',
                'description' => '<p>Malý mixpult.</p>',
                'category_id' => $category->id,
                'price_per_day' => 250,
                'stock' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('', Product::where('name', 'Allen&Heath ZEDi-10')->value('status'));
    }

    public function test_admin_saves_a_product_with_an_empty_description(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $product = Product::factory()->create(['description' => '', 'category_id' => $category->id]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Bez popisu', 'description' => null, 'category_id' => $category->id, 'price_per_day' => 100, 'stock' => 1])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame('', Product::where('name', 'Bez popisu')->value('description'));

        // An existing product with an empty description saves too, and real
        // text still goes through.
        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm(['price_per_day' => 321])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(321.0, (float) $product->fresh()->price_per_day);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm(['description' => '<p>Malý mixpult se 2 mikrofonními vstupy.</p>'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertStringContainsString('Malý mixpult se 2 mikrofonními vstupy.', $product->fresh()->description);
    }

    public function test_migration_moves_admin_main_photos_to_avatar_and_leaves_replaced_ones(): void
    {
        $single = Product::factory()->create();
        $this->photo($single, 'default');
        $two = Product::factory()->create();
        $this->photo($two, 'default');
        $this->photo($two, 'default');
        $replaced = Product::factory()->create();
        $this->photo($replaced, 'default');
        $this->photo($replaced, 'avatar');
        $mainBefore = [$single->fresh()->mainImageUrl(), $replaced->fresh()->mainImageUrl()];

        $migration = require database_path('migrations/2026_11_26_000008_product_main_photos_move_to_avatar.php');
        $migration->up();
        $migration->up();

        $this->assertCount(1, $single->fresh()->getMedia('avatar'));
        $this->assertCount(0, $single->fresh()->getMedia('default'));
        $this->assertCount(1, $two->fresh()->getMedia('avatar'));
        $this->assertCount(1, $two->fresh()->getMedia('product-images'));
        $this->assertCount(1, $replaced->fresh()->getMedia('avatar'));
        $this->assertCount(1, $replaced->fresh()->getMedia('default'));
        // What the site shows doesn't change.
        $this->assertSame($mainBefore, [$single->fresh()->mainImageUrl(), $replaced->fresh()->mainImageUrl()]);
    }

    public function test_opera_12_shows_the_front_photo_first(): void
    {
        $product = Product::factory()->create(['slug' => 'db-technologies-opera-12']);
        foreach (['opera-12_back', 'opera-12_front'] as $name) {
            $product->addMedia(UploadedFile::fake()->image("{$name}.jpg", 60, 90))->usingName($name)->toMediaCollection('product-images');
        }
        $product->media()->update(['order_column' => null]); // as on production: no stored order

        (require database_path('migrations/2026_11_26_000009_opera_12_front_photo_first.php'))->up();

        $gallery = $product->fresh()->getMedia('product-images');
        $this->assertSame(['opera-12_front', 'opera-12_back'], $gallery->pluck('name')->all());
        $this->assertSame($gallery->first()->getUrl(), $product->fresh()->mainImageUrl());
    }

    public function test_admin_can_edit_a_description_with_a_list(): void
    {
        // The rich editor syncs its content as a nested JSON tree; a list item's
        // text sits at data.description.content.N.content.N.content.N.content.N.text,
        // deeper than Livewire's default 10-level limit (the production error).
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create([
            'description' => '<p>Obsahuje:</p><ul><li><p>Procesor Rane RPM 26</p></li></ul>',
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->set('data.description.content.1.content.0.content.0.content.0.text', 'Procesor Rane RPM 26z')
            ->assertHasNoErrors();
    }

    public function test_api_lists_the_main_photo_and_gallery(): void
    {
        $product = Product::factory()->create(['price_per_day' => 100, 'stock' => 1]);
        $this->photo($product, 'avatar');
        $this->photo($product, 'product-images');
        $this->photo($product, 'default'); // an old, replaced photo

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.images');
    }
}
