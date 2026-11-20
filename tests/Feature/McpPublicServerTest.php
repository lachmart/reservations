<?php

namespace Tests\Feature;

use App\Mail\ReservationCreated;
use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class McpPublicServerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('mcp-create-reservation:127.0.0.1');
    }

    private function rpc(string $method, array $params = [], string $server = 'reservations'): TestResponse
    {
        return $this->postJson("/mcp/{$server}", [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);
    }

    private function callTool(string $name, array $arguments): TestResponse
    {
        return $this->rpc('tools/call', ['name' => $name, 'arguments' => $arguments]);
    }

    /** Decoded JSON payload of a successful tool result. */
    private function payload(TestResponse $response): array
    {
        $response->assertOk()->assertJsonPath('result.isError', false);

        return json_decode($response->json('result.content.0.text'), true);
    }

    private function errorText(TestResponse $response): string
    {
        $response->assertOk()->assertJsonPath('result.isError', true);

        return (string) $response->json('result.content.0.text');
    }

    private function booking(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jana Nováková',
            'phone' => '+420 777 123 456',
            'start_date' => '2027-07-06 10:00',
            'end_date' => '2027-07-07 10:00',
            'event_place' => 'Olomouc, Flora',
            'event_type' => 'Svatba',
            'delivery' => true,
        ], $overrides);
    }

    public function test_create_reservation_stores_items_and_computes_price(): void
    {
        $top = Product::factory()->create(['name' => 'Nexo PS15R2', 'stock' => 4, 'price_per_day' => 1000]);
        $sub = Product::factory()->create(['name' => 'Subwoofer MT18', 'stock' => 2, 'price_per_day' => 1200]);

        $data = $this->payload($this->callTool('create-reservation', $this->booking([
            'items' => [
                ['product_id' => $top->id, 'quantity' => 2],
                ['product_id' => $sub->id, 'quantity' => 2],
            ],
        ])));

        // Czech wall-clock time, labelled with the Prague offset (summer: +02:00).
        $this->assertSame('2027-07-06T10:00:00+02:00', $data['start_date']);

        // 1 day * (2 * 1000 + 2 * 1200)
        $this->assertSame(4400.0, (float) $data['price']);
        $this->assertSame(1, $data['days']);
        $this->assertCount(2, $data['items']);

        $reservation = Reservation::with('reservationItems')->findOrFail($data['id']);
        $this->assertEqualsCanonicalizing(
            [[$top->id, 2], [$sub->id, 2]],
            $reservation->reservationItems->map(fn ($i) => [$i->product_id, (int) $i->amount])->all(),
        );
        $this->assertSame('Olomouc, Flora', $reservation->event_place);
        $this->assertSame('Svatba', $reservation->event_type);
        $this->assertTrue($reservation->delivery);
        $this->assertSame('mcp', $reservation->source);
        // Same initial state as a web reservation.
        $this->assertTrue($reservation->is_active);
        $this->assertFalse($reservation->is_paid);
        // Phone only: no customer e-mail, but the owner is told.
        $this->assertSame('', $reservation->email);
    }

    public function test_bundles_are_expanded_and_priced_at_the_bundle_rate(): void
    {
        $top = Product::factory()->create(['stock' => 4, 'price_per_day' => 1000]);
        $sub = Product::factory()->create(['stock' => 2, 'price_per_day' => 1200]);
        $set = Product::factory()->create(['name' => 'Svatba s kapelou', 'stock' => 0, 'price_per_day' => 3900]);
        $set->bundleItems()->attach($top->id, ['amount' => 2]);
        $set->bundleItems()->attach($sub->id, ['amount' => 2]);

        $data = $this->payload($this->callTool('create-reservation', $this->booking([
            'end_date' => '2027-07-08 10:00', // 2 days
            'items' => [['product_id' => $set->id, 'quantity' => 1]],
        ])));

        $this->assertSame(7800.0, (float) $data['price']);
        $amounts = Reservation::findOrFail($data['id'])->reservationItems->pluck('amount', 'product_id')->map(fn ($a) => (int) $a)->all();
        $this->assertEquals([$top->id => 2, $sub->id => 2], $amounts);
    }

    public function test_insufficient_availability_saves_nothing(): void
    {
        $top = Product::factory()->create(['stock' => 4, 'price_per_day' => 1000]);
        $sub = Product::factory()->create(['name' => 'Subwoofer MT18', 'stock' => 1, 'price_per_day' => 1200]);

        $error = $this->errorText($this->callTool('create-reservation', $this->booking([
            'items' => [
                ['product_id' => $top->id, 'quantity' => 2],
                ['product_id' => $sub->id, 'quantity' => 2],
            ],
        ])));

        $this->assertStringContainsString('Subwoofer MT18', $error);
        $this->assertStringContainsString('requested 2, available 1', $error);
        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('reservation_items', 0);
    }

    public function test_existing_reservations_reduce_availability(): void
    {
        $top = Product::factory()->create(['stock' => 2, 'price_per_day' => 1000]);
        $args = $this->booking(['items' => [['product_id' => $top->id, 'quantity' => 2]]]);

        $this->payload($this->callTool('create-reservation', $args));
        $this->errorText($this->callTool('create-reservation', $args));

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_public_client_cannot_set_price_paid_or_active(): void
    {
        $top = Product::factory()->create(['stock' => 4, 'price_per_day' => 1000]);

        foreach (['price' => 0, 'is_paid' => true, 'is_active' => false] as $field => $value) {
            $error = $this->errorText($this->callTool('create-reservation', $this->booking([
                'items' => [['product_id' => $top->id, 'quantity' => 1]],
                $field => $value,
            ])));
            $this->assertStringContainsString($field, $error);
        }

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_public_schema_has_items_and_no_admin_fields(): void
    {
        $tools = collect($this->rpc('tools/list')->assertOk()->json('result.tools'))->keyBy('name');

        $this->assertEqualsCanonicalizing(
            ['list-products', 'check-product-availability', 'create-reservation'],
            $tools->keys()->all(),
        );

        $schema = $tools['create-reservation']['inputSchema'];
        $this->assertArrayHasKey('items', $schema['properties']);
        $this->assertContains('items', $schema['required']);
        $this->assertNotContains('email', $schema['required']);
        foreach (['price', 'is_paid', 'is_active'] as $field) {
            $this->assertArrayNotHasKey($field, $schema['properties']);
        }
        $this->assertStringNotContainsString('add-reservation-item', $tools['create-reservation']['description']);
    }

    public function test_phone_or_email_is_required(): void
    {
        $top = Product::factory()->create(['stock' => 4]);

        $this->errorText($this->callTool('create-reservation', $this->booking([
            'phone' => null,
            'items' => [['product_id' => $top->id, 'quantity' => 1]],
        ])));

        $this->payload($this->callTool('create-reservation', $this->booking([
            'phone' => null,
            'email' => 'jana@example.com',
            'items' => [['product_id' => $top->id, 'quantity' => 1]],
        ])));

        Mail::assertSent(ReservationCreated::class, fn ($m) => $m->hasTo('jana@example.com'));
    }

    public function test_items_are_required(): void
    {
        $this->errorText($this->callTool('create-reservation', $this->booking(['items' => []])));
        $this->errorText($this->callTool('create-reservation', $this->booking()));

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_owner_is_notified(): void
    {
        config(['contact.owner_notify' => ['owner@example.com']]);
        $top = Product::factory()->create(['stock' => 4]);

        $this->payload($this->callTool('create-reservation', $this->booking([
            'items' => [['product_id' => $top->id, 'quantity' => 1]],
        ])));

        Mail::assertSent(ReservationCreated::class, fn ($m) => $m->hasTo('owner@example.com'));
    }

    public function test_reservations_are_rate_limited_per_client(): void
    {
        $top = Product::factory()->create(['stock' => 100]);
        $args = $this->booking(['items' => [['product_id' => $top->id, 'quantity' => 1]]]);

        for ($i = 0; $i < 5; $i++) {
            $this->payload($this->callTool('create-reservation', $args));
        }

        $this->assertStringContainsString('Too many', $this->errorText($this->callTool('create-reservation', $args)));
        $this->assertDatabaseCount('reservations', 5);
    }

    public function test_unknown_tool_is_a_json_rpc_error(): void
    {
        $this->callTool('add-reservation-item', ['reservation_id' => 1])
            ->assertOk()
            ->assertJsonPath('error.code', -32602)
            ->assertJsonMissingPath('result');
    }

    public function test_unknown_protocol_version_is_negotiated_down(): void
    {
        $this->rpc('initialize', [
            'protocolVersion' => '2099-01-01',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'test', 'version' => '1'],
        ])
            ->assertOk()
            ->assertJsonPath('result.protocolVersion', '2025-06-18')
            ->assertJsonMissingPath('error');
    }

    public function test_admin_can_create_with_items_and_override_price(): void
    {
        config(['mcp.token' => 'secret']);
        $top = Product::factory()->create(['stock' => 4, 'price_per_day' => 1000]);

        $response = $this->withToken('secret')->postJson('/mcp/admin', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => 'create-reservation', 'arguments' => $this->booking([
                'items' => [['product_id' => $top->id, 'quantity' => 1]],
                'price' => 500,
                'is_paid' => true,
            ])],
        ]);

        $data = $this->payload($response);
        $reservation = Reservation::findOrFail($data['id']);
        $this->assertSame(500.0, (float) $reservation->price);
        $this->assertTrue($reservation->is_paid);
        $this->assertSame('admin', $reservation->source);
        $this->assertCount(1, $reservation->reservationItems);
        Mail::assertNothingSent();
    }

    public function test_server_card_lists_public_tools(): void
    {
        foreach (['/mcp/server-card.json', '/.well-known/mcp/server-card.json'] as $path) {
            $this->getJson($path)
                ->assertOk()
                ->assertJsonPath('remotes.0.url', url('/mcp/reservations'))
                ->assertJsonCount(3, 'tools')
                ->assertHeader('Cache-Control', 'max-age=3600, public');
        }
    }
}
