<?php

namespace Tests\Feature;

use App\Mail\Transport\PhpMailTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Sends for real through PHP mail(). In DDEV, mail() goes to Mailpit, so the
 * message can be read back; elsewhere the test is skipped.
 */
class PhpMailTransportTest extends TestCase
{
    private const MAILPIT = 'http://127.0.0.1:8025/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        if (! str_contains((string) ini_get('sendmail_path'), 'mailpit')) {
            $this->markTestSkipped('mail() is not routed to Mailpit here.');
        }
        Http::preventStrayRequests(false);
        Http::delete(self::MAILPIT.'/messages');
    }

    public function test_mailer_resolves_to_the_php_mail_transport(): void
    {
        $this->assertInstanceOf(PhpMailTransport::class, Mail::mailer('phpmail')->getSymfonyTransport());
    }

    public function test_a_czech_message_with_bcc_arrives_intact(): void
    {
        config(['mail.default' => 'phpmail', 'mail.from.address' => 'info@marakarental.cz', 'mail.from.name' => 'Maraka rental']);

        Mail::raw('Dobrý den, žluťoučký kůň úpěl ďábelské ódy.', function ($m) {
            $m->to('zakaznik@example.com', 'Jana Nováková')
                ->bcc('skryta@example.com')
                ->subject('Potvrzení rezervace – Svatební obřad');
        });

        $messages = Http::get(self::MAILPIT.'/messages')->json('messages');
        $this->assertCount(1, $messages);
        $msg = Http::get(self::MAILPIT.'/message/'.$messages[0]['ID'])->json();

        $this->assertSame('Potvrzení rezervace – Svatební obřad', $msg['Subject']);
        $this->assertSame('zakaznik@example.com', $msg['To'][0]['Address']);
        $this->assertSame('info@marakarental.cz', $msg['From']['Address']);
        $this->assertStringContainsString('žluťoučký kůň', $msg['Text']);

        // The Bcc recipient is reached but not listed in To. (Stripping the Bcc
        // header itself is sendmail's job with -t; Mailpit keeps it for display.)
        $this->assertContains('skryta@example.com', array_column($msg['Bcc'] ?? [], 'Address'));
        $this->assertNotContains('skryta@example.com', array_column($msg['To'], 'Address'));
    }
}
