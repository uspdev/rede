<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Racks passam a ficar dentro de uma TR.
     *
     * Seguro para produção:
     *  - a coluna é nova e nullable (racks.predio_id continua existindo e preenchido);
     *  - para cada prédio que já tem racks, cria a "TR 1" e move todos os racks dele para ela;
     *  - pode ser rodada de novo sem duplicar nada (reaproveita a TR 1 se já existir).
     */
    public function up(): void
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->foreignId('telecom_room_id')->nullable()->after('predio_id')
                ->constrained('telecom_rooms')->restrictOnDelete();
        });

        $agora = now();
        $prediosComRacks = DB::table('racks')
            ->whereNull('telecom_room_id')
            ->distinct()
            ->pluck('predio_id');

        foreach ($prediosComRacks as $predioId) {
            $trId = DB::table('telecom_rooms')
                ->where('predio_id', $predioId)
                ->where('nome', 'TR 1')
                ->value('id');

            if (!$trId) {
                $trId = DB::table('telecom_rooms')->insertGetId([
                    'predio_id' => $predioId,
                    'nome' => 'TR 1',
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
            }

            DB::table('racks')
                ->where('predio_id', $predioId)
                ->whereNull('telecom_room_id')
                ->update(['telecom_room_id' => $trId]);
        }
    }

    public function down(): void
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('telecom_room_id');
        });
    }
};
