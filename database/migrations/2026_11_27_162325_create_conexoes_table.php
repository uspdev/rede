<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conexões (patch cords) entre uma porta de equipamento (switch)
     * e uma porta de patch panel.
     */
    public function up(): void
    {
        Schema::create('conexoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipamento_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('porta_equipamento');
            $table->foreignId('patch_panel_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('porta_patch_panel');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // Cada porta só pode ter um cabo
            $table->unique(['equipamento_id', 'porta_equipamento']);
            $table->unique(['patch_panel_id', 'porta_patch_panel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conexoes');
    }
};
