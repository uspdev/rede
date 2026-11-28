<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipos de porta de switch (ex.: "Acesso", "Uplink", "Câmeras"),
     * com cor e VLAN opcional. Tabela nova: não altera nada que já existe.
     */
    public function up(): void
    {
        Schema::create('tipo_portas_switch', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('cor', 7)->nullable()->default('#0D6EFD');
            $table->unsignedSmallInteger('vlan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_portas_switch');
    }
};
