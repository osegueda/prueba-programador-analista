<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_inventario_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_producto')
                ->constrained('tbl_productos')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->enum('tipo_movimiento', ['ENTRADA', 'SALIDA']);
            $table->unsignedInteger('cantidad');
            $table->dateTime('fecha_ingreso');
            $table->string('motivo', 255)->nullable();
            $table->string('referencia', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_inventario_productos');
    }
};
