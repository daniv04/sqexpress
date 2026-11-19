<?php

namespace Tests\Feature;

use App\Filament\Resources\ShippingMethodResource\Pages\EditShippingMethod;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShippingMethodAlternativeAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_the_alternative_address_when_the_method_has_one(): void
    {
        $address = "shipping mark: ABC-123\n地址：深圳市宝安区福永街道";

        $method = ShippingMethod::factory()->create([
            'active' => true,
            'pais' => 'China',
            'direccion_alternativa' => $address,
        ]);

        $this->assertSame($address, $method->fresh()->direccion_alternativa);

        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Dirección Alternativa / Alternative Address')
            ->assertSee('shipping mark: ABC-123')
            ->assertSee('地址：深圳市宝安区福永街道');
    }

    public function test_admin_can_save_an_alternative_address_on_any_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create(['pais' => 'Estados Unidos']);

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(EditShippingMethod::class, ['record' => $method->getRouteKey()])
            ->fillForm(['direccion_alternativa' => "Bodega 2\nMiami, FL"])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame("Bodega 2\nMiami, FL", $method->fresh()->direccion_alternativa);
    }

    public function test_profile_hides_the_alternative_address_block_when_the_method_has_none(): void
    {
        $method = ShippingMethod::factory()->create([
            'active' => true,
            'pais' => 'China',
            'direccion_alternativa' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/profile')
            ->assertOk()
            ->assertSee($method->name)
            ->assertDontSee('Dirección Alternativa / Alternative Address');
    }
}
