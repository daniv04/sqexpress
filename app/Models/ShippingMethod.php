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
        'unit_type',
        'price_per_unit',
    ];

    public const UNIT_OPTIONS = [
        'kg' => 'Kilogramo (kg)',
        'lb' => 'Libra (lb)',
        'm3' => 'Metro cúbico (m³)',
        'ft3' => 'Pie cúbico (ft³)',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'price_per_unit' => 'decimal:2',
        ];
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function unitSuffix(): string
    {
        return match ($this->unit_type) {
            'lb' => 'lb',
            'm3' => 'm³',
            'ft3' => 'ft³',
            default => 'kg',
        };
    }
}
