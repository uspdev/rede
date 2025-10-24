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
        $request->validate([
            'hostname' => 'required',
            'model' => 'required',
            'ip' => 'required|ip',
            'qtde_portas' => 'required|integer|min:1|max:48',
            'rack_id' => 'required|exists:racks,id',
            'user_id' => 'required|exists:users,id',
            'poe_type' => 'boolean' 
        ]);

        // Processar hostname 
        $hostname = str_replace('a', '', $request->hostname);

        // Verificar se equipamento já existe
        $equipamento = Equipamento::where('hostname', $hostname)->first();
        
        if(!$equipamento) {
            $equipamento = new Equipamento;
        }

        // Preencher dados 
        $equipamento->hostname = $hostname;
        $equipamento->model = $request->model;
        $equipamento->ip = $request->ip;
        $equipamento->qtde_portas = $request->qtde_portas;
        $equipamento->rack_id = $request->rack_id;
        $equipamento->user_id = $request->user_id;
        $equipamento->poe_type = $request->poe_type ?? false;

        $equipamento->save();

        return response()->json([
            'message' => 'Equipamento criado/atualizado com sucesso',
            'equipamento' => $equipamento
        ], 201);
    }   
}