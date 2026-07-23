<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barrio extends Model
{
    protected $fillable = ['codigo', 'nombre', 'distrito_id'];

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
}
