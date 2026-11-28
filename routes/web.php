<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\PredioController;
use App\Http\Controllers\SalaController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\TelecomRoomController;
use App\Http\Controllers\PatchPanelController;
use App\Http\Controllers\TipoPortaController;
use App\Http\Controllers\TipoPortaSwitchController;
use App\Http\Controllers\PlantaController;
use App\Http\Controllers\PlantaPercursoController;
use App\Http\Controllers\ModeloSwitchController;
use App\Http\Controllers\ConexaoController;

Route::get('/',[IndexController::class,'index'])->name('home');

// Modelos de Switch (novo)
Route::get('/modelo-switches', [ModeloSwitchController::class, 'index']);
Route::get('/modelo-switches/create', [ModeloSwitchController::class, 'create']);
Route::post('/modelo-switches', [ModeloSwitchController::class, 'store']);
Route::get('/modelo-switches/{modeloSwitch}', [ModeloSwitchController::class, 'show']);
Route::get('/modelo-switches/{modeloSwitch}/edit', [ModeloSwitchController::class, 'edit']);
Route::put('/modelo-switches/{modeloSwitch}', [ModeloSwitchController::class, 'update']);
Route::delete('/modelo-switches/{modeloSwitch}', [ModeloSwitchController::class, 'destroy']);

// Prédios
Route::get('/predios', [PredioController::class, 'index'])->name('predios.index');
Route::get('/predios/create', [PredioController::class, 'create'])->name('predios.create');
Route::post('/predios', [PredioController::class, 'store'])->name('predios.store');
Route::get('/predios/{predio}', [PredioController::class, 'show'])->name('predios.show');
Route::get('/predios/{predio}/trs', [PredioController::class, 'trs'])->name('predios.trs');
Route::get('/predios/{predio}/salas', [PredioController::class, 'salas'])->name('predios.salas');
Route::get('/predios/{predio}/edit', [PredioController::class, 'edit'])->name('predios.edit');
Route::put('/predios/{predio}', [PredioController::class, 'update'])->name('predios.update');
Route::delete('/predios/{predio}', [PredioController::class, 'destroy'])->name('predios.destroy');

// Salas
Route::get('/salas/create', [SalaController::class, 'create'])->name('salas.create');
Route::post('/salas', [SalaController::class, 'store'])->name('salas.store');

Route::get('/salas/{planta}/markers', [SalaController::class, 'markers'])->name('salas.markers');
Route::post('/salas/{planta}/criar-e-marcar', [SalaController::class, 'criarEMarcar'])->name('salas.criar-e-marcar');
Route::delete('/salas/{sala}/unmark', [SalaController::class, 'unmark'])->name('salas.unmark');
Route::put('/salas/{sala}/{planta}/mark', [SalaController::class, 'mark'])->name('salas.mark');

Route::get('/salas/{sala}', [SalaController::class, 'show'])->name('salas.show');
Route::get('/salas/{sala}/edit', [SalaController::class, 'edit'])->name('salas.edit');
Route::put('/salas/{sala}', [SalaController::class, 'update'])->name('salas.update');
Route::delete('/salas/{sala}', [SalaController::class, 'destroy'])->name('salas.destroy');
Route::get('/salas/{planta}/mark', [PlantaController::class, 'mark'])->name('salas.planta.mark');


// Telecommunications Rooms (TR)
Route::get('/telecom-rooms/create', [TelecomRoomController::class, 'create'])->name('telecom-rooms.create');
Route::post('/telecom-rooms', [TelecomRoomController::class, 'store'])->name('telecom-rooms.store');
Route::get('/telecom-rooms/{telecomRoom}', [TelecomRoomController::class, 'show'])->name('telecom-rooms.show');
Route::get('/telecom-rooms/{telecomRoom}/edit', [TelecomRoomController::class, 'edit'])->name('telecom-rooms.edit');
Route::put('/telecom-rooms/{telecomRoom}', [TelecomRoomController::class, 'update'])->name('telecom-rooms.update');
Route::delete('/telecom-rooms/{telecomRoom}', [TelecomRoomController::class, 'destroy'])->name('telecom-rooms.destroy');

// Racks
Route::get('/racks/create', [RackController::class, 'create'])->name('racks.create');
Route::post('/racks', [RackController::class, 'store'])->name('racks.store');
Route::get('/racks/{rack}', [RackController::class, 'show'])->name('racks.show');
Route::get('/racks/{rack}/imprimir', [RackController::class, 'imprimir'])->name('racks.imprimir');
Route::get('/racks/{rack}/edit', [RackController::class, 'edit'])->name('racks.edit');
Route::put('/racks/{rack}', [RackController::class, 'update'])->name('racks.update');
Route::delete('/racks/{rack}', [RackController::class, 'destroy'])->name('racks.destroy');
Route::post('/racks/{rack}/reordenar', [RackController::class, 'reordenar']);

// Patch Panels
Route::get('/patch-panels/create', [PatchPanelController::class, 'create'])->name('patch-panels.create');
Route::post('/patch-panels', [PatchPanelController::class, 'store'])->name('patch-panels.store');
Route::get('/patch-panels/{patchPanel}', [PatchPanelController::class, 'show'])->name('patch-panels.show');
Route::get('/patch-panels/{patchPanel}/edit', [PatchPanelController::class, 'edit'])->name('patch-panels.edit');
Route::get('/patch-panels/{patchPanel}/excluir', [PatchPanelController::class, 'confirmarExclusao'])->name('patch-panels.excluir');
Route::put('/patch-panels/{patchPanel}', [PatchPanelController::class, 'update'])->name('patch-panels.update');
Route::delete('/patch-panels/{patchPanel}', [PatchPanelController::class, 'destroy'])->name('patch-panels.destroy');

// Equipamentos
Route::get('/equipamentos/create', [EquipamentoController::class, 'create'])->name('equipamentos.create');
Route::post('/equipamentos', [EquipamentoController::class, 'store'])->name('equipamentos.store');
Route::get('/equipamentos/{equipamento}', [EquipamentoController::class, 'show'])->name('equipamentos.show');
Route::get('/equipamentos/{equipamento}/edit', [EquipamentoController::class, 'edit'])->name('equipamentos.edit');
Route::get('/equipamentos/{equipamento}/excluir', [EquipamentoController::class, 'confirmarExclusao'])->name('equipamentos.excluir');
Route::put('/equipamentos/{equipamento}', [EquipamentoController::class, 'update'])->name('equipamentos.update');
// Tipo de uma porta do switch (JSON; tipo_porta_switch_id vazio remove o tipo)
Route::put('/equipamentos/{equipamento}/porta/{porta}/tipo', [EquipamentoController::class, 'salvarTipoPorta'])->whereNumber('porta')->name('equipamentos.porta.tipo');
Route::put('/equipamentos/{equipamento}/portas/tipo', [EquipamentoController::class, 'salvarTipoPortas'])->name('equipamentos.portas.tipo');
Route::delete('/equipamentos/{equipamento}', [EquipamentoController::class, 'destroy'])->name('equipamentos.destroy');

// Tipo Portas
Route::get('/tipo-portas', [TipoPortaController::class, 'index'])->name('tipo-portas.index');
Route::get('/tipo-portas/create', [TipoPortaController::class, 'create'])->name('tipo-portas.create');
Route::post('/tipo-portas', [TipoPortaController::class, 'store'])->name('tipo-portas.store');
Route::get('/tipo-portas/{tipoPorta}', [TipoPortaController::class, 'show'])->name('tipo-portas.show');
Route::get('/tipo-portas/{tipoPorta}/edit', [TipoPortaController::class, 'edit'])->name('tipo-portas.edit');
Route::put('/tipo-portas/{tipoPorta}', [TipoPortaController::class, 'update'])->name('tipo-portas.update');
Route::delete('/tipo-portas/{tipoPorta}', [TipoPortaController::class, 'destroy'])->name('tipo-portas.destroy');

// Tipo Portas de Switch
Route::get('/tipo-portas-switch', [TipoPortaSwitchController::class, 'index'])->name('tipo-portas-switch.index');
Route::get('/tipo-portas-switch/create', [TipoPortaSwitchController::class, 'create'])->name('tipo-portas-switch.create');
Route::post('/tipo-portas-switch', [TipoPortaSwitchController::class, 'store'])->name('tipo-portas-switch.store');
Route::get('/tipo-portas-switch/{tipoPortaSwitch}/edit', [TipoPortaSwitchController::class, 'edit'])->name('tipo-portas-switch.edit');
Route::put('/tipo-portas-switch/{tipoPortaSwitch}', [TipoPortaSwitchController::class, 'update'])->name('tipo-portas-switch.update');
Route::delete('/tipo-portas-switch/{tipoPortaSwitch}', [TipoPortaSwitchController::class, 'destroy'])->name('tipo-portas-switch.destroy');

// Plantas
Route::get('/plantas/public/{planta}', [PlantaController::class, 'showPublic'])->name('plantas.public');
Route::get('/plantas/pdf/{planta}', [PlantaController::class, 'pdfPublic'])->name('plantas.pdf');
Route::get('/plantas/imprimir/{planta}', [PlantaController::class, 'imprimir'])->name('plantas.imprimir');
Route::post('/plantas/{predio}', [PlantaController::class, 'store'])->name('plantas.store');
Route::get('/plantas/{planta}/edit', [PlantaController::class, 'edit'])->name('plantas.edit');
Route::get('/plantas/{planta}/mark', [PlantaController::class, 'editMark'])->name('plantas.mark');
// Camada de infraestrutura (eletrocalhas, canaletas, dutos) — antes de /plantas/{predio}/{planta}
Route::get('/plantas/{planta}/percursos', [PlantaPercursoController::class, 'index'])->name('plantas.percursos');
Route::post('/plantas/{planta}/percursos', [PlantaPercursoController::class, 'store'])->name('plantas.percursos.store');
Route::put('/percursos/{percurso}', [PlantaPercursoController::class, 'update'])->name('percursos.update');
Route::delete('/percursos/{percurso}', [PlantaPercursoController::class, 'destroy'])->name('percursos.destroy');
Route::delete('/plantas/{patch_panel_sala_id}/unmark', [PlantaController::class, 'unmark'])->name('plantas.unmark');
Route::put('/plantas/{planta}/mark', [PlantaController::class, 'mark'])->name('plantas.mark.update');
Route::put('/plantas/{planta}/config', [PlantaController::class, 'config'])->name('plantas.config');
Route::put('/plantas/{planta}', [PlantaController::class, 'update'])->name('plantas.update');
Route::get('/plantas/{predio}/{planta}', [PlantaController::class, 'show'])->name('plantas.show');
Route::get('/plantas/{predio}', [PlantaController::class, 'index'])->name('plantas.index');
Route::delete('/plantas/{predio}/{planta}', [PlantaController::class, 'destroy'])->name('plantas.destroy');

// Vincular portas de patch panels a salas
Route::get('/patch-panels/{patchPanel}/selecionar-sala', [PatchPanelController::class, 'selecionarSala'])->name('patch-panels.selecionar-sala');
Route::get('/patch-panels/{patchPanel}/selecionar-tipo-porta/{sala}', [PatchPanelController::class, 'selecionarTipoPorta'])->name('patch-panels.selecionar-tipo-porta');
Route::post('/patch-panels/{patchPanel}/vincular-sala', [PatchPanelController::class, 'vincularSala'])->name('patch-panels.vincular-sala');
Route::delete('/patch-panels/{patchPanel}/desvincular-sala/{sala}', [PatchPanelController::class, 'desvincularSala'])->name('patch-panels.desvincular-sala');
// Vínculo da porta direto pelo diagrama do rack (JSON)
Route::put('/patch-panels/{patchPanel}/porta/{porta}', [PatchPanelController::class, 'salvarPorta'])->whereNumber('porta')->name('patch-panels.porta.salvar');
Route::delete('/patch-panels/{patchPanel}/porta/{porta}', [PatchPanelController::class, 'removerPorta'])->whereNumber('porta')->name('patch-panels.porta.remover');

// Editar tipo de porta patch panels
Route::get('/patch-panels/{patchPanel}/editar-tipo-porta/{sala}', [PatchPanelController::class, 'editarTipoPorta'])->name('patch-panels.editar-tipo-porta');
Route::put('/patch-panels/{patchPanel}/atualizar-tipo-porta/{sala}', [PatchPanelController::class, 'atualizarTipoPorta'])->name('patch-panels.atualizar-tipo-porta');

// Conexões (patch cords) entre portas de switch e portas de patch panel
Route::post('/conexoes', [ConexaoController::class, 'store'])->name('conexoes.store');
Route::delete('/conexoes/{conexao}', [ConexaoController::class, 'destroy'])->name('conexoes.destroy');
