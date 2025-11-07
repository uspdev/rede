<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equipamento;

class EquipamentoController extends Controller
{
    public function store(Request $request)
    {
        // Verificar autorização
        if($request->header('Authorization') != env('AUTHORIZATION_KEY')){
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        // Validação dos campos
        $validated = $request->validate([
            'hostname' => 'required',
            'model' => 'required',
            'ip' => 'required|ip',
            'qtde_portas' => 'required|integer|min:1|max:48',
            'rack_id' => 'required|exists:racks,id',
            'user_id' => 'required|exists:users,id',
            'poe_type' => 'boolean'
        ]);

        $equipamento = Equipamento::updateOrCreate(
            ['hostname' => $validated['hostname']],
            $validated
        );

        return response()->json([
            'message' => 'Equipamento criado/atualizado com sucesso',
            'equipamento' => $equipamento
        ], 201);
    }
}
