<?php

namespace Tests\Feature;

use App\Enums\PackageStatus;
use App\Events\PackagePrealerted;
use App\Events\PackageStatusChanged;
use App\Filament\Resources\PackageResource\Pages\CreatePackage;
use App\Models\Package;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCreateUnannouncedPackageTest extends TestCase
{
    use RefreshDatabase;

    private function formData(array $overrides = []): array
    {
        return array_merge([
            'tracking' => 'TRK-UNANNOUNCED',
            'shipping_method_id' => ShippingMethod::factory()->create(['active' => true])->id,
            'user_id' => User::factory()->create()->id,
            'description' => 'Paquete sin prealerta',
            'approx_value' => 20,
        ], $overrides);
    }

    public function test_package_arrived_at_office_is_created_as_received_in_business(): void
    {
        Event::fake([PackagePrealerted::class, PackageStatusChanged::class]);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreatePackage::class)
            ->fillForm($this->formData([
                'arrived_at_office' => true,
                'shelf_location' => 'A-3',
                'weight' => 2.5,
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $package = Package::where('tracking', 'TRK-UNANNOUNCED')->firstOrFail();

        $this->assertSame(PackageStatus::RECEIVED_IN_BUSINESS->value, $package->status);
        $this->assertSame('A-3', $package->shelf_location);
        $this->assertSame('2.50', (string) $package->weight);
        $this->assertSame(
            [null, PackageStatus::PREALERTED->value],
            $package->statusHistories()->orderBy('id')->pluck('from_status')->all(),
        );

        Event::assertNotDispatched(PackagePrealerted::class);
        Event::assertDispatched(PackageStatusChanged::class);
    }

    public function test_arrived_at_office_requires_shelf_location(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreatePackage::class)
            ->fillForm($this->formData(['arrived_at_office' => true]))
            ->call('create')
            ->assertHasFormErrors(['shelf_location' => 'required']);
    }

    public function test_regular_admin_prealert_stays_prealerted(): void
    {
        Event::fake([PackagePrealerted::class, PackageStatusChanged::class]);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreatePackage::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $package = Package::where('tracking', 'TRK-UNANNOUNCED')->firstOrFail();

        $this->assertSame(PackageStatus::PREALERTED->value, $package->status);
        Event::assertDispatched(PackagePrealerted::class);
        Event::assertNotDispatched(PackageStatusChanged::class);
    }
}
