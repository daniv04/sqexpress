<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    protected $fillable = ['codigo', 'nombre'];

    public function cantones()
    {
        return $this->hasMany(Canton::class);
    }
}
