<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
        'pais',
        'direccion',
        'estado',
        'ciudad',
        'telefono',
        'codigo_postal',
        'nombre_en_campo',
        'complemento_nombre',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}
