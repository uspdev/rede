<?php

namespace App\Http\Controllers;

use App\Models\Planta;
use App\Models\PlantaPercurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Camada de infraestrutura da planta (eletrocalhas, canaletas, dutos).
 */
class PlantaPercursoController extends Controller
{
    /** Editor da camada */
    public function index(Planta $planta)
    {
        Gate::authorize('admin');

        $planta->load([
            'predio',
            'percursos',
            'markers' => fn ($q) => $q->whereNotNull('x')->whereNotNull('y')->where('visible', true)->with(['patchPanel.rack', 'sala', 'tipoPorta']),
            'salas' => fn ($q) => $q->whereNotNull('x')->whereNotNull('y'),
        ]);

        return view('plantas.percursos', ['planta' => $planta]);
    }

    public function store(Request $request, Planta $planta)
    {
        Gate::authorize('admin');

        $percurso = $planta->percursos()->create($this->validar($request) + ['user_id' => auth()->id()]);

        return response()->json(['percurso' => $percurso->paraJs()], 201);
    }

    public function update(Request $request, PlantaPercurso $percurso)
    {
        Gate::authorize('admin');

        $percurso->update($this->validar($request, parcial: true) + ['user_id' => auth()->id()]);

        return response()->json(['percurso' => $percurso->fresh()->paraJs()]);
    }

    public function destroy(PlantaPercurso $percurso)
    {
        Gate::authorize('admin');

        $percurso->delete();

        return response()->json(['success' => true]);
    }

    private function validar(Request $request, bool $parcial = false): array
    {
        $obrigatorio = $parcial ? 'sometimes' : 'required';

        $dados = $request->validate([
            'tipo' => [$obrigatorio, Rule::in(array_keys(PlantaPercurso::TIPOS))],
            'pontos' => [$obrigatorio, 'array', 'min:2', 'max:500'],
            'pontos.*' => ['array', 'size:2'],
            'pontos.*.*' => ['numeric', 'between:-5,105'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ], [
            'pontos.min' => 'O percurso precisa de pelo menos 2 pontos.',
        ]);

        if (isset($dados['pontos'])) {
            $dados['pontos'] = array_map(fn ($p) => [round((float) $p[0], 3), round((float) $p[1], 3)], $dados['pontos']);
        }

        return $dados;
    }
}
