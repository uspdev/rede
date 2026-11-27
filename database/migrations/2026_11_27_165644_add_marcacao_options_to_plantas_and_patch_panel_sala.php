<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opções novas da tela de marcação. Só adiciona colunas; as marcações existentes
     * (x, y, fontsize, label_position) não são alteradas.
     */
    public function up(): void
    {
        Schema::table('plantas', function (Blueprint $table) {
            // multiplicador aplicado ao tamanho de todas as marcações da planta
            $table->decimal('escala_marcadores', 4, 2)->default(1);
            // janela visível da planta, em % da imagem inteira: {"x":..,"y":..,"w":..,"h":..}
            $table->json('recorte')->nullable();
        });

        Schema::table('patch_panel_sala', function (Blueprint $table) {
            // deslocamento do rótulo em relação ao ícone (em "em", proporcional ao tamanho da fonte)
            // usado quando label_position = 'livre'
            $table->decimal('label_dx', 6, 2)->nullable();
            $table->decimal('label_dy', 6, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('patch_panel_sala', function (Blueprint $table) {
            $table->dropColumn(['label_dx', 'label_dy']);
        });

        Schema::table('plantas', function (Blueprint $table) {
            $table->dropColumn(['escala_marcadores', 'recorte']);
        });
    }
};
