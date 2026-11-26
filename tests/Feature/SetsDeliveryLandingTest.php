<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SetsDeliveryLandingTest extends TestCase
{
    use RefreshDatabase;

    private const CATALOG = [
        'Nexo PS15R2 (2000W, 1x15"+1x3")' => [1500, 2],
        'Nexo PS10R2' => [1000, 2],
        'Subwoofer MT18' => [800, 4],
        'Amprack FP' => [1000, 1],
        'Gravity SP 5322 B' => [50, 2],
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 10m' => [10, 4],
        'dB Technologies Opera 15' => [500, 2],
        'Konig&Meyer 21450 black' => [50, 2],
        'Fractal Lights 9x10W' => [100, 8],
        'Gravity LS TBTV 28' => [100, 2],
        'XLR - XLR (Sommer cable + Neutrik) - 10m' => [10, 10],
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 10m' => [10, 6],
        'dB Technologies Opera 12' => [500, 6],
        'Sennheiser XSW 2-835 B' => [300, 1],
        'Allen&Heath ZED6' => [200, 1],
        'JBL Eon Compact One' => [500, 2],
        'AKG WMS40 Mini Dual' => [250, 1],
        'Allen&Heath CQ-20B (rack)' => [1200, 1],
        'Varytec LED Studio 150 2900K' => [250, 2],
    ];

    private function seedCatalog(): void
    {
        $zvuk = Category::factory()->create(['name' => 'Zvuk', 'slug' => 'zvuk']);
        foreach (self::CATALOG as $name => [$price, $stock]) {
            Product::factory()->create(['name' => $name, 'price_per_day' => $price, 'stock' => $stock, 'category_id' => $zvuk->id]);
        }
    }

    private function runSetsMigration(): void
    {
        (require database_path('migrations/2026_11_21_000000_create_event_sets.php'))->up();
    }

    public function test_sets_migration_builds_sets_from_catalog_and_is_idempotent(): void
    {
        $this->seedCatalog();
        $this->runSetsMigration();
        $this->runSetsMigration();

        $band = Product::where('slug', 'set-svatba-s-kapelou')->firstOrFail();
        $this->assertSame(4990.0, (float) $band->price_per_day);
        $this->assertSame(1, (int) $band->stock); // limited by the single amp rack
        $this->assertEquals(5, $band->bundleItems()->count());
        $this->assertSame(3, Product::whereIn('slug', ['set-svatba-s-kapelou', 'set-dj-party', 'set-proslovy-a-hudba'])->count());

        // The detail page lists what is inside and the saving.
        $this->get(route('products.show', $band))
            ->assertOk()
            ->assertSee('Co je v setu')
            ->assertSee('Subwoofer MT18')
            ->assertSee('ušetříte');
    }

    public function test_sets_migration_skips_without_catalog(): void
    {
        $this->runSetsMigration();
        $this->assertSame(0, Product::count());
    }

    public function test_list_products_shows_set_components(): void
    {
        $this->seedCatalog();
        $this->runSetsMigration();

        $res = $this->postJson('/mcp/reservations', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => 'list-products', 'arguments' => ['search' => 'Set DJ']],
        ])->assertOk();

        $set = json_decode($res->json('result.content.0.text'), true)['products'][0];
        $this->assertTrue($set['is_bundle']);
        $this->assertStringEndsWith('/products/set-dj-party', $set['url']);
        $this->assertContains(['product_id' => Product::where('name', 'dB Technologies Opera 15')->value('id'), 'name' => 'dB Technologies Opera 15', 'quantity' => 2], $set['components']);
    }

    public function test_mcp_delivery_zone_adds_its_price(): void
    {
        Mail::fake();
        RateLimiter::clear('mcp-create-reservation:127.0.0.1');
        $p = Product::factory()->create(['stock' => 4, 'price_per_day' => 500]);

        $call = fn (string $zone) => json_decode($this->postJson('/mcp/reservations', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => 'create-reservation', 'arguments' => [
                'name' => 'Jana', 'phone' => '1',
                'start_date' => '2027-07-06 10:00', 'end_date' => '2027-07-07 10:00',
                'delivery_zone' => $zone,
                'items' => [['product_id' => $p->id, 'quantity' => 1]],
            ]],
        ])->assertJsonPath('result.isError', false)->json('result.content.0.text'), true);

        $olomouc = $call('olomouc');
        $this->assertSame(1100.0, (float) $olomouc['price']);
        $this->assertSame(500.0, (float) $olomouc['rental_price']);
        $this->assertSame(600.0, (float) $olomouc['delivery_price']);
        $this->assertTrue($olomouc['delivery']);

        $far = $call('dal');
        $this->assertSame(500.0, (float) $far['price']);
        $this->assertNull($far['delivery_price']);
        $this->assertTrue($far['delivery']);

        $pickup = $call('pickup');
        $this->assertSame(500.0, (float) $pickup['price']);
        $this->assertFalse($pickup['delivery']);
    }

    public function test_cart_checkout_includes_delivery_zone_price(): void
    {
        Mail::fake();
        $p = Product::factory()->create(['stock' => 4, 'price_per_day' => 500]);
        $this->withSession(['cart' => [
            'start_date' => '2027-07-06 10:00:00', 'end_date' => '2027-07-07 10:00:00',
            'items' => [$p->id => 1], 'bundles' => [],
        ]]);

        $this->post(route('cart.checkout'), ['name' => 'Jana', 'phone' => '1', 'delivery_zone' => 'okoli'])
            ->assertSessionHasNoErrors();

        $r = Reservation::firstOrFail();
        $this->assertSame(1500.0, (float) $r->price);
        $this->assertSame(1000.0, (float) $r->delivery_price);
        $this->assertSame('okoli', $r->delivery_zone);
    }

    public function test_invalid_delivery_zone_is_rejected(): void
    {
        $p = Product::factory()->create(['stock' => 4]);
        $this->withSession(['cart' => [
            'start_date' => '2027-07-06 10:00:00', 'end_date' => '2027-07-07 10:00:00',
            'items' => [$p->id => 1], 'bundles' => [],
        ]]);

        $this->post(route('cart.checkout'), ['name' => 'Jana', 'phone' => '1', 'delivery_zone' => 'mars'])
            ->assertSessionHasErrors('delivery_zone');
    }

    public function test_landing_pages_render_with_sets_and_are_in_sitemap(): void
    {
        $this->seedCatalog();
        $this->runSetsMigration();

        $this->get('/ozvuceni-svatby-olomouc')
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Ozvučení svatby v Olomouci a okolí')
            ->assertSee('Set Svatba s kapelou')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('id="poptavka"', false);

        $this->get('/ozvuceni-kapely')->assertOk()->assertSee('Nexo PS15R2');
        $this->get('/pronajem-reproduktoru-olomouc')->assertOk()->assertSee('Set DJ párty');
        $this->get('/neexistujici-stranka')->assertNotFound();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('ozvuceni-svatby-olomouc'))
            ->assertSee(url('pronajem-reproduktoru-olomouc'));
    }

    public function test_ceremony_set_reuses_the_old_bundle_and_swaps_its_contents(): void
    {
        $this->seedCatalog();
        $old = Product::factory()->create(['name' => 'Svatební balíček – obřad', 'slug' => 'svatebmi-balicek-obrad', 'price_per_day' => 1500, 'stock' => 1]);
        $old->bundleItems()->attach(Product::where('name', 'dB Technologies Opera 15')->value('id'), ['amount' => 2]);

        $migration = require database_path('migrations/2026_11_22_000000_rebuild_ceremony_set.php');
        $migration->up();
        $migration->up();

        $set = $old->fresh();
        $this->assertSame('set-svatebni-obrad', $set->slug);
        $this->assertSame(1190.0, (float) $set->price_per_day);
        $this->assertEqualsCanonicalizing(
            ['JBL Eon Compact One' => 2, 'Konig&Meyer 21450 black' => 2, 'AKG WMS40 Mini Dual' => 1],
            $set->bundleItems->mapWithKeys(fn ($c) => [$c->name => (int) $c->pivot->amount])->all(),
        );
        $this->assertSame(1, Product::where('name', 'like', '%obřad%')->count());

        $this->runSetsMigration();
        $this->get('/ozvuceni-svatby-olomouc')->assertOk()->assertSee('Set Svatební obřad');
    }

    public function test_service_request_is_stored_and_not_priced(): void
    {
        Mail::fake();
        $p = Product::factory()->create(['stock' => 4, 'price_per_day' => 500]);
        $this->withSession(['cart' => [
            'start_date' => '2027-07-06 10:00:00', 'end_date' => '2027-07-07 10:00:00',
            'items' => [$p->id => 1], 'bundles' => [],
        ]]);

        $this->post(route('cart.checkout'), ['name' => 'Jana', 'phone' => '1', 'delivery_zone' => 'olomouc', 'service' => '1'])
            ->assertSessionHasNoErrors();

        $r = Reservation::firstOrFail();
        $this->assertTrue($r->service);
        $this->assertSame(1100.0, (float) $r->price); // rental + transport only
        Mail::assertSent(\App\Mail\ReservationCreated::class, fn ($m) => str_contains($m->render(), 'Instalace, zapojení nebo obsluha'));
    }

    public function test_band_set_switches_to_ps10_and_is_cheaper_than_its_parts(): void
    {
        $this->seedCatalog();
        $this->runSetsMigration();
        (require database_path('migrations/2026_11_22_000002_band_set_uses_ps10.php'))->up();

        $band = Product::where('slug', 'set-svatba-s-kapelou')->firstOrFail();
        $names = $band->bundleItems->pluck('pivot.amount', 'name')->map(fn ($a) => (int) $a)->all();
        $this->assertSame(2, $names['Nexo PS10R2']);
        $this->assertArrayNotHasKey('Nexo PS15R2 (2000W, 1x15"+1x3")', $names);
        $this->assertSame(4290.0, (float) $band->price_per_day);
        $parts = $band->bundleItems->sum(fn ($c) => (float) $c->price_per_day * (int) $c->pivot->amount);
        $this->assertGreaterThan((float) $band->price_per_day, $parts);
    }

    public function test_band_set_gets_mixer_mic_and_lights_and_stays_cheaper_than_its_parts(): void
    {
        $this->seedCatalog();
        Product::where('name', 'Subwoofer MT18')->update(['price_per_day' => 500]);
        $this->runSetsMigration();
        (require database_path('migrations/2026_11_22_000002_band_set_uses_ps10.php'))->up();
        $migration = require database_path('migrations/2026_11_26_000002_band_set_adds_mixer_mic_and_lights.php');
        $migration->up();
        $migration->up();

        $band = Product::where('slug', 'set-svatba-s-kapelou')->firstOrFail();
        $parts = $band->bundleItems->pluck('pivot.amount', 'name')->map(fn ($a) => (int) $a)->all();
        $this->assertSame(1, $parts['Allen&Heath CQ-20B (rack)']);
        $this->assertSame(1, $parts['Sennheiser XSW 2-835 B']);
        $this->assertSame(4, $parts['Fractal Lights 9x10W']);
        $this->assertSame(2, $parts['Gravity LS TBTV 28']);
        $this->assertSame(2, $parts['Nexo PS10R2']);
        $this->assertSame(5690.0, (float) $band->price_per_day);
        $this->assertSame(1, (int) $band->stock);
        $this->assertSame('set-svatba-s-kapelou', $band->slug);

        $sum = $band->bundleItems->sum(fn ($c) => (float) $c->price_per_day * (int) $c->pivot->amount);
        $this->assertSame(6280.0, $sum);
        $this->get(route('products.show', $band))->assertOk()->assertSee('ušetříte 590');
    }

    public function test_zed6_is_renamed_to_zedi8_and_joins_the_dj_set(): void
    {
        $this->seedCatalog();
        $this->runSetsMigration();
        $migration = require database_path('migrations/2026_11_26_000005_zedi8_replaces_zed6_and_dj_set_update.php');
        $migration->up();
        $migration->up();

        $mixer = Product::where('slug', 'allenheath-zedi-8')->firstOrFail();
        $this->assertSame('Allen&Heath ZEDi-8', $mixer->name);
        $this->assertFalse(Product::where('name', 'Allen&Heath ZED6')->exists());
        $this->get('/products/allenheath-zed6')->assertStatus(301)->assertRedirect('/products/allenheath-zedi-8');

        $dj = Product::where('slug', 'set-dj-party')->firstOrFail();
        $parts = $dj->bundleItems->pluck('pivot.amount', 'name')->map(fn ($a) => (int) $a)->all();
        $this->assertSame(1, $parts['Allen&Heath ZEDi-8']);
        $this->assertSame(2, $parts['dB Technologies Opera 15']);
        $this->assertSame(2, $parts['Fractal Lights 9x10W']);
        $this->assertArrayNotHasKey('Gravity LS TBTV 28', $parts);
        // 1 290 + 200 for the mixer − 100 for the light stand.
        $this->assertSame(1390.0, (float) $dj->price_per_day);
        $this->assertSame('Set DJ párty (2 bedny, mixpult, světla)', $dj->name);
        $this->assertStringContainsString('na stativech, mixpult Allen&Heath ZEDi-8 a dvě LED světla, včetně kabelů', $dj->description);
        $this->assertStringNotContainsString('na stativu', $dj->description);
        $this->assertStringContainsString('DJ připojí kontrolér do mixpultu', $dj->description);

        // The speeches set keeps the same mixer under its new name.
        $speeches = Product::where('slug', 'set-proslovy-a-hudba')->firstOrFail();
        $this->assertContains('Allen&Heath ZEDi-8', $speeches->bundleItems->pluck('name')->all());
    }

    public function test_band_set_gets_varytec_spots_and_six_fractals_and_stays_590_below_its_parts(): void
    {
        $this->seedCatalog();
        Product::where('name', 'Subwoofer MT18')->update(['price_per_day' => 500]);
        $this->runSetsMigration();
        (require database_path('migrations/2026_11_22_000002_band_set_uses_ps10.php'))->up();
        (require database_path('migrations/2026_11_26_000002_band_set_adds_mixer_mic_and_lights.php'))->up();
        $migration = require database_path('migrations/2026_11_26_000004_band_set_gets_more_lights.php');
        $migration->up();
        $migration->up();

        $band = Product::where('slug', 'set-svatba-s-kapelou')->firstOrFail();
        $parts = $band->bundleItems->pluck('pivot.amount', 'name')->map(fn ($a) => (int) $a)->all();
        $this->assertSame(2, $parts['Varytec LED Studio 150 2900K']);
        $this->assertSame(6, $parts['Fractal Lights 9x10W']);
        $this->assertSame(2, $parts['Gravity LS TBTV 28']);
        $this->assertSame(1, $parts['Allen&Heath CQ-20B (rack)']);
        $this->assertCount(12, $parts);
        $this->assertSame(6390.0, (float) $band->price_per_day);
        $this->assertSame(1, (int) $band->stock);
        $this->assertStringContainsString('na proslovy, šest LED světel Fractal a dva reflektory Varytec', $band->description);
        $this->assertStringNotContainsString('čtyři LED', $band->description);

        $sum = $band->bundleItems->sum(fn ($c) => (float) $c->price_per_day * (int) $c->pivot->amount);
        $this->assertSame(6980.0, $sum);
        $this->get(route('products.show', $band))->assertOk()->assertSee('ušetříte 590');
    }
}
