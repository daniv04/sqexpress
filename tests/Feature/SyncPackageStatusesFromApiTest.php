<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncPackageStatusesFromApiTest extends TestCase
{
    use RefreshDatabase;

    private function apiResponse(array $items): array
    {
        return array_values($items);
    }

    private function makePackage(string $tracking, string $status, ?float $weight = null): Package
    {
        return Package::factory()->create([
            'tracking' => $tracking,
            'status'   => $status,
            'weight'   => $weight ?? 1.0,
            'shelf_location' => in_array($status, ['received_in_business', 'ready_to_deliver']) ? 'A-01' : null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Avance simple de un estado
    // -------------------------------------------------------------------------

    public function test_advances_prealerted_to_received_in_warehouse(): void
    {
        $package = $this->makePackage('TRK001', 'prealerted');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK001', 'estado' => 'Recibido en Miami', 'pesoFinal' => '2.5'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $package->refresh();
        $this->assertEquals('received_in_warehouse', $package->status);
        $this->assertEquals(2.5, $package->weight);
    }

    public function test_advances_received_in_warehouse_to_assigned_flight(): void
    {
        $package = $this->makePackage('TRK002', 'received_in_warehouse');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK002', 'estado' => 'Vuelo Asignado', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('assigned_flight', $package->refresh()->status);
    }

    public function test_advances_assigned_flight_to_received_in_customs(): void
    {
        $package = $this->makePackage('TRK003', 'assigned_flight');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK003', 'estado' => 'Aduana', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_customs', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Avance de múltiples pasos en un solo sync
    // -------------------------------------------------------------------------

    public function test_advances_prealerted_to_received_in_business_step_by_step(): void
    {
        $package = $this->makePackage('TRK004', 'prealerted');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK004', 'estado' => 'Recibido en Costa Rica', 'pesoFinal' => '3.2'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_business', $package->refresh()->status);
    }

    public function test_weight_is_set_when_advancing_through_warehouse(): void
    {
        $package = $this->makePackage('TRK005', 'prealerted', weight: 0.0);

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK005', 'estado' => 'Recibido en Costa Rica', 'pesoFinal' => '4.7'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals(4.7, $package->refresh()->weight);
    }

    public function test_history_records_are_created_for_each_step(): void
    {
        $package = $this->makePackage('TRK006', 'prealerted');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK006', 'estado' => 'Recibido en Costa Rica', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        // prealerted→warehouse, warehouse→flight, flight→customs, customs→business = 4 transitions
        $this->assertCount(4, $package->statusHistories()->get());
    }

    // -------------------------------------------------------------------------
    // No retroceder si el paquete ya está más avanzado
    // -------------------------------------------------------------------------

    public function test_does_not_downgrade_if_package_is_already_ahead(): void
    {
        $package = $this->makePackage('TRK007', 'received_in_customs');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK007', 'estado' => 'Recibido en Miami', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_customs', $package->refresh()->status);
    }

    public function test_does_not_update_if_api_state_matches_current(): void
    {
        $package = $this->makePackage('TRK008', 'received_in_warehouse');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK008', 'estado' => 'Recibido en Miami', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_warehouse', $package->refresh()->status);
        $this->assertCount(0, $package->statusHistories()->get());
    }

    // -------------------------------------------------------------------------
    // Paquetes que no deben sincronizarse
    // -------------------------------------------------------------------------

    public function test_skips_packages_not_in_api_response(): void
    {
        $package = $this->makePackage('TRK009', 'prealerted');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'OTRO123', 'estado' => 'Recibido en Miami', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('prealerted', $package->refresh()->status);
    }

    public function test_does_not_sync_packages_in_non_syncable_statuses(): void
    {
        $delivered  = $this->makePackage('TRK010', 'delivered');
        $canceled   = $this->makePackage('TRK011', 'canceled');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK010', 'estado' => 'Recibido en Costa Rica', 'pesoFinal' => '1.0'],
                ['seguimiento' => 'TRK011', 'estado' => 'Recibido en Costa Rica', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('delivered', $delivered->refresh()->status);
        $this->assertEquals('canceled', $canceled->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Sensibilidad a mayúsculas
    // -------------------------------------------------------------------------

    public function test_matches_tracking_case_insensitively(): void
    {
        $package = $this->makePackage('TBADD0045952890', 'prealerted');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                // API devuelve en minúsculas
                ['seguimiento' => 'tbadd0045952890', 'estado' => 'Recibido en Miami', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_warehouse', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Estado desconocido en la API
    // -------------------------------------------------------------------------

    public function test_skips_unknown_api_state_and_logs_warning(): void
    {
        $package = $this->makePackage('TRK012', 'prealerted');

        Log::shouldReceive('warning')
            ->once()
            ->with('SyncPackageStatusesFromApi: estado desconocido en API', \Mockery::subset([
                'tracking' => 'TRK012',
            ]));

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'TRK012', 'estado' => 'Estado Inventado', 'pesoFinal' => '1.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('prealerted', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Fallos de la API
    // -------------------------------------------------------------------------

    public function test_returns_failure_when_api_responds_with_error_status(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->artisan('packages:sync-statuses')->assertFailed();
    }

    public function test_returns_failure_when_api_is_unreachable(): void
    {
        Http::fake(['*' => Http::sequence()->push(fn () => throw new \Exception('Connection refused'))]);

        $this->artisan('packages:sync-statuses')->assertFailed();
    }

    // -------------------------------------------------------------------------
    // Múltiples paquetes en un mismo sync
    // -------------------------------------------------------------------------

    public function test_updates_multiple_packages_in_single_run(): void
    {
        $p1 = $this->makePackage('PKG001', 'prealerted');
        $p2 = $this->makePackage('PKG002', 'received_in_warehouse');
        $p3 = $this->makePackage('PKG003', 'assigned_flight');

        Http::fake([
            '*' => Http::response($this->apiResponse([
                ['seguimiento' => 'PKG001', 'estado' => 'Recibido en Miami',     'pesoFinal' => '1.0'],
                ['seguimiento' => 'PKG002', 'estado' => 'Vuelo Asignado',        'pesoFinal' => '2.0'],
                ['seguimiento' => 'PKG003', 'estado' => 'Recibido en Costa Rica','pesoFinal' => '3.0'],
            ])),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_warehouse', $p1->refresh()->status);
        $this->assertEquals('assigned_flight',        $p2->refresh()->status);
        $this->assertEquals('received_in_business',   $p3->refresh()->status);
    }
}
