<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientHidePackageTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(): User
    {
        return User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
    }

    public function test_client_can_hide_a_delivered_package(): void
    {
        $client = $this->makeClient();
        $package = Package::factory()->create([
            'user_id' => $client->id,
            'status' => 'delivered',
        ]);

        $this->actingAs($client)
            ->patch(route('package.hide', $package))
            ->assertRedirect(route('mis-paquetes'));

        $this->assertNotNull($package->refresh()->hidden_at);
    }

    public function test_client_can_hide_a_canceled_package(): void
    {
        $client = $this->makeClient();
        $package = Package::factory()->create([
            'user_id' => $client->id,
            'status' => 'canceled',
        ]);

        $this->actingAs($client)->patch(route('package.hide', $package));

        $this->assertNotNull($package->refresh()->hidden_at);
    }

    public function test_client_cannot_hide_a_package_in_transit(): void
    {
        $client = $this->makeClient();
        $package = Package::factory()->create([
            'user_id' => $client->id,
            'status' => 'in_transit',
        ]);

        $this->actingAs($client)
            ->patch(route('package.hide', $package))
            ->assertForbidden();

        $this->assertNull($package->refresh()->hidden_at);
    }

    public function test_client_cannot_hide_another_users_package(): void
    {
        $client = $this->makeClient();
        $otherPackage = Package::factory()->create(['status' => 'delivered']);

        $this->actingAs($client)
            ->patch(route('package.hide', $otherPackage))
            ->assertForbidden();

        $this->assertNull($otherPackage->refresh()->hidden_at);
    }

    public function test_hidden_packages_are_excluded_from_mis_paquetes(): void
    {
        $client = $this->makeClient();
        $visible = Package::factory()->create([
            'user_id' => $client->id,
            'status' => 'delivered',
        ]);
        $hidden = Package::factory()->create([
            'user_id' => $client->id,
            'status' => 'delivered',
            'hidden_at' => now(),
        ]);

        $response = $this->actingAs($client)->get(route('mis-paquetes'));

        $response->assertOk()
            ->assertSee($visible->tracking)
            ->assertDontSee($hidden->tracking);
    }
}
