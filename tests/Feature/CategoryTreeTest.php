<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    private Category $zvuk;

    private Category $mikrofony;

    private Category $mixpulty;

    private Category $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zvuk = Category::create(['name' => 'Zvuk', 'slug' => 'zvuk', 'position' => 1]);
        $this->stage = Category::create(['name' => 'Stage', 'slug' => 'stage', 'position' => 2]);
        $this->mikrofony = Category::create(['name' => 'Mikrofony', 'slug' => 'mikrofony', 'parent_id' => $this->zvuk->id, 'position' => 2]);
        $this->mixpulty = Category::create(['name' => 'Mixpulty', 'slug' => 'mixpulty', 'parent_id' => $this->zvuk->id, 'position' => 1]);
    }

    private function product(string $name, Category $category, array $attrs = []): Product
    {
        return Product::factory()->create($attrs + ['name' => $name, 'category_id' => $category->id, 'price_per_day' => 100, 'stock' => 2]);
    }

    public function test_section_lists_its_subcategories_products_and_subcategory_only_its_own(): void
    {
        $this->product('Shure SM58', $this->mikrofony);
        $this->product('XR18 mixer', $this->mixpulty);
        $this->product('Truss tower', $this->stage);

        $this->get(route('products.index', ['category' => 'zvuk']))
            ->assertOk()
            ->assertSee('Shure SM58')
            ->assertSee('XR18 mixer')
            ->assertDontSee('Truss tower')
            // subcategory chips of the chosen section, in menu order
            ->assertSeeInOrder(['Mixpulty', 'Mikrofony']);

        $this->get(route('products.index', ['category' => 'mikrofony']))
            ->assertOk()
            ->assertSee('Shure SM58')
            ->assertDontSee('XR18 mixer')
            ->assertSee('aria-current="page">Mikrofony</a>', false);

        // AJAX search inside a section still covers the subcategories.
        $this->get(route('products.index', ['category' => 'zvuk', 'q' => 'XR18']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('XR18 mixer')
            ->assertDontSee('Shure SM58');
    }

    public function test_header_menu_shows_sections_and_non_empty_subcategories(): void
    {
        $this->product('Shure SM58', $this->mikrofony);
        $this->product('Truss tower', $this->stage);
        // Mixpulty has nothing rentable, so it is left out.
        $this->product('Broken mixer', $this->mixpulty, ['stock' => 0]);
        $empty = Category::create(['name' => 'Prázdná sekce', 'slug' => 'prazdna']);

        $html = $this->get('/')->assertOk()->getContent();
        $menu = substr($html, strpos($html, 'id="mega-menu"'));
        $menu = substr($menu, 0, strpos($menu, '</script>'));

        $this->assertStringContainsString('/products?category=zvuk', $menu);
        $this->assertStringContainsString('/products?category=mikrofony', $menu);
        $this->assertStringContainsString('/products?category=stage', $menu);
        $this->assertStringNotContainsString('/products?category=mixpulty', $menu);
        $this->assertStringNotContainsString($empty->name, $menu);
    }

    public function test_home_tiles_are_sections_counting_their_subcategories(): void
    {
        $this->product('Shure SM58', $this->mikrofony);
        $this->product('Shure SM57', $this->mikrofony);
        $this->product('XR18 mixer', $this->mixpulty);

        $response = $this->get('/')->assertOk();
        $tiles = $this->between($response, 'Co si u&nbsp;nás půjčíte', 'Oblíbená technika');

        $this->assertStringContainsString('3 položek', $tiles);
        $this->assertStringNotContainsString('category=mikrofony', $tiles);
    }

    public function test_sitemap_has_sections_and_subcategories(): void
    {
        $this->product('Shure SM58', $this->mikrofony);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('products.index', ['category' => 'zvuk']), false)
            ->assertSee(route('products.index', ['category' => 'mikrofony']), false)
            ->assertDontSee(route('products.index', ['category' => 'mixpulty']), false);
    }

    public function test_product_page_breadcrumbs_include_section_and_json_ld_has_context(): void
    {
        $mic = $this->product('Shure SM58', $this->mikrofony);

        $html = $this->get(route('products.show', $mic))->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">\s*(.*?)\s*</script>#s', $html, $m);
        $blocks = array_map(fn ($json) => json_decode($json, true), $m[1]);
        $this->assertNotEmpty($blocks);
        foreach ($blocks as $block) {
            // Blade must not compile "@context" as its @context directive.
            $this->assertSame('https://schema.org', $block['@context'] ?? null);
        }

        $crumbs = collect($blocks)->firstWhere('@type', 'BreadcrumbList')['itemListElement'];
        $this->assertSame(['Domů', 'Technika', 'Zvuk', 'Mikrofony', 'Shure SM58'], array_column($crumbs, 'name'));
        $this->assertSame([1, 2, 3, 4, 5], array_column($crumbs, 'position'));
        $this->assertSame('Zvuk › Mikrofony', collect($blocks)->firstWhere('@type', 'Product')['category']);
    }

    public function test_placeholder_kind_follows_the_section(): void
    {
        $sub = Category::create(['name' => 'Reproboxy', 'slug' => 'reproboxy', 'parent_id' => $this->zvuk->id]);
        $box = $this->product('Opera 12', $sub);
        $laptop = $this->product('ThinkPad', Category::create(['name' => 'Počítače', 'slug' => 'pocitace']));
        $set = $this->product('Set Svatební obřad (2× JBL + mikrofony)', Category::create(['name' => 'Sety', 'slug' => 'sety']));

        $this->assertSame('speaker', $box->placeholderKind());
        $this->assertSame('laptop', $laptop->placeholderKind());
        $this->assertSame('package', $set->placeholderKind());
    }

    public function test_sets_become_the_first_section_with_the_sets_listed_in_the_menu(): void
    {
        Category::query()->delete();
        $zvuk = Category::create(['name' => 'Zvuk', 'slug' => 'zvuk', 'position' => 1]);
        $distro = Category::create(['name' => 'Distro', 'slug' => 'distro', 'position' => 6]);
        $sety = Category::create(['name' => 'Sety', 'slug' => 'sety', 'parent_id' => $zvuk->id, 'position' => 1]);
        $mics = Category::create(['name' => 'Mikrofony', 'slug' => 'mikrofony', 'parent_id' => $zvuk->id, 'position' => 7]);
        $this->product('Shure SM58', $mics);
        $this->product('Powerbox', $distro);
        $this->product('Set Svatba s kapelou (zvuk, mixpult, světla)', $sety, ['slug' => 'set-svatba-s-kapelou', 'price_per_day' => 6390]);
        $dj = $this->product('Set DJ párty (2 bedny + světla)', $sety, ['slug' => 'set-dj-party', 'price_per_day' => 1290]);

        $migration = require database_path('migrations/2026_11_26_000003_sets_become_a_section.php');
        $migration->up();
        $migration->up();

        $this->assertNull($sety->fresh()->parent_id);
        $this->assertSame(['Sety', 'Zvuk', 'Distro'], Category::roots()->ordered()->pluck('name')->all());

        $response = $this->get('/')->assertOk();
        $html = $response->getContent();
        $menu = substr($html, strpos($html, 'id="mega-menu"'));
        $menu = substr($menu, 0, strpos($menu, '</script>'));

        // Sety comes first and lists the sets (cheapest first) with short names and prices.
        $this->assertStringContainsString(route('products.show', $dj), $menu);
        $order = array_map(fn ($needle) => strpos($menu, $needle), ['category=sety', 'DJ párty', '1 290 Kč', 'Svatba s kapelou', '6 390 Kč', 'category=zvuk', 'category=mikrofony']);
        $this->assertNotContains(false, $order);
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order);
        $this->assertStringNotContainsString('Set DJ', $menu);
        // … and not again in the "Další" column.
        $this->assertSame(1, substr_count($menu, 'category=sety'));

        // On the homepage Sety is the first tile, wide because there are three sections.
        $tiles = $this->between($response, 'Co si u&nbsp;nás půjčíte', 'Oblíbená technika');
        $this->assertLessThan(strpos($tiles, 'category=zvuk'), strpos($tiles, 'category=sety'));
        $this->assertMatchesRegularExpression('#category=sety"\s+class="[^"]*col-span-2#', $tiles);

        $migration->down();
        $this->assertSame($zvuk->id, $sety->fresh()->parent_id);
    }

    public function test_deleting_a_section_promotes_its_subcategories(): void
    {
        $this->zvuk->delete();

        $this->assertNull($this->mikrofony->fresh()->parent_id);
    }

    public function test_subcategory_migration_moves_products_within_their_section_and_is_idempotent(): void
    {
        Category::query()->delete();
        $zvuk = Category::create(['name' => 'Zvuk', 'slug' => 'zvuk']);
        $svetla = Category::create(['name' => 'Světla', 'slug' => 'svetla']);
        $distro = Category::create(['name' => 'Distro', 'slug' => 'distro']);
        $sm58 = $this->product('Shure SM58', $zvuk, ['slug' => 'shure-sm58']);
        $xlr = $this->product('XLR 10m', $zvuk, ['slug' => 'xlr-xlr-sommer-cable-neutrik-10m']);
        $set = $this->product('Set DJ', $zvuk, ['slug' => 'set-dj-party']);
        $odd = $this->product('Something new', $zvuk, ['slug' => 'something-new']);
        $dmx = $this->product('DMX 5m', $svetla, ['slug' => 'dmx-kabel-klotz-delka-5m']);
        // Matches a Zvuk pattern by slug but lives in another section: stays put.
        $foreign = $this->product('Shure in distro', $distro, ['slug' => 'shure-odd']);

        $migration = require database_path('migrations/2026_11_26_000001_create_subcategories.php');
        $migration->up();
        $migration->up();

        $this->assertSame('mikrofony', $sm58->fresh()->category->slug);
        $this->assertSame('kabely', $xlr->fresh()->category->slug);
        $this->assertSame('sety', $set->fresh()->category->slug);
        $this->assertSame('zvuk', $odd->fresh()->category->slug);
        $this->assertSame('dmx-kabely', $dmx->fresh()->category->slug);
        $this->assertSame('distro', $foreign->fresh()->category->slug);

        $this->assertSame(1, Category::where('slug', 'mikrofony')->count());
        $this->assertSame($zvuk->id, Category::where('slug', 'mikrofony')->value('parent_id'));
        $this->assertSame([1, 2, 6], [$zvuk->fresh()->position, $svetla->fresh()->position, $distro->fresh()->position]);
        $this->assertSame(
            ['Sety', 'Reproboxy pasivní', 'Reproboxy aktivní', 'Subwoofery', 'Zesilovače', 'Mixpulty', 'Mikrofony', 'Stativy', 'DI-boxy', 'Kabely'],
            $zvuk->children()->pluck('name')->all(),
        );

        // The section page still shows everything.
        $this->get(route('products.index', ['category' => 'zvuk']))
            ->assertSee('Shure SM58')->assertSee('Something new')->assertSee('Set DJ');
    }

    public function test_subcategory_migration_skips_without_sections(): void
    {
        Category::query()->delete();

        (require database_path('migrations/2026_11_26_000001_create_subcategories.php'))->up();

        $this->assertSame(0, Category::count());
    }

    public function test_admin_mcp_manages_the_tree(): void
    {
        config(['mcp.token' => 'secret']);
        $this->product('Shure SM58', $this->mikrofony);
        $this->product('Loose zvuk item', $this->zvuk);

        $created = $this->admin('create-category', ['name' => 'Stativy', 'parent_id' => $this->zvuk->id]);
        $this->assertSame($this->zvuk->id, $created['parent_id']);
        $this->assertSame(3, $created['position']); // after Mixpulty (1) and Mikrofony (2)

        $this->assertStringContainsString('two levels', $this->adminError('create-category', ['name' => 'Deep', 'parent_id' => $this->mikrofony->id]));
        $this->assertStringContainsString('has subcategories', $this->adminError('update-category', ['id' => $this->zvuk->id, 'parent_id' => $this->stage->id]));
        $this->assertStringContainsString('has subcategories', $this->adminError('delete-category', ['id' => $this->zvuk->id]));

        $moved = $this->admin('update-category', ['id' => $this->mixpulty->id, 'parent_id' => 0, 'name' => 'Mixy', 'position' => 9]);
        $this->assertNull($moved['parent_id']);
        $this->assertSame('mixpulty', $moved['slug']);
        $this->assertSame('Mixy', $this->mixpulty->fresh()->name);

        $list = $this->admin('list-categories', []);
        $this->assertSame(['Zvuk', 'Mikrofony', 'Stativy', 'Stage', 'Mixy'], array_column($list['categories'], 'name'));

        $products = $this->admin('list-products', ['category_id' => $this->zvuk->id]);
        $this->assertEqualsCanonicalizing(['Shure SM58', 'Loose zvuk item'], array_column($products['products'], 'name'));
        $this->assertContains('Zvuk › Mikrofony', array_column($products['products'], 'category'));
    }

    private function admin(string $tool, array $arguments): array
    {
        $response = $this->callAdmin($tool, $arguments);
        $this->assertFalse((bool) $response->json('result.isError'), (string) $response->json('result.content.0.text'));

        return json_decode($response->json('result.content.0.text'), true);
    }

    private function adminError(string $tool, array $arguments): string
    {
        $response = $this->callAdmin($tool, $arguments);
        $this->assertTrue((bool) $response->json('result.isError'));

        return (string) $response->json('result.content.0.text');
    }

    private function callAdmin(string $tool, array $arguments): TestResponse
    {
        return $this->withToken('secret')->postJson('/mcp/admin', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => $arguments],
        ])->assertOk();
    }

    private function between(TestResponse $response, string $from, string $to): string
    {
        $html = $response->getContent();
        $start = strpos($html, $from);
        $this->assertNotFalse($start, "Missing: {$from}");

        return substr($html, $start, strpos($html, $to, $start) - $start);
    }
}
