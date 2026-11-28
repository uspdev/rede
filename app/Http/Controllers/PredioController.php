<?php

namespace App\Http\Controllers;

use App\Models\Predio;
use App\Http\Requests\PredioRequest;
use Illuminate\Support\Facades\Gate;

class PredioController extends Controller
{
    public function index()
    {
        Gate::authorize('admin');
        return view('predios.index',[
            'predios' => Predio::withCount(['salas', 'plantas', 'telecomRooms'])
                ->orderBy('nome')
                ->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('admin');
        return view('predios.create');
    }

    public function store(PredioRequest $request)
    {
        Gate::authorize('admin');
        Predio::create($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Prédio criado com sucesso!');
        return redirect()->route('predios.index');
    }

    public function show(Predio $predio)
    {
        Gate::authorize('admin');
        return view('predios.show', [
            'predio' => $predio->loadCount(['salas', 'plantas', 'telecomRooms']),
        ]);
    }

    /** Só as TRs do prédio e os racks de cada uma */
    public function trs(Predio $predio)
    {
        Gate::authorize('admin');
        return view('predios.trs', [
            'predio' => $predio,
            'trs' => $predio->telecomRooms()
                ->with(['racks' => fn ($q) => $q->withCount('equipamentos', 'patchPanels')->orderBy('nome')])
                ->orderBy('nome')
                ->get(),
            // racks que (ainda) não estão em nenhuma TR
            'racksSemTr' => $predio->racks()->whereNull('telecom_room_id')->orderBy('nome')->get(),
        ]);
    }

    /** Só as salas do prédio */
    public function salas(Predio $predio)
    {
        Gate::authorize('admin');
        return view('predios.salas', [
            'predio' => $predio,
            'salas' => $predio->salas()->orderBy('nome')->get(),
        ]);
    }

    public function edit(Predio $predio)
    {
        Gate::authorize('admin');
        return view('predios.edit', ['predio' => $predio]);
    }

    public function update(PredioRequest $request, Predio $predio)
    {
        Gate::authorize('admin');
        $predio->update($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Prédio atualizado com sucesso!');
        return redirect()->route('predios.index');
    }

    public function destroy(Predio $predio)
    {
        Gate::authorize('admin');

        if ($predio->plantas->count()) {
            session()->flash('alert-danger', 'Prédio não deletado, pois possui plantas cadastradas!');
            return back();
        }

        if($predio->salas->isEmpty() && $predio->racks->isEmpty() && $predio->telecomRooms->isEmpty()) {
            $predio->delete();
            session()->flash('alert-success', 'Prédio removido com sucesso!');
        } else {
            session()->flash('alert-danger', 'Prédio não deletado, pois possui salas/ambientes, Telecommunications Rooms ou racks cadastrados!');
        }
        return redirect()->route('predios.index');
    }
}
