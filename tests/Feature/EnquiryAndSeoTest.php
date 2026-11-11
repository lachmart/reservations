<?php

namespace Tests\Feature;

use App\Mail\EnquiryReceived;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnquiryAndSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_enquiry_is_emailed_to_the_business(): void
    {
        Mail::fake();

        $this->from(route('home'))->post(route('enquiry.store'), [
            'name' => 'Jana Nováková',
            'phone' => '+420 777 111 222',
            'event_date' => '2027-06-12',
            'event_type' => 'Svatba',
            'message' => 'Svatba pro 120 hostů.',
        ])->assertRedirect(route('home'))->assertSessionHas('enquiry_sent');

        Mail::assertSent(EnquiryReceived::class, fn ($m) => $m->hasTo(config('contact.owner_notify')[0])
            && $m->enquiry['name'] === 'Jana Nováková');
    }

    public function test_enquiry_reply_to_is_the_customer(): void
    {
        $mail = (new EnquiryReceived([
            'name' => 'Petr', 'email' => 'petr@example.com', 'message' => 'Ples',
        ]))->build();

        $this->assertTrue($mail->hasReplyTo('petr@example.com'));
    }

    public function test_enquiry_needs_phone_or_email(): void
    {
        Mail::fake();

        $this->from(route('home'))->post(route('enquiry.store'), [
            'name' => 'Bez kontaktu',
            'message' => 'Ahoj',
        ])->assertSessionHasErrors(['phone', 'email']);

        Mail::assertNothingSent();
    }

    public function test_honeypot_silently_drops_bots(): void
    {
        Mail::fake();

        $this->from(route('home'))->post(route('enquiry.store'), [
            'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'spam', 'website' => 'http://spam',
        ])->assertRedirect(route('home'));

        Mail::assertNothingSent();
    }

    public function test_homepage_targets_rental_searches(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>'.e(config('seo.home.title')).'</title>', false)
            ->assertSee('Pronájem ozvučení a&nbsp;světel', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('id="poptavka"', false);
    }

    public function test_category_page_uses_search_copy(): void
    {
        Category::factory()->create(['slug' => 'zvuk', 'name' => 'Zvuk']);

        $this->get(route('products.index', ['category' => 'zvuk']))
            ->assertOk()
            ->assertSee(e(config('seo.categories.zvuk.title')), false)
            ->assertSee('Pronájem ozvučení</h1>', false)
            ->assertDontSee('Pronájem Zvuk');
    }
}
