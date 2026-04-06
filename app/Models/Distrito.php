<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    protected $fillable = ['codigo', 'nombre', 'canton_id'];

    public function canton()
    {
        return $this->belongsTo(Canton::class);
    }
}
