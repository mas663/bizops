<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelLoginSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_owner_can_actually_log_in_via_the_login_form(): void
    {
        $this->seed();

        $user = User::first();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => env('OWNER_PASSWORD'),
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);

        // A fresh request re-resolves the user from the session via the
        // Eloquent user provider (retrieveById). This is exactly the path
        // that previously caused infinite recursion when the User model
        // carried the BelongsToOrganization global scope.
        $dashboard = $this->get('/admin');
        $dashboard->assertOk();
        $dashboard->assertSee('Dashboard');
    }
}
