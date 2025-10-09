<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equipamento;

class EquipamentoController extends Controller
{
    public function store(Request $request){
        if($request->header('Authorization') != env('authorization_key')){
            return response('Unauthorized action.', 403);
        }

        die($request->hostname);

        $request->validate([
            'hostname' => 'required',
            'model' => 'required',
            'ip' => 'required',
            'poe_type' => 'required',
            'local' => 'required',
            'position' => 'required',

        ]);

        $equipamento = Equipamento::where('hostname',$request->hostname)->first();
        if(!$equipamento) $equipamento = new Equipamento;

        $equipamento->hostname = $hostname;
        $equipamento->model = $request->model;
        $equipamento->poe_type = $request->poe_type;
        $equipamento->ip = $request->ip;
        $equipamento->local = $request->local;
        $equipamento->position = $request->position;

        $equipamento->uplink_extra_ports = $request->uplink_extra_ports;
        $equipamento->rep_ports = $request->rep_ports;
        $equipamento->printer_ports = $request->printer_ports;
        $equipamento->ignore_ports = $request->ignore_ports;
        $equipamento->save();

        return response()->json($equipamento);
    }   
}
