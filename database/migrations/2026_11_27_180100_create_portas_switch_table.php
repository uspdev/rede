<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo atribuído a cada porta física de um switch (equipamento).
     * Só existe linha para portas que receberam um tipo.
     * Tabela nova: não altera nada que já existe.
     */
    public function up(): void
    {
        Schema::create('portas_switch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipamento_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('porta');
            $table->foreignId('tipo_porta_switch_id')->constrained('tipo_portas_switch')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['equipamento_id', 'porta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portas_switch');
    }
};
