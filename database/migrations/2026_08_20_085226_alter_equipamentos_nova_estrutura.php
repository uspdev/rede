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
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->foreignId('modelo_switch_id')->after('rack_id')->nullable()->constrained('modelo_switches');
            $table->integer('ordem')->default(0);
            $table->text('comentario')->nullable()->after('ordem');

            $table->dropColumn(['model', 'qtde_portas', 'poe_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            //
        });
    }
};
