<?php

namespace App\Http\Controllers;

use App\Http\Requests\TelecomRoomRequest;
use App\Models\Predio;
use App\Models\TelecomRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TelecomRoomController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('admin');
        $predio = Predio::findOrFail($request->input('predio_id'));

        return view('telecom-rooms.form', [
            'tr' => new TelecomRoom(['predio_id' => $predio->id]),
            'predio' => $predio,
            'sugestao' => 'TR ' . ($predio->telecomRooms()->count() + 1),
        ]);
    }

    public function store(TelecomRoomRequest $request)
    {
        Gate::authorize('admin');
        $tr = TelecomRoom::create($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', "{$tr->nome} criada com sucesso!");
        return redirect()->route('predios.trs', ['predio' => $tr->predio_id]);
    }

    public function show(TelecomRoom $telecomRoom)
    {
        Gate::authorize('admin');
        return view('telecom-rooms.show', [
            'tr' => $telecomRoom->load('predio'),
            'racks' => $telecomRoom->racks()->withCount('equipamentos', 'patchPanels')->orderBy('nome')->get(),
        ]);
    }

    public function edit(TelecomRoom $telecomRoom)
    {
        Gate::authorize('admin');
        return view('telecom-rooms.form', [
            'tr' => $telecomRoom,
            'predio' => $telecomRoom->predio,
        ]);
    }

    public function update(TelecomRoomRequest $request, TelecomRoom $telecomRoom)
    {
        Gate::authorize('admin');
        $telecomRoom->update($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', "{$telecomRoom->nome} atualizada com sucesso!");
        return redirect()->route('telecom-rooms.show', ['telecomRoom' => $telecomRoom]);
    }

    public function destroy(TelecomRoom $telecomRoom)
    {
        Gate::authorize('admin');

        if ($telecomRoom->racks()->exists()) {
            session()->flash('alert-danger', "{$telecomRoom->nome} não pode ser removida, pois possui racks. Mova ou exclua os racks antes.");
            return back();
        }

        $predioId = $telecomRoom->predio_id;
        $telecomRoom->delete();
        session()->flash('alert-success', 'Telecommunications Room removida com sucesso!');
        return redirect()->route('predios.trs', ['predio' => $predioId]);
    }
}
