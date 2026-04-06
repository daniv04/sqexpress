<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->string('pais')->nullable();
            $table->string('direccion')->nullable();
            $table->string('estado')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('telefono')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('nombre_en_campo')->nullable();
            $table->string('complemento_nombre')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn([
                'pais',
                'direccion',
                'estado',
                'ciudad',
                'telefono',
                'codigo_postal',
                'nombre_en_campo',
                'complemento_nombre',
            ]);
        });
    }
};
