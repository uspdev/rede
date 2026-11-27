<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Como as portas do switch são numeradas no painel frontal:
     *  - horizontal: linha de cima 1, 2, 3...; linha de baixo continua (ex.: 13, 14, 15...)
     *  - vertical:   ímpares em cima e pares embaixo (1/2, 3/4, 5/6...)
     * Modelos já cadastrados ficam como horizontal.
     */
    public function up(): void
    {
        Schema::table('modelo_switches', function (Blueprint $table) {
            $table->string('numeracao_portas', 20)->default('horizontal')->after('qtde_portas');
        });
    }

    public function down(): void
    {
        Schema::table('modelo_switches', function (Blueprint $table) {
            $table->dropColumn('numeracao_portas');
        });
    }
};
