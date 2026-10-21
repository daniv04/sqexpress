<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DbService\LockerCodeService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LockerCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private LockerCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LockerCodeService();
    }

    /**
     * Test que genera el primer código cuando no hay usuarios.
     */
    public function test_generates_first_locker_code_when_no_users_exist(): void
    {
        $code = $this->service->generateNextLockerCode();

        $this->assertEquals('SQECR01', $code);
    }

    /**
     * Test que genera el siguiente código basado en el último existente.
     */
    public function test_generates_next_locker_code_based_on_last_user(): void
    {
        // Crear un usuario con locker_code
        User::factory()->create(['locker_code' => 'SQECR05']);

        $code = $this->service->generateNextLockerCode();

        $this->assertEquals('SQECR06', $code);
    }

    /**
     * Test que genera el código correcto cuando hay múltiples usuarios.
     */
    public function test_generates_correct_code_with_multiple_users(): void
    {
        // Crear varios usuarios con diferentes locker_codes
        User::factory()->create(['locker_code' => 'SQECR01']);
        User::factory()->create(['locker_code' => 'SQECR03']);
        User::factory()->create(['locker_code' => 'SQECR10']);
        User::factory()->create(['locker_code' => null]); // Usuario sin código

        $code = $this->service->generateNextLockerCode();

        // Debe tomar el más alto (SQECR10) e incrementar
        $this->assertEquals('SQECR11', $code);
    }

    /**
     * Test que genera código correcto con números grandes.
     */
    public function test_generates_code_with_large_numbers(): void
    {
        User::factory()->create(['locker_code' => 'SQECR99999']);

        $code = $this->service->generateNextLockerCode();

        $this->assertEquals('SQECR100000', $code);
    }

    /**
     * Test que ignora códigos con formato incorrecto.
     */
    public function test_handles_malformed_locker_codes(): void
    {
        User::factory()->create(['locker_code' => 'SQECR05']);
        User::factory()->create(['locker_code' => 'INVALID123']);
        User::factory()->create(['locker_code' => 'ABC00010']);

        $code = $this->service->generateNextLockerCode();

        $this->assertEquals('SQECR06', $code);
    }

    /**
     * Test que maneja códigos no secuenciales correctamente.
     */
    public function test_handles_non_sequential_codes(): void
    {
        User::factory()->create(['locker_code' => 'SQECR01']);
        User::factory()->create(['locker_code' => 'SQECR99']);
        User::factory()->create(['locker_code' => 'SQECR05']);

        $code = $this->service->generateNextLockerCode();

        $this->assertEquals('SQECR100', $code);
    }

    /**
     * Test que el formato siempre tiene 2 dígitos mínimo.
     */
    public function test_code_format_always_has_minimum_two_digits(): void
    {
        $code = $this->service->generateNextLockerCode();

        $this->assertMatchesRegularExpression('/^SQECR\d{2,}$/', $code);
    }

    /**
     * Test que todos los usuarios null no afectan la generación.
     */
    public function test_all_null_locker_codes_generates_first_code(): void
    {
        User::factory()->count(5)->create(['locker_code' => null]);

        $code = $this->service->generateNextLockerCode();

        $this->assertEquals('SQECR01', $code);
    }

    /**
     * Test de generación consecutiva múltiple.
     */
    public function test_generates_consecutive_codes_multiple_times(): void
    {
        $code1 = $this->service->generateNextLockerCode();
        User::factory()->create(['locker_code' => $code1]);

        $code2 = $this->service->generateNextLockerCode();
        User::factory()->create(['locker_code' => $code2]);

        $code3 = $this->service->generateNextLockerCode();

        $this->assertEquals('SQECR01', $code1);
        $this->assertEquals('SQECR02', $code2);
        $this->assertEquals('SQECR03', $code3);
    }
}