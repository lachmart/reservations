<?php

namespace Tests\Feature;

use App\Filament\Resources\ReservationResource\Pages\EditReservation;
use App\Filament\Resources\ReservationResource\Pages\ListReservations;
use App\Filament\Resources\ReservationResource\Pages\ViewReservation;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use App\Support\RentalSheet;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RentalSheetTest extends TestCase
{
    use RefreshDatabase;

    private function reservation(array $attributes = []): Reservation
    {
        return Reservation::factory()->create($attributes + [
            'name' => 'Jana Nováková',
            'email' => 'jana@example.com',
            'phone' => '777 123 456',
            'start_date' => '2027-07-06 10:00',
            'end_date' => '2027-07-07 10:00',
            'price' => 6390,
            'note' => '',
            'event_place' => 'Olomouc, Flora',
            'is_active' => true,
            'is_paid' => false,
        ]);
    }

    /** A set of 2 speakers and 4 cables, booked twice, next to a plain mixer. */
    private function bookedSetAndMixer(): Reservation
    {
        $mixer = Product::factory()->create(['name' => 'Allen&Heath ZEDi-8', 'price_per_day' => 300, 'stock' => 1]);
        $speaker = Product::factory()->create(['name' => 'Nexo PS15R2', 'price_per_day' => 900, 'stock' => 4]);
        $cable = Product::factory()->create(['name' => 'SPEAKON 10m', 'price_per_day' => 30, 'stock' => 10]);
        $set = Product::factory()->create(['name' => 'Set Velký zvuk', 'price_per_day' => 2500, 'stock' => 2]);
        $set->bundleItems()->attach([$speaker->id => ['amount' => 2], $cable->id => ['amount' => 4]]);

        $reservation = $this->reservation();
        $reservation->reservationItems()->create(['product_id' => $mixer->id, 'amount' => 1]);
        $reservation->reservationItems()->create(['product_id' => $set->id, 'amount' => 2]);

        return $reservation;
    }

    public function test_a_set_is_broken_down_into_its_pieces(): void
    {
        $sheet = new RentalSheet($this->bookedSetAndMixer());

        $this->assertSame([
            ['kind' => 'item', 'name' => 'Allen&Heath ZEDi-8', 'amount' => 1],
            ['kind' => 'set', 'name' => 'Set Velký zvuk', 'amount' => 2],
            ['kind' => 'part', 'name' => 'Nexo PS15R2', 'amount' => 4],
            ['kind' => 'part', 'name' => 'SPEAKON 10m', 'amount' => 8],
        ], $sheet->rows());
    }

    public function test_extras_from_the_warehouse_are_listed(): void
    {
        $sheet = new RentalSheet($this->reservation(['extra_items' => [
            ['name' => 'Prodlužka 10 m', 'amount' => 2],
            ['name' => '  ', 'amount' => 1],            // an empty row left in the form
            ['name' => 'Gaffa páska', 'amount' => null], // no amount means one
        ]]));

        $this->assertSame([
            ['name' => 'Prodlužka 10 m', 'amount' => 2],
            ['name' => 'Gaffa páska', 'amount' => 1],
        ], $sheet->extras());
    }

    public function test_the_sheet_has_the_customer_checkboxes_and_blank_rows(): void
    {
        $reservation = $this->bookedSetAndMixer();
        $reservation->update(['extra_items' => [['name' => 'Prodlužka 10 m', 'amount' => 2]]]);

        $html = view('pdf.rental-sheet', ['sheet' => new RentalSheet($reservation->fresh())])->render();

        $this->assertStringContainsString("Výpůjční list č. {$reservation->id}", $html);
        $this->assertStringContainsString('Jana Nováková', $html);
        $this->assertStringContainsString('777 123 456', $html);
        $this->assertStringContainsString('Olomouc, Flora', $html);
        $this->assertStringContainsString('6. 7. 2027 10:00', $html);
        $this->assertStringContainsString('Vydáno', $html);
        $this->assertStringContainsString('Vráceno', $html);
        $this->assertStringContainsString('Nexo PS15R2', $html);
        $this->assertStringContainsString('Přidáno na skladě', $html);
        $this->assertStringContainsString('Prodlužka 10 m', $html);
        $this->assertSame(RentalSheet::BLANK_ROWS, substr_count($html, 'class="blank"'));
        $this->assertStringNotContainsString('6390', $html); // no prices on the sheet
    }

    public function test_admin_downloads_the_rental_sheet_as_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $reservation = $this->bookedSetAndMixer();

        $response = $this->get(route('filament.admin.rental-sheet', $reservation))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString("vypujcni-list-{$reservation->id}.pdf", $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_guests_cannot_open_a_rental_sheet(): void
    {
        $reservation = $this->reservation();

        $this->get(route('filament.admin.rental-sheet', $reservation))->assertRedirect('/admin/login');
    }

    public function test_admin_saves_extra_items_on_a_reservation(): void
    {
        $this->actingAs(User::factory()->create());
        $reservation = $this->reservation();
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
            ->fillForm(['extra_items' => [['name' => 'Prodlužka 10 m', 'amount' => 2]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();
        $this->assertSame([['name' => 'Prodlužka 10 m', 'amount' => 2]], $reservation->fresh()->extra_items);
    }

    public function test_admin_saves_a_reservation_but_not_one_ending_before_it_starts(): void
    {
        // The date rules once compared against "end_date" outside the form's
        // data, so every save failed with "must be a date before end date".
        $this->actingAs(User::factory()->create());
        $reservation = $this->reservation();

        Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
            ->fillForm(['end_date' => '2027-07-08 10:00'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('2027-07-08 10:00', $reservation->fresh()->end_date->format('Y-m-d H:i'));

        Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
            ->fillForm(['end_date' => '2027-07-05 10:00'])
            ->call('save')
            ->assertHasFormErrors(['start_date', 'end_date']);
    }

    public function test_reservation_pages_offer_the_rental_sheet(): void
    {
        $this->actingAs(User::factory()->create());
        $reservation = $this->reservation();
        $url = route('filament.admin.rental-sheet', $reservation);

        Livewire::test(ViewReservation::class, ['record' => $reservation->getKey()])
            ->assertActionHasUrl('rentalSheet', $url)
            ->assertActionShouldOpenUrlInNewTab('rentalSheet');
        Livewire::test(EditReservation::class, ['record' => $reservation->getKey()])
            ->assertActionHasUrl('rentalSheet', $url);
        Livewire::test(ListReservations::class)
            ->assertActionHasUrl(TestAction::make('rentalSheet')->table($reservation), $url);
    }
}
