<?php

namespace App\Services;

use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Distrito;

class GeodataService
{
    public function getProvincias()
    {
        return Provincia::select('id', 'codigo', 'nombre')
            ->orderBy('nombre')
            ->get();
    }

    public function getCantonesByProvincia(int $provinciaId)
    {
        return Canton::where('provincia_id', $provinciaId)
            ->select('id', 'codigo', 'nombre', 'provincia_id')
            ->orderBy('nombre')
            ->get();
    }

    public function getDistritosByCanton(int $cantonId)
    {
        return Distrito::where('canton_id', $cantonId)
            ->select('id', 'codigo', 'nombre', 'canton_id')
            ->orderBy('nombre')
            ->get();
    }
}
