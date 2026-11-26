<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InviteAdminUserTest extends TestCase
{
    use RefreshDatabase;

    private function invite(array $arguments)
    {
        config(['mcp.token' => 'secret']);

        return $this->withToken('secret')->postJson('/mcp/admin', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => 'invite-admin-user', 'arguments' => $arguments],
        ])->assertOk();
    }

    public function test_invite_creates_the_account_and_emails_a_set_password_link(): void
    {
        Notification::fake();

        $response = $this->invite(['name' => 'Radim Test', 'email' => 'radim@example.test']);
        $this->assertFalse((bool) $response->json('result.isError'), (string) $response->json('result.content.0.text'));
        $text = (string) $response->json('result.content.0.text');
        $this->assertStringNotContainsStringIgnoringCase('password":', $text);

        $user = User::where('email', 'radim@example.test')->sole();
        $this->assertSame('Radim Test', $user->name);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) {
            return str_contains($n->url, '/admin/password-reset/reset');
        });

        // The login page offers "Forgot password?" for when the link expires.
        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_set_password_mail_goes_out_right_away_without_a_queue_worker(): void
    {
        // Production queues to the database and runs no worker.
        config(['queue.default' => 'database']);
        Event::fake([NotificationSent::class]);

        $this->invite(['name' => 'Radim Test', 'email' => 'radim@example.test']);

        Event::assertDispatched(NotificationSent::class, fn ($e) => $e->notifiable->email === 'radim@example.test');
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_inviting_an_existing_account_only_sends_a_fresh_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['name' => 'Radim', 'email' => 'radim@example.test']);
        $hash = $user->password;

        $response = $this->invite(['name' => 'Someone Else', 'email' => 'radim@example.test']);

        $this->assertFalse((bool) $response->json('result.isError'));
        $this->assertFalse(json_decode($response->json('result.content.0.text'), true)['created']);
        $this->assertSame(['Radim', $hash], [$user->fresh()->name, $user->fresh()->password]);
        $this->assertSame(1, User::count());
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
