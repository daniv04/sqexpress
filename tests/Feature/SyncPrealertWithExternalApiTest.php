<?php

namespace Tests\Feature;

use App\Events\PackagePrealerted;
use App\Listeners\SyncPrealertWithExternalApi;
use App\Models\Package;
use App\Services\MlcLogisticsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SyncPrealertWithExternalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function loginResponse(): array
    {
        return ['token' => 'fake-jwt', 'expiracion' => now()->addHour()->toDateTimeString()];
    }

    private function handle(Package $package): void
    {
        (new SyncPrealertWithExternalApi(new MlcLogisticsClient()))->handle(
            new PackagePrealerted($package),
        );
    }

    public function test_sends_prealerta_with_expected_body(): void
    {
        $package = Package::factory()->create([
            'tracking' => 'TRK001',
            'description' => 'Un producto cualquiera',
        ]);

        Http::fake([
            '*/users/login' => Http::response($this->loginResponse()),
            '*/prealertas*' => Http::response([
                'success' => true,
                'status' => 'success',
                'prealertas' => [['id' => 1, 'codigo' => 'SQECR04', 'numero_seguimiento' => 'TRK001']],
            ]),
        ]);

        $this->handle($package);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/prealertas')) {
                return true; // ignorar la llamada de login
            }

            $body = $request->data();

            return $body === [[
                'codigo' => config('services.mlclogistics.default_codigo'),
                'numero_seguimiento' => 'TRK001',
                'fecha' => now()->toDateString(),
                'tipo_envio' => 'aereo',
                'observaciones' => 'Un producto cualquiera',
            ]];
        });
    }

    public function test_logs_warning_and_notifies_admins_when_api_reports_failure(): void
    {
        $package = Package::factory()->create(['tracking' => 'TRK002']);

        Http::fake([
            '*/users/login' => Http::response($this->loginResponse()),
            '*/prealertas*' => Http::response(['success' => false, 'status' => 'error'], 200),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('SyncPrealertWithExternalApi: respuesta inesperada', \Mockery::subset([
                'tracking' => 'TRK002',
            ]));

        $this->handle($package);
    }

    public function test_logs_error_and_notifies_admins_on_exception(): void
    {
        $package = Package::factory()->create(['tracking' => 'TRK003']);

        Http::fake([
            '*/users/login' => Http::response($this->loginResponse()),
            '*/prealertas*' => Http::sequence()->push(fn () => throw new \Exception('Connection refused')),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('SyncPrealertWithExternalApi: excepción al llamar API', \Mockery::subset([
                'tracking' => 'TRK003',
            ]));

        $this->handle($package);
    }
}
