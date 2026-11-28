<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planta;
use App\Models\Sala;
use App\Http\Requests\PlantaRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Predio;
use Illuminate\Support\Facades\Storage;
use App\Models\PatchPanelSala;
use App\Models\PatchPanel;
use App\Models\TipoPorta;

class PlantaController extends Controller
{
    public function index(Predio $predio)
    {
        Gate::authorize('admin');

        $predio->load([
            'plantas.markers' => function ($query) {
                $query->whereNotNull('x')->whereNotNull('y')->with(['patchPanel.rack', 'sala']);
            },
            // Carrega apenas as salas que possuem marcação (x e y preenchidos)
            'plantas.salas' => function ($query) {
                $query->whereNotNull('x')->whereNotNull('y');
            }
        ]);
        
        return view('plantas.index', [
            'predio' => $predio,
        ]);
    }

    public function store(PlantaRequest $request, Predio $predio)
    {
        Gate::authorize('admin');
        $file = $request->file('planta');
        $path = $file->store('plantas');

        $planta = new Planta();
        $planta->predio_id = $request->input('predio_id');
        $planta->path = $path;
        $planta->name = $request->input('name');
        $planta->original_name = $file->getClientOriginalName();
        $planta->save();

        return redirect()->back()->with('success', 'Planta enviada com sucesso!');
    }

    public function edit(Planta $planta)
    {
        Gate::authorize('admin');

        // Carrega e ordena os pontos marcados nesta planta
        $pontos = PatchPanelSala::with(['patchPanel.rack', 'sala'])
            ->where('planta_id', $planta->id)
            ->get()
            ->sortBy([
                fn ($a, $b) => (optional(optional($a->patchPanel)->rack)->nome ?? '') <=> (optional(optional($b->patchPanel)->rack)->nome ?? ''),
                fn ($a, $b) => (optional($a->patchPanel)->nome ?? '') <=> (optional($b->patchPanel)->nome ?? ''),
                fn ($a, $b) => ($a->porta ?? 0) <=> ($b->porta ?? 0),
            ])
            ->values(); // Reindexa a coleção

        return view('plantas.edit', [
            'planta' => $planta,
            'pontos' => $pontos,
        ]);
    }

    public function update(PlantaRequest $request, Planta $planta)
    {
        Gate::authorize('admin');

        $planta->name = $request->input('name');
        $planta->predio_id = $request->input('predio_id');

        if ($request->hasFile('planta')) {
            if ($planta->path && Storage::exists($planta->path)) {
                Storage::delete($planta->path);
            }

            $file = $request->file('planta');
            $planta->path = $file->store('plantas');
            $planta->original_name = $file->getClientOriginalName();
        }

        $planta->save();

        PatchPanelSala::where('planta_id', $planta->id)->update(['visible' => false]);

        $pontosVisiveis = $request->input('pontos_visiveis', []);
        if (!empty($pontosVisiveis)) {
            PatchPanelSala::whereIn('id', $pontosVisiveis)
                ->where('planta_id', $planta->id)
                ->update(['visible' => true]);
        }

        return redirect()->route('plantas.index', ['predio' => $planta->predio_id])
            ->with('success', 'Planta e permissões dos pontos atualizadas com sucesso!');
    }

    /** Imagem da planta: pública (usada pela visualização e pela impressão) */
    public function show(Predio $predio, Planta $planta)
    {
        return Storage::download($planta->path, $planta->original_name);
    }

    /** Visualização da planta: pública, só leitura (anônimo pode ver) */
    public function showPublic(Planta $planta)
    {

        $planta->load('predio');

        // Carrega os pontos visíveis ordenados por Sala e depois por Rack -> Patch Panel -> Porta
        $markers = PatchPanelSala::where('planta_id', $planta->id)
            ->where('visible', true)
            ->with(['patchPanel.rack', 'sala', 'tipoPorta'])
            ->get()
            ->sortBy([
                fn ($a, $b) => (optional($a->sala)->nome ?? '') <=> (optional($b->sala)->nome ?? ''),
                fn ($a, $b) => (optional(optional($a->patchPanel)->rack)->nome ?? '') <=> (optional(optional($b->patchPanel)->rack)->nome ?? ''),
                fn ($a, $b) => (optional($a->patchPanel)->nome ?? '') <=> (optional($b->patchPanel)->nome ?? ''),
                fn ($a, $b) => ($a->porta ?? 0) <=> ($b->porta ?? 0),
            ])
            ->values();

        return view('plantas.public_show', [
            'planta'  => $planta,
            'percursos' => $planta->percursos()->orderBy('id')->get(),
            'markers' => $markers,
        ]);
    }

    public function editMark(Planta $planta)
    {
        Gate::authorize('admin');

        $predio_id = $planta->predio_id;

        $salasMarkerd = Sala::where('planta_id', $planta->id)
            ->whereNotNull('x')
            ->whereNotNull('y')
            ->get();

        // pontos já marcados na planta (com coordenadas x e y)
        $markers = PatchPanelSala::with(['patchPanel.rack', 'sala'])
            ->where('planta_id', $planta->id)
            ->whereNotNull('x')
            ->whereNotNull('y')
            ->get();

        // pontos do MESMO PRÉDIO que ainda estão sem coordenadas (x e y nulos):
        // pela sala vinculada ou pelo rack do patch panel
        $pontosSemMarcacao = PatchPanelSala::with(['patchPanel.rack', 'sala', 'tipoPorta'])
            ->where(function ($q) use ($predio_id) {
                $q->whereHas('sala', fn ($s) => $s->where('predio_id', $predio_id))
                  ->orWhereHas('patchPanel.rack', fn ($r) => $r->where('predio_id', $predio_id));
            })
            ->whereNull('x')
            ->whereNull('y')
            ->get()
            ->sortBy([
                fn ($a, $b) => (optional(optional($a->patchPanel)->rack)->nome ?? '') <=> (optional(optional($b->patchPanel)->rack)->nome ?? ''),
                fn ($a, $b) => (optional($a->patchPanel)->nome ?? '') <=> (optional($b->patchPanel)->nome ?? ''),
                fn ($a, $b) => ($a->porta ?? 0) <=> ($b->porta ?? 0),
            ])
            ->values(); // Reindexa a coleção

        // Portas dos patch panels do prédio que ainda não têm sala vinculada
        $patchPanelsPredio = PatchPanel::with('rack')
            ->whereHas('rack', fn ($r) => $r->where('predio_id', $predio_id))
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->rack->nome ?? '') <=> ($b->rack->nome ?? ''),
                fn ($a, $b) => $a->nome <=> $b->nome,
            ]);
        $ocupadas = PatchPanelSala::whereIn('patch_panel_id', $patchPanelsPredio->pluck('id'))
            ->get(['patch_panel_id', 'porta'])
            ->map(fn ($v) => $v->patch_panel_id . ':' . $v->porta)
            ->flip();
        $portasSemSala = [];
        foreach ($patchPanelsPredio as $pp) {
            for ($porta = 1; $porta <= $pp->qtde_portas; $porta++) {
                if (!$ocupadas->has($pp->id . ':' . $porta)) {
                    $portasSemSala[] = [
                        'pp' => $pp->id,
                        'porta' => $porta,
                        'nome' => ($pp->rack->nome ?? '') . '-' . $pp->nome . '-' . $porta,
                    ];
                }
            }
        }

        $salasPredio = Sala::where('predio_id', $predio_id)->orderBy('nome')->get(['id', 'nome', 'x', 'y', 'planta_id']);

        // Busca todos os tipos de porta disponíveis
        $tipoPortas = TipoPorta::all();

        return view('plantas.mark', [
            'planta' => $planta,
            'markers' => $markers,
            'salasMarkerd' => $salasMarkerd,
            'pontosSemMarcacao' => $pontosSemMarcacao,
            'portasSemSala' => $portasSemSala,
            'salasPredio' => $salasPredio,
            'tipoPortas' => $tipoPortas, // Passado para o Blade
        ]);
    }

    public function mark(Planta $planta, Request $request)
    {
        Gate::authorize('admin');

        // Tornamos os campos de formulário opcionais no request para requisições via Drag & Drop
        $validated = $request->validate([
            'patch_panel_sala_id' => 'required|exists:patch_panel_sala,id',
            'planta_id'           => 'required|exists:plantas,id',
            'x'                   => 'required|numeric',
            'y'                   => 'required|numeric',
            'fontsize'            => 'nullable|integer|min:2|max:50',
            'label_position'      => 'nullable|in:left,right,top,bottom,livre',
            'label_dx'            => 'nullable|numeric|between:-60,60',
            'label_dy'            => 'nullable|numeric|between:-60,60',
        ]);

        $ponto = PatchPanelSala::findOrFail($validated['patch_panel_sala_id']);

        // Monta o array com as coordenadas enviadas
        $dadosParaAtualizar = [
            'x'         => $validated['x'],
            'y'         => $validated['y'],
            'planta_id' => $validated['planta_id'],
        ];

        // Atualiza os outros campos SOMENTE se eles estiverem presentes no request
        if ($request->has('fontsize')) {
            $dadosParaAtualizar['fontsize'] = $request->fontsize;
        }
        if ($request->has('tipo_porta_id')) {
            $dadosParaAtualizar['tipo_porta_id'] = $request->tipo_porta_id;
        }
        if ($request->has('comentario')) {
            $dadosParaAtualizar['comentario'] = $request->comentario;
        }
        if ($request->has('tamanho')) {
            $dadosParaAtualizar['tamanho'] = $request->tamanho;
        }
        if ($request->filled('label_position')) {
            $dadosParaAtualizar['label_position'] = $request->label_position;
        }
        if ($request->filled('label_dx') && $request->filled('label_dy')) {
            $dadosParaAtualizar['label_dx'] = $validated['label_dx'];
            $dadosParaAtualizar['label_dy'] = $validated['label_dy'];
        }

        // Executa a atualização sem alterar os dados não enviados
        $ponto->update($dadosParaAtualizar);

        // Retorna JSON para chamadas AJAX/Fetch (Drag & Drop)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Posição atualizada com sucesso!',
                'data'    => $ponto
            ], 200);
        }

        return redirect()->back()->with('success', 'Ponto atualizado com sucesso!');
    }

    /**
     * Configurações de exibição da planta (tamanho global das marcações e recorte).
     */
    public function config(Planta $planta, Request $request)
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'escala_marcadores' => 'sometimes|numeric|between:0.2,5',
            'recorte'           => 'sometimes|nullable|array',
            'recorte.x'         => 'required_with:recorte|numeric|between:0,100',
            'recorte.y'         => 'required_with:recorte|numeric|between:0,100',
            'recorte.w'         => 'required_with:recorte|numeric|between:1,100',
            'recorte.h'         => 'required_with:recorte|numeric|between:1,100',
        ]);

        if (array_key_exists('escala_marcadores', $validated)) {
            $planta->escala_marcadores = $validated['escala_marcadores'];
        }
        if ($request->exists('recorte')) {
            $planta->recorte = $validated['recorte'] ?? null;
        }
        $planta->save();

        return response()->json([
            'success' => true,
            'escala_marcadores' => $planta->escala_marcadores,
            'recorte' => $planta->recorteValido(),
        ]);
    }

    public function destroy(Predio $predio, Planta $planta)
    {
        Gate::authorize('admin');

        if (PatchPanelSala::where('planta_id', $planta->id)->get()->isNotEmpty()) {
            session()->flash('alert-danger', 'Planta não pode ser removida, pois tem pontos marcados!');
            return back();
        }

        if (Sala::where('planta_id', $planta->id)->get()->isNotEmpty()) {
            session()->flash('alert-danger', 'Planta não pode ser removida, pois tem salas/ambientes marcadas!');
            return back();
        }

        // Remove o arquivo da imagem do storage
        if ($planta->path && Storage::exists($planta->path)) {
            Storage::delete($planta->path);
        }

        // Deleta o registro da planta
        $planta->delete();
        return back()->with('success', 'Planta e marcações removidas com sucesso!');
    }

    public function unmark($patch_panel_sala_id)
    {
        Gate::authorize('admin');

        // Zera as coordenadas x e y de todas as marcações vinculadas a esta planta
        PatchPanelSala::where('id', $patch_panel_sala_id)->update([
            'x' => null,
            'y' => null,
            'planta_id' => null,
        ]);

        return back()->with('success', 'Ponto removido com sucesso!');
    }

    /**
     * Versão para impressão: o navegador desenha a planta (SVG vetorial) e as marcações
     * por cima, usando as mesmas coordenadas em % salvas no banco.
     */
    /** Versão para impressão / PDF: pública, só leitura (anônimo pode ver) */
    public function imprimir(Planta $planta)
    {

        $planta->load('predio');

        $markers = PatchPanelSala::where('planta_id', $planta->id)
            ->where('visible', true)
            ->whereNotNull('x')
            ->whereNotNull('y')
            ->with(['patchPanel.rack', 'sala', 'tipoPorta'])
            ->get()
            ->sortBy([
                fn ($a, $b) => (optional($a->sala)->nome ?? '') <=> (optional($b->sala)->nome ?? ''),
                fn ($a, $b) => (optional(optional($a->patchPanel)->rack)->nome ?? '') <=> (optional(optional($b->patchPanel)->rack)->nome ?? ''),
                fn ($a, $b) => (optional($a->patchPanel)->nome ?? '') <=> (optional($b->patchPanel)->nome ?? ''),
                fn ($a, $b) => ($a->porta ?? 0) <=> ($b->porta ?? 0),
            ])
            ->values();

        $salas = Sala::where('planta_id', $planta->id)
            ->whereNotNull('x')
            ->whereNotNull('y')
            ->get();

        return view('plantas.imprimir', [
            'planta' => $planta,
            'percursos' => $planta->percursos()->orderBy('id')->get(),
            'markers' => $markers,
            'salas' => $salas,
            'viewBox' => $planta->viewBox(),
            'publicUrl' => route('plantas.public', ['planta' => $planta]),
        ]);
    }

    public function pdfPublic(Planta $planta)
    {
        // O PDF gerado pelo DomPDF não renderizava o SVG da planta corretamente.
        // Mantém o link antigo funcionando, mandando para a versão de impressão.
        return redirect()->route('plantas.imprimir', ['planta' => $planta]);
    }
}
