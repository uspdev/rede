<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request\EquipamentoRequest;
use App\Models\Equipamento;

class EquipamentoController extends Controller
{
    public function store(EquipamentoRequest $request)
    {
        // Verificar autorização
        if($request->header('Authorization') != env('AUTHORIZATION_KEY')){
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $validated['ordem'] = Equipamento::where('rack_id', $validated['rack_id'])->max('ordem') + 1;

        $equipamento = Equipamento::updateOrCreate($validated);

        return response()->json([
            'message' => 'Equipamento criado/atualizado com sucesso',
            'equipamento' => $equipamento->load('modeloSwitch')
        ], 201);
    }
}
