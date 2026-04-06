<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Distrito;
use Illuminate\Support\Facades\File;

class GeodataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Leer el archivo JSON
        $jsonPath = database_path('data/geodata.json');
        $geodata = json_decode(File::get($jsonPath), true);

        foreach ($geodata as $provinciaData) {
            // Crear provincia
            $provincia = Provincia::create([
                'codigo' => $provinciaData['codigo'],
                'nombre' => $provinciaData['nombre'],
            ]);

            // Crear cantones de la provincia
            foreach ($provinciaData['cantones'] as $cantonData) {
                $canton = Canton::create([
                    'codigo' => $cantonData['codigo'],
                    'nombre' => $cantonData['nombre'],
                    'provincia_id' => $provincia->id,
                ]);

                // Crear distritos del cantón
                foreach ($cantonData['distritos'] as $distritoData) {
                    Distrito::create([
                        'codigo' => $distritoData['codigo'],
                        'nombre' => $distritoData['nombre'],
                        'canton_id' => $canton->id,
                    ]);
                }
            }
        }

        $this->command->info('Geodata cargada exitosamente!');
    }
}
