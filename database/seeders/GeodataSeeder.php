<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Distrito;
use App\Models\Barrio;
use Illuminate\Support\Facades\File;

class GeodataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Provincia::exists() && Barrio::exists()) {
            return;
        }

        // Leer el archivo JSON
        $jsonPath = database_path('data/geodata.json');
        $geodata = json_decode(File::get($jsonPath), true);

        foreach ($geodata as $provinciaData) {
            // Crear provincia (o reutilizar si ya existe, para poder rellenar barrios sin duplicar)
            $provincia = Provincia::firstOrCreate(
                ['codigo' => $provinciaData['codigo']],
                ['nombre' => $provinciaData['nombre']]
            );

            // Crear cantones de la provincia
            foreach ($provinciaData['cantones'] as $cantonData) {
                $canton = Canton::firstOrCreate(
                    ['codigo' => $cantonData['codigo'], 'provincia_id' => $provincia->id],
                    ['nombre' => $cantonData['nombre']]
                );

                // Crear distritos del cantón
                foreach ($cantonData['distritos'] as $distritoData) {
                    $distrito = Distrito::firstOrCreate(
                        ['codigo' => $distritoData['codigo'], 'canton_id' => $canton->id],
                        ['nombre' => $distritoData['nombre']]
                    );

                    // Crear barrios del distrito
                    foreach ($distritoData['barrios'] as $barrioData) {
                        Barrio::firstOrCreate(
                            ['codigo' => $barrioData['codigo'], 'distrito_id' => $distrito->id],
                            ['nombre' => $barrioData['nombre']]
                        );
                    }
                }
            }
        }

        $this->command->info('Geodata cargada exitosamente!');
    }
}
