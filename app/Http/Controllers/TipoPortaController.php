<?php

namespace App\Http\Controllers;

use App\Models\TipoPorta;
use App\Http\Requests\TipoPortaRequest;
use Illuminate\Support\Facades\Gate;

class TipoPortaController extends Controller
{
    public function index()
    {
        Gate::authorize('admin');
        return view('tipo-portas.index', [
            'tipoPortas' => TipoPorta::all(),
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');
        return view('tipo-portas.create');
    }

    public function store(TipoPortaRequest $request)
    {
        Gate::authorize('admin');
        TipoPorta::create($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Tipo de porta criado com sucesso!');
        return redirect()->route('tipo-portas.index');
    }

    public function show(TipoPorta $tipoPorta)
    {
        Gate::authorize('admin');
        return view('tipo-portas.show', [
            'tipoPorta' => $tipoPorta,
        ]);
    }

    public function edit(TipoPorta $tipoPorta)
    {
        Gate::authorize('admin');
        return view('tipo-portas.edit', [
            'tipoPorta' => $tipoPorta,
        ]);
    }

    public function update(TipoPortaRequest $request, TipoPorta $tipoPorta)
    {
        Gate::authorize('admin');
        $tipoPorta->update($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Tipo de porta atualizado com sucesso!');
        return redirect()->route('tipo-portas.index');
    }

    public function destroy(TipoPorta $tipoPorta)
    {
        Gate::authorize('admin');
        
        if($tipoPorta->patchPanelSalas->isEmpty()) {
            $tipoPorta->delete();
            session()->flash('alert-success', 'Tipo de porta removido com sucesso!');
        } else {
            session()->flash('alert-danger', 'Tipo de porta não pode ser removido, pois está em uso!');
        }
        return redirect()->route('tipo-portas.index');
    }
}
