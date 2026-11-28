<?php

namespace App\Http\Controllers;

use App\Models\TipoPortaSwitch;
use App\Http\Requests\TipoPortaSwitchRequest;
use Illuminate\Support\Facades\Gate;

class TipoPortaSwitchController extends Controller
{
    public function index()
    {
        Gate::authorize('admin');
        return view('tipo-portas-switch.index', [
            'tipos' => TipoPortaSwitch::withCount('portas')->orderBy('nome')->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');
        return view('tipo-portas-switch.form', ['tipo' => new TipoPortaSwitch()]);
    }

    public function store(TipoPortaSwitchRequest $request)
    {
        Gate::authorize('admin');
        TipoPortaSwitch::create($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Tipo de porta de switch criado com sucesso!');
        return redirect()->route('tipo-portas-switch.index');
    }

    public function edit(TipoPortaSwitch $tipoPortaSwitch)
    {
        Gate::authorize('admin');
        return view('tipo-portas-switch.form', ['tipo' => $tipoPortaSwitch]);
    }

    public function update(TipoPortaSwitchRequest $request, TipoPortaSwitch $tipoPortaSwitch)
    {
        Gate::authorize('admin');
        $tipoPortaSwitch->update($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Tipo de porta de switch atualizado com sucesso!');
        return redirect()->route('tipo-portas-switch.index');
    }

    public function destroy(TipoPortaSwitch $tipoPortaSwitch)
    {
        Gate::authorize('admin');

        if ($tipoPortaSwitch->portas()->exists()) {
            session()->flash('alert-danger', 'Tipo de porta não pode ser removido, pois está em uso!');
        } else {
            $tipoPortaSwitch->delete();
            session()->flash('alert-success', 'Tipo de porta de switch removido com sucesso!');
        }
        return redirect()->route('tipo-portas-switch.index');
    }
}
