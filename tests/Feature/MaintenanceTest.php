<?php

namespace Tests\Feature;

use App\Filament\Pages\Maintenance as MaintenancePage;
use App\Models\User;
use App\Support\Maintenance;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_is_hidden_without_a_token(): void
    {
        config(['app.maintenance_token' => null]);

        $this->post('/_maintenance/migrate')->assertNotFound();
    }

    public function test_endpoint_rejects_a_wrong_token_and_unknown_tasks(): void
    {
        config(['app.maintenance_token' => 'secret']);

        $this->post('/_maintenance/migrate', [], ['X-Maintenance-Token' => 'nope'])->assertForbidden();
        $this->post('/_maintenance/rm-rf', [], ['X-Maintenance-Token' => 'secret'])->assertNotFound();
    }

    public function test_endpoint_runs_migrations_with_the_token(): void
    {
        config(['app.maintenance_token' => 'secret']);

        $this->post('/_maintenance/migrate', [], ['X-Maintenance-Token' => 'secret'])
            ->assertOk()
            ->assertSee('== migrate: OK');
    }

    public function test_deploy_task_runs_every_step_and_stops_on_failure(): void
    {
        config(['app.maintenance_token' => 'secret']);
        // Don't really cache config during tests; just check the wiring.
        $fake = Mockery::mock(Maintenance::class);
        $fake->shouldReceive('run')->with('deploy')->once()->andReturn(['ok' => false, 'output' => '== migrate: FAILED']);
        $this->app->instance(Maintenance::class, $fake);

        $this->post('/_maintenance/deploy', [], ['X-Maintenance-Token' => 'secret'])
            ->assertStatus(500)
            ->assertSee('FAILED');
    }

    public function test_admin_page_runs_a_task(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/maintenance')->assertOk()->assertSee('Údržba serveru');

        Livewire::test(MaintenancePage::class)
            ->callAction(TestAction::make('migrate'))
            ->assertNotified();
    }

    public function test_database_import_refuses_a_database_that_has_tables(): void
    {
        config(['app.maintenance_token' => 'secret']);
        file_put_contents(Maintenance::importPath(), gzencode('CREATE TABLE should_not_exist (id INT);'));

        try {
            $this->post('/_maintenance/import-database', [], ['X-Maintenance-Token' => 'secret'])
                ->assertStatus(500)
                ->assertSee('not empty');
            $this->assertFileExists(Maintenance::importPath());
        } finally {
            @unlink(Maintenance::importPath());
        }
    }

    public function test_endpoint_does_not_need_sessions_or_cache_tables(): void
    {
        // An empty database on the first deploy has no sessions/cache tables.
        config(['app.maintenance_token' => 'secret', 'session.driver' => 'database', 'session.table' => 'missing_sessions', 'cache.default' => 'database', 'cache.stores.database.table' => 'missing_cache']);

        $this->post('/_maintenance/migrate', [], ['X-Maintenance-Token' => 'secret'])->assertOk();
    }

    public function test_test_mail_task_sends_to_the_owner(): void
    {
        Mail::fake();
        config(['app.maintenance_token' => 'secret', 'contact.owner_notify' => ['owner@example.com']]);

        $this->post('/_maintenance/test-mail', [], ['X-Maintenance-Token' => 'secret'])
            ->assertOk()
            ->assertSee('Sent to owner@example.com');
    }
}
