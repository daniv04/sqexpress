<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncPackageStatusesFromApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El token JWT queda cacheado entre tests (CACHE_STORE=array persiste
        // en el mismo proceso), lo limpiamos para que cada test loguee de nuevo.
        Cache::flush();
    }

    private function loginResponse(): array
    {
        return ['token' => 'fake-jwt', 'expiracion' => now()->addHour()->toDateTimeString()];
    }

    private function fakeMlc(array $prealertas = [], array $paquetes = []): void
    {
        Http::fake([
            '*/users/login' => Http::response($this->loginResponse()),
            '*/prealertas*' => Http::response([
                'prealertas' => $prealertas,
                'pagination' => ['pageCount' => 1],
            ]),
            '*/paquetes*' => Http::response([
                'paquetes' => $paquetes,
                'pagination' => ['pageCount' => 1],
            ]),
        ]);
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
    // Avance vía prealertas (estatus 0/1)
    // -------------------------------------------------------------------------

    public function test_prealerta_estatus_one_advances_to_received_in_warehouse(): void
    {
        $package = $this->makePackage('TRK001', 'prealerted', weight: 0.0);

        $this->fakeMlc(prealertas: [
            ['numero_seguimiento' => 'TRK001', 'estatus' => 1],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $package->refresh();
        $this->assertEquals('received_in_warehouse', $package->status);
        $this->assertEquals(0.0, $package->weight); // la prealerta no trae peso
    }

    public function test_prealerta_estatus_zero_does_not_advance(): void
    {
        $package = $this->makePackage('TRK002', 'prealerted');

        $this->fakeMlc(prealertas: [
            ['numero_seguimiento' => 'TRK002', 'estatus' => 0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('prealerted', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Avance simple vía paquetes (un paso)
    // -------------------------------------------------------------------------

    public function test_advances_received_in_warehouse_to_assigned_flight(): void
    {
        $package = $this->makePackage('TRK003', 'received_in_warehouse');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK003', 'estatus' => 'Manifiesto Programado', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('assigned_flight', $package->refresh()->status);
    }

    public function test_advances_assigned_flight_to_in_transit(): void
    {
        $package = $this->makePackage('TRK004', 'assigned_flight');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK004', 'estatus' => 'En Transito a CR', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('in_transit', $package->refresh()->status);
    }

    public function test_advances_in_transit_to_received_in_customs(): void
    {
        $package = $this->makePackage('TRK005', 'in_transit');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK005', 'estatus' => 'En Aduanas CR', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_customs', $package->refresh()->status);
    }

    public function test_advances_received_in_customs_to_received_in_business(): void
    {
        $package = $this->makePackage('TRK006', 'received_in_customs');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK006', 'estatus' => 'Entregado', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_business', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Avance de múltiples pasos en un solo sync
    // -------------------------------------------------------------------------

    public function test_advances_prealerted_straight_to_received_in_business_via_paquetes(): void
    {
        $package = $this->makePackage('TRK007', 'prealerted', weight: 0.0);

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK007', 'estatus' => 'Entregado', 'peso_real' => 3.2],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $package->refresh();
        $this->assertEquals('received_in_business', $package->status);
        $this->assertEquals(0.0, $package->weight); // el peso ya no viene del API
    }

    public function test_sync_never_assigns_weight_from_api(): void
    {
        $package = $this->makePackage('TRK008', 'prealerted', weight: 500.0);

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK008', 'estatus' => 'Manifiesto Programado', 'peso_real' => 3.2, 'peso' => 4.7],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $package->refresh();
        $this->assertEquals('assigned_flight', $package->status);
        $this->assertEquals(500.0, $package->weight); // lo asigna el admin manualmente
    }

    public function test_history_records_are_created_for_each_step(): void
    {
        $package = $this->makePackage('TRK009', 'prealerted');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK009', 'estatus' => 'Entregado', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        // prealerted→warehouse→flight→in_transit→customs→customs_process_finished→business = 6 transiciones
        $this->assertCount(6, $package->statusHistories()->get());
    }

    // -------------------------------------------------------------------------
    // No retroceder / no repetir si el paquete ya está al día
    // -------------------------------------------------------------------------

    public function test_does_not_downgrade_if_package_is_already_ahead(): void
    {
        $package = $this->makePackage('TRK010', 'received_in_customs');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK010', 'estatus' => 'Manifiesto Programado', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_customs', $package->refresh()->status);
    }

    public function test_does_not_update_if_api_state_matches_current(): void
    {
        $package = $this->makePackage('TRK011', 'assigned_flight');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK011', 'estatus' => 'Manifiesto Programado', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('assigned_flight', $package->refresh()->status);
        $this->assertCount(0, $package->statusHistories()->get());
    }

    // -------------------------------------------------------------------------
    // Paquetes que no deben sincronizarse
    // -------------------------------------------------------------------------

    public function test_skips_packages_not_in_any_api_response(): void
    {
        $package = $this->makePackage('TRK012', 'prealerted');

        $this->fakeMlc(
            prealertas: [['numero_seguimiento' => 'OTRO123', 'estatus' => 1]],
            paquetes: [['tracking' => 'OTRO456', 'estatus' => 'Entregado']],
        );

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('prealerted', $package->refresh()->status);
    }

    public function test_does_not_sync_packages_in_non_syncable_statuses(): void
    {
        $delivered = $this->makePackage('TRK013', 'delivered');
        $canceled  = $this->makePackage('TRK014', 'canceled');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK013', 'estatus' => 'Entregado', 'peso_real' => 1.0],
            ['tracking' => 'TRK014', 'estatus' => 'Entregado', 'peso_real' => 1.0],
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
        $package = $this->makePackage('TBADD0045952890', 'received_in_warehouse');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'tbadd0045952890', 'estatus' => 'Manifiesto Programado', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('assigned_flight', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Estados a ignorar explícitamente (decisión de negocio, sin warning)
    // -------------------------------------------------------------------------

    public function test_ignores_known_irrelevant_states_without_warning(): void
    {
        Log::shouldReceive('warning')->never();

        $package = $this->makePackage('TRK015', 'received_in_customs');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK015', 'estatus' => 'Entrega Programada', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_customs', $package->refresh()->status);
    }

    public function test_advances_received_in_customs_to_customs_process_finished(): void
    {
        $package = $this->makePackage('TRK017', 'received_in_customs');

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK017', 'estatus' => 'En Oficina Central', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('customs_process_finished', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Estado desconocido en la API
    // -------------------------------------------------------------------------

    public function test_skips_unknown_api_state_and_logs_warning(): void
    {
        $package = $this->makePackage('TRK016', 'received_in_warehouse');

        Log::shouldReceive('warning')
            ->once()
            ->with('SyncPackageStatusesFromApi: estado desconocido en API', \Mockery::subset([
                'tracking' => 'TRK016',
            ]));

        $this->fakeMlc(paquetes: [
            ['tracking' => 'TRK016', 'estatus' => 'Estado Inventado', 'peso_real' => 1.0],
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_warehouse', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Fallos de la API
    // -------------------------------------------------------------------------

    public function test_returns_failure_when_api_responds_with_error_status(): void
    {
        Http::fake([
            '*/users/login' => Http::response($this->loginResponse()),
            '*/prealertas*' => Http::response([], 500),
            '*/paquetes*' => Http::response([], 500),
        ]);

        $this->artisan('packages:sync-statuses')->assertFailed();
    }

    public function test_returns_failure_when_api_is_unreachable(): void
    {
        Http::fake([
            '*/users/login' => Http::response($this->loginResponse()),
            '*/prealertas*' => Http::sequence()->push(fn () => throw new \Exception('Connection refused')),
        ]);

        $this->artisan('packages:sync-statuses')->assertFailed();
    }

    // -------------------------------------------------------------------------
    // Paginación
    // -------------------------------------------------------------------------

    public function test_follows_pagination_across_multiple_pages(): void
    {
        $package = $this->makePackage('TRK017', 'received_in_warehouse');

        Http::fake([
            '*/users/login' => Http::response($this->loginResponse()),
            '*/prealertas*' => Http::response(['prealertas' => [], 'pagination' => ['pageCount' => 1]]),
            '*/paquetes*' => Http::sequence()
                ->push([
                    'paquetes' => [['tracking' => 'OTRO_EN_PAGINA_1', 'estatus' => 'Entregado', 'peso_real' => 1.0]],
                    'pagination' => ['pageCount' => 2],
                ])
                ->push([
                    'paquetes' => [['tracking' => 'TRK017', 'estatus' => 'Manifiesto Programado', 'peso_real' => 1.0]],
                    'pagination' => ['pageCount' => 2],
                ]),
        ]);

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('assigned_flight', $package->refresh()->status);
    }

    // -------------------------------------------------------------------------
    // Múltiples paquetes en un mismo sync
    // -------------------------------------------------------------------------

    public function test_updates_multiple_packages_in_single_run(): void
    {
        $p1 = $this->makePackage('PKG001', 'prealerted');
        $p2 = $this->makePackage('PKG002', 'received_in_warehouse');
        $p3 = $this->makePackage('PKG003', 'assigned_flight');

        $this->fakeMlc(
            prealertas: [
                ['numero_seguimiento' => 'PKG001', 'estatus' => 1],
            ],
            paquetes: [
                ['tracking' => 'PKG002', 'estatus' => 'Manifiesto Programado', 'peso_real' => 2.0],
                ['tracking' => 'PKG003', 'estatus' => 'Entregado', 'peso_real' => 3.0],
            ],
        );

        $this->artisan('packages:sync-statuses')->assertSuccessful();

        $this->assertEquals('received_in_warehouse', $p1->refresh()->status);
        $this->assertEquals('assigned_flight',        $p2->refresh()->status);
        $this->assertEquals('received_in_business',   $p3->refresh()->status);
    }
}
