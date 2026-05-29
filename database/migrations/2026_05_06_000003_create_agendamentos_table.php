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
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->string('dia'); // 'sexta', 'sabado', 'domingo'
            $table->string('horario'); // '19:00', '19:30', etc
            $table->string('equipe');
            $table->string('responsavel');
            $table->string('telefone');
            $table->enum('status', ['livre', 'ocupado'])->default('livre');
            $table->timestamps();
            
            // Índice composto para garantir unicidade de dia + horário
            $table->unique(['dia', 'horario']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
