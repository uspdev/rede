<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Camada de infraestrutura da planta: eletrocalhas, canaletas e dutos.
     * Cada percurso é uma linha com vários pontos, em % da imagem da planta
     * (mesmo sistema de coordenadas das outras marcações).
     * Tabela nova: não altera nada que já existe.
     */
    public function up(): void
    {
        Schema::create('planta_percursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planta_id')->constrained('plantas')->cascadeOnDelete();
            $table->string('tipo', 20); // eletrocalha | canaleta | duto
            $table->json('pontos');     // [[x, y], [x, y], ...] em %
            $table->string('descricao')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planta_percursos');
    }
};
