<?php

namespace Tests\Feature;

use App\Events\PackageDeletedByAdmin;
use App\Events\PackageUpdatedByAdmin;
use App\Filament\Resources\PackageResource\Pages\ListPackages;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\DbService\PackageService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPackageEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_update_package_updates_only_whitelisted_fields(): void
    {
        Event::fake([PackageUpdatedByAdmin::class]);

        $originalUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $originalMethod = ShippingMethod::factory()->create(['active' => true]);
        $otherMethod = ShippingMethod::factory()->create(['active' => true]);

        $package = Package::factory()->create([
            'tracking' => 'TRK-OLD',
            'description' => 'Descripción vieja',
            'weight' => 2.50,
            'approx_value' => 50.00,
            'shelf_location' => 'A-1',
            'shipping_method_id' => $originalMethod->id,
            'user_id' => $originalUser->id,
            'status' => 'prealerted',
        ]);

        $service = app(PackageService::class);
        $service->adminUpdatePackage($package, [
            'tracking' => 'TRK-NEW',
            'description' => 'Descripción nueva',
            'weight' => 3.75,
            'approx_value' => 99.99,
            'shelf_location' => 'B-2',
            'shipping_method_id' => $otherMethod->id,
            'status' => 'delivered',
            'user_id' => $otherUser->id,
        ]);

        $package->refresh();

        $this->assertSame('TRK-NEW', $package->tracking);
        $this->assertSame('Descripción nueva', $package->description);
        $this->assertSame('3.75', (string) $package->weight);
        $this->assertSame('99.99', (string) $package->approx_value);
        $this->assertSame('B-2', $package->shelf_location);
        $this->assertSame($otherMethod->id, $package->shipping_method_id);

        $this->assertSame('prealerted', $package->status);
        $this->assertSame($originalUser->id, $package->user_id);
    }

    public function test_admin_update_package_dispatches_event_with_correct_diff(): void
    {
        Event::fake([PackageUpdatedByAdmin::class]);

        $package = Package::factory()->create([
            'tracking' => 'TRK-OLD',
            'weight' => 2.50,
        ]);

        $service = app(PackageService::class);
        $service->adminUpdatePackage($package, [
            'tracking' => 'TRK-NEW',
            'weight' => 2.50,
        ]);

        Event::assertDispatched(PackageUpdatedByAdmin::class, function (PackageUpdatedByAdmin $event) use ($package) {
            return $event->package->is($package)
                && array_key_exists('tracking', $event->changes)
                && $event->changes['tracking']['old'] === 'TRK-OLD'
                && $event->changes['tracking']['new'] === 'TRK-NEW'
                && ! array_key_exists('weight', $event->changes);
        });
    }

    public function test_admin_update_package_does_not_dispatch_event_when_nothing_changes(): void
    {
        Event::fake([PackageUpdatedByAdmin::class]);

        $method = ShippingMethod::factory()->create();

        $package = Package::factory()->create([
            'tracking' => 'TRK-SAME',
            'description' => 'Sin cambios',
            'weight' => 4.00,
            'approx_value' => 20.00,
            'shelf_location' => 'C-3',
            'shipping_method_id' => $method->id,
        ]);

        $service = app(PackageService::class);
        $service->adminUpdatePackage($package, [
            'tracking' => 'TRK-SAME',
            'description' => 'Sin cambios',
            'weight' => 4.0,
            'approx_value' => 20.0,
            'shelf_location' => 'C-3',
            'shipping_method_id' => $method->id,
        ]);

        Event::assertNotDispatched(PackageUpdatedByAdmin::class);
    }

    public function test_admin_delete_package_deletes_and_dispatches_event_with_captured_data(): void
    {
        Event::fake([PackageDeletedByAdmin::class]);

        $user = User::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
        ]);

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'tracking' => 'TRK-DEL',
            'description' => 'Paquete a eliminar',
        ]);

        $service = app(PackageService::class);
        $service->adminDeletePackage($package);

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);

        Event::assertDispatched(PackageDeletedByAdmin::class, function (PackageDeletedByAdmin $event) {
            return $event->tracking === 'TRK-DEL'
                && $event->userEmail === 'juan@example.com'
                && $event->userName === 'Juan Pérez'
                && $event->description === 'Paquete a eliminar';
        });
    }

    public function test_admin_delete_package_throws_and_does_not_delete_when_it_has_an_invoice(): void
    {
        Event::fake([PackageDeletedByAdmin::class]);

        $adminUser = User::factory()->create();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-0001',
            'user_id' => $adminUser->id,
            'created_by' => $adminUser->id,
            'subtotal' => 10,
            'discount_amount' => 0,
            'delivery_fee' => 0,
            'total' => 10,
            'points_earned' => 0,
            'generated_at' => now(),
        ]);

        $package = Package::factory()->create(['invoice_id' => $invoice->id]);

        $this->expectException(DomainException::class);

        try {
            app(PackageService::class)->adminDeletePackage($package);
        } finally {
            $this->assertDatabaseHas('packages', ['id' => $package->id]);
            Event::assertNotDispatched(PackageDeletedByAdmin::class);
        }
    }

    // ── Livewire/Filament click-through: "editar" → "confirmar_edicion" ────────

    public function test_admin_editar_action_mounts_nested_confirmar_edicion_action_without_updating_yet(): void
    {
        Event::fake([PackageUpdatedByAdmin::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $method = ShippingMethod::factory()->create(['active' => true]);
        $newMethod = ShippingMethod::factory()->create(['active' => true]);

        $package = Package::factory()->create([
            'tracking' => 'TRK-OLD',
            'description' => 'Descripción vieja',
            'weight' => 2.50,
            'approx_value' => 50.00,
            'shelf_location' => 'A-1',
            'shipping_method_id' => $method->id,
            'status' => 'prealerted',
        ]);

        $this->actingAs($admin);

        $livewire = Livewire::test(ListPackages::class)
            ->mountTableAction('editar', $package)
            ->assertTableActionMounted('editar')
            ->setTableActionData([
                'tracking' => 'TRK-NEW',
                'description' => 'Descripción nueva',
                'weight' => 3.75,
                'approx_value' => 99.99,
                'shelf_location' => 'B-2',
                'shipping_method_id' => $newMethod->id,
            ])
            ->callMountedTableAction();

        $livewire
            ->assertHasNoTableActionErrors()
            ->assertTableActionMounted('editar.confirmar_edicion');

        // The parent action's closure only mounted the nested confirmation —
        // it must NOT have updated the package yet.
        $package->refresh();
        $this->assertSame('TRK-OLD', $package->tracking);
        Event::assertNotDispatched(PackageUpdatedByAdmin::class);
    }

    public function test_admin_editar_confirmar_edicion_full_chain_updates_package_and_dispatches_event(): void
    {
        Event::fake([PackageUpdatedByAdmin::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $method = ShippingMethod::factory()->create(['active' => true]);
        $newMethod = ShippingMethod::factory()->create(['active' => true]);

        $package = Package::factory()->create([
            'tracking' => 'TRK-OLD',
            'description' => 'Descripción vieja',
            'weight' => 2.50,
            'approx_value' => 50.00,
            'shelf_location' => 'A-1',
            'shipping_method_id' => $method->id,
            'status' => 'received_in_business',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPackages::class)
            ->mountTableAction('editar', $package)
            ->setTableActionData([
                'tracking' => 'TRK-NEW',
                'description' => 'Descripción nueva',
                'weight' => 3.75,
                'approx_value' => 99.99,
                'shelf_location' => 'B-2',
                'shipping_method_id' => $newMethod->id,
            ])
            ->callMountedTableAction()
            ->assertTableActionMounted('editar.confirmar_edicion')
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertTableActionNotMounted('editar.confirmar_edicion');

        $package->refresh();
        $this->assertSame('TRK-NEW', $package->tracking);
        $this->assertSame('Descripción nueva', $package->description);
        $this->assertSame('3.75', (string) $package->weight);
        $this->assertSame('99.99', (string) $package->approx_value);
        $this->assertSame('B-2', $package->shelf_location);
        $this->assertSame($newMethod->id, $package->shipping_method_id);

        Event::assertDispatched(PackageUpdatedByAdmin::class, function (PackageUpdatedByAdmin $event) use ($package) {
            return $event->package->is($package)
                && $event->changes['tracking']['old'] === 'TRK-OLD'
                && $event->changes['tracking']['new'] === 'TRK-NEW';
        });
    }

    // ── Livewire/Filament click-through: "eliminar" → "confirmar_eliminacion" ──

    public function test_admin_eliminar_action_mounts_nested_confirmar_eliminacion_action_without_deleting_yet(): void
    {
        Event::fake([PackageDeletedByAdmin::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $package = Package::factory()->create([
            'tracking' => 'TRK-DEL',
            'status' => 'prealerted',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPackages::class)
            ->mountTableAction('eliminar', $package)
            ->assertTableActionMounted('eliminar')
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertTableActionMounted('eliminar.confirmar_eliminacion');

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
        Event::assertNotDispatched(PackageDeletedByAdmin::class);
    }

    public function test_admin_eliminar_confirmar_eliminacion_full_chain_deletes_package_and_dispatches_event(): void
    {
        Event::fake([PackageDeletedByAdmin::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
        ]);

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'tracking' => 'TRK-DEL',
            'description' => 'Paquete a eliminar',
            'status' => 'prealerted',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPackages::class)
            ->mountTableAction('eliminar', $package)
            ->callMountedTableAction()
            ->assertTableActionMounted('eliminar.confirmar_eliminacion')
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertTableActionNotMounted('eliminar.confirmar_eliminacion');

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);

        Event::assertDispatched(PackageDeletedByAdmin::class, function (PackageDeletedByAdmin $event) {
            return $event->tracking === 'TRK-DEL'
                && $event->userEmail === 'juan@example.com'
                && $event->userName === 'Juan Pérez'
                && $event->description === 'Paquete a eliminar';
        });
    }
}
