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
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->boolean('cancelado')->default(false)->after('status');
            $table->string('motivo_cancelamento')->nullable()->after('cancelado');
            $table->json('notificacoes_enviadas')->nullable()->after('motivo_cancelamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn('cancelado');
            $table->dropColumn('motivo_cancelamento');
            $table->dropColumn('notificacoes_enviadas');
        });
    }
};
