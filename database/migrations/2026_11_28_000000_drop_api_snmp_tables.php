<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove as tabelas do antigo histórico de portas coletado pela API/SNMP
     * (portas → snapshots → macs). As rotas que gravavam nelas foram desativadas
     * e o código correspondente removido.
     *
     * Ordem inversa das FKs: macs → snapshots → portas. dropIfExists não falha se
     * alguma já não existir.
     *
     * ATENÇÃO: apaga os dados dessas tabelas. O down() recria apenas a estrutura.
     */
    public function up(): void
    {
        Schema::dropIfExists('macs');
        Schema::dropIfExists('snapshots');
        Schema::dropIfExists('portas');
    }

    public function down(): void
    {
        if (!Schema::hasTable('portas')) {
            Schema::create('portas', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->string('porta');
                $table->string('tipo')->nullable();
                $table->foreignId('equipamento_id')->constrained();
            });
        }

        if (!Schema::hasTable('snapshots')) {
            Schema::create('snapshots', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->enum('status', ['up', 'down']);
                $table->foreignId('porta_id')->constrained();
            });
        }

        if (!Schema::hasTable('macs')) {
            Schema::create('macs', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->integer('vlan');
                $table->macAddress('mac');
                $table->foreignId('snapshot_id')->constrained();
            });
        }
    }
};
