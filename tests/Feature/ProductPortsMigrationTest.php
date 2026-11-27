<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Port;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPortsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_11_27_000000_add_product_ports.php');
    }

    private function ports(string $product): array
    {
        return Product::where('name', $product)->firstOrFail()->ports
            ->mapWithKeys(fn (Port $p) => ["{$p->name} {$p->gender->value}" => (int) $p->pivot->amount])
            ->all();
    }

    public function test_it_attaches_ports_to_known_gear_and_is_idempotent(): void
    {
        $zvuk = Category::factory()->create(['name' => 'Zvuk', 'slug' => 'zvuk']);
        foreach (['Shure SM58', 'Allen&Heath ZEDi-8', 'XLR - XLR (Sommer cable + Neutrik) - 5m', 'Amprack FP'] as $name) {
            Product::factory()->create(['name' => $name, 'category_id' => $zvuk->id]);
        }

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(['XLR 3pin male' => 1], $this->ports('Shure SM58'));
        $this->assertEqualsCanonicalizing(
            ['XLR 3pin female' => 2, 'Jack 6,3 mm female' => 7, 'XLR 3pin male' => 2, 'USB-B female' => 1, 'IEC C6 (napájení, trojlístek) male' => 1],
            $this->ports('Allen&Heath ZEDi-8'),
        );
        $this->assertEqualsCanonicalizing(
            ['XLR 3pin male' => 1, 'XLR 3pin female' => 1],
            $this->ports('XLR - XLR (Sommer cable + Neutrik) - 5m'),
        );
        $this->assertSame([], $this->ports('Amprack FP')); // custom rack: entered by hand
        $this->assertSame(1, Port::where('name', 'XLR 3pin')->where('gender', 'male')->count());
    }

    public function test_it_does_nothing_without_the_catalog_and_down_cleans_up(): void
    {
        $this->migration()->up();
        $this->assertSame(0, Port::count());

        $zvuk = Category::factory()->create(['name' => 'Zvuk', 'slug' => 'zvuk']);
        Product::factory()->create(['name' => 'Shure SM58', 'category_id' => $zvuk->id]);
        $this->migration()->up();
        $this->assertGreaterThan(0, Port::count());

        $this->migration()->down();
        $this->assertSame([], $this->ports('Shure SM58'));
        $this->assertSame(0, Port::count());
    }
}
