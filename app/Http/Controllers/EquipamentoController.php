<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;
use App\Models\ModeloSwitch;
use App\Models\Rack;
use App\Http\Requests\EquipamentoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EquipamentoController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('admin');

        return view('equipamentos.create', [
            'racks' => Rack::all(),
            'modelos' => ModeloSwitch::orderBy('fabricante')->orderBy('nome')->get(),
            'rack_selecionado' => $request->input('rack_id')
        ]);
    }

    public function store(EquipamentoRequest $request)
    {
        Gate::authorize('admin');

        $validated = $request->validated();

        $validated['ordem'] = Equipamento::where('rack_id', $request->rack_id)->max('ordem') + 1;

        $equipamento = Equipamento::create($validated);

        session()->flash('alert-success', 'Equipamento criado com sucesso!');
        return redirect()->route('racks.show', ['rack' => $equipamento->rack_id]);
    }

    public function show(Equipamento $equipamento)
    {
        Gate::authorize('admin');
        return view('equipamentos.show', ['equipamento' => $equipamento]);
    }

    public function edit(Equipamento $equipamento)
    {
        Gate::authorize('admin');

        return view('equipamentos.edit', [
            'equipamento' => $equipamento,
            'racks' => Rack::all(),
            'modelos' => ModeloSwitch::orderBy('fabricante')->orderBy('nome')->get(),
        ]);
    }

    public function update(EquipamentoRequest $request, Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $equipamento->update($request->validated());

        session()->flash('alert-success', 'Equipamento atualizado com sucesso!');
        return redirect()->route('equipamentos.show', ['equipamento' => $equipamento]);
    }

    public function destroy(Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $rack_id = $equipamento->rack_id;
        $equipamento->delete();

        session()->flash('alert-success', 'Equipamento removido com sucesso!');
        return redirect()->route('racks.show', ['rack' => $rack_id]);
    }
}
