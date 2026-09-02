<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\PredioController;
use App\Http\Controllers\SalaController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\PatchPanelController;
use App\Http\Controllers\TipoPortaController; 
use App\Http\Controllers\PlantaController; 

Route::get('/',[IndexController::class,'index'])->name('home');

// Prédios
Route::get('/predios', [PredioController::class, 'index'])->name('predios.index');
Route::get('/predios/create', [PredioController::class, 'create'])->name('predios.create');
Route::post('/predios', [PredioController::class, 'store'])->name('predios.store');
Route::get('/predios/{predio}', [PredioController::class, 'show'])->name('predios.show');
Route::get('/predios/{predio}/edit', [PredioController::class, 'edit'])->name('predios.edit');
Route::put('/predios/{predio}', [PredioController::class, 'update'])->name('predios.update');
Route::delete('/predios/{predio}', [PredioController::class, 'destroy'])->name('predios.destroy');

// Salas
Route::get('/salas/create', [SalaController::class, 'create'])->name('salas.create');
Route::post('/salas', [SalaController::class, 'store'])->name('salas.store');

Route::get('/salas/{planta}/markers', [SalaController::class, 'markers'])->name('salas.markers');
Route::delete('/salas/{sala}/unmark', [SalaController::class, 'unmark'])->name('salas.unmark');
Route::put('/salas/{sala}/{planta}/mark', [SalaController::class, 'mark'])->name('salas.mark');

Route::get('/salas/{sala}', [SalaController::class, 'show'])->name('salas.show');
Route::get('/salas/{sala}/edit', [SalaController::class, 'edit'])->name('salas.edit');
Route::put('/salas/{sala}', [SalaController::class, 'update'])->name('salas.update');
Route::delete('/salas/{sala}', [SalaController::class, 'destroy'])->name('salas.destroy');
Route::get('/salas/{planta}/mark', [PlantaController::class, 'mark'])->name('salas.planta.mark');


// Racks
Route::get('/racks/create', [RackController::class, 'create'])->name('racks.create');
Route::post('/racks', [RackController::class, 'store'])->name('racks.store');
Route::get('/racks/{rack}', [RackController::class, 'show'])->name('racks.show');
Route::get('/racks/{rack}/edit', [RackController::class, 'edit'])->name('racks.edit');
Route::put('/racks/{rack}', [RackController::class, 'update'])->name('racks.update');
Route::delete('/racks/{rack}', [RackController::class, 'destroy'])->name('racks.destroy');

// Patch Panels
Route::get('/patch-panels/create', [PatchPanelController::class, 'create'])->name('patch-panels.create');
Route::post('/patch-panels', [PatchPanelController::class, 'store'])->name('patch-panels.store');
Route::get('/patch-panels/{patchPanel}', [PatchPanelController::class, 'show'])->name('patch-panels.show');
Route::get('/patch-panels/{patchPanel}/edit', [PatchPanelController::class, 'edit'])->name('patch-panels.edit');
Route::put('/patch-panels/{patchPanel}', [PatchPanelController::class, 'update'])->name('patch-panels.update');
Route::delete('/patch-panels/{patchPanel}', [PatchPanelController::class, 'destroy'])->name('patch-panels.destroy');

// Equipamentos
Route::get('/equipamentos/create', [EquipamentoController::class, 'create'])->name('equipamentos.create');
Route::post('/equipamentos', [EquipamentoController::class, 'store'])->name('equipamentos.store');
Route::get('/equipamentos/{equipamento}', [EquipamentoController::class, 'show'])->name('equipamentos.show');
Route::get('/equipamentos/{equipamento}/edit', [EquipamentoController::class, 'edit'])->name('equipamentos.edit');
Route::put('/equipamentos/{equipamento}', [EquipamentoController::class, 'update'])->name('equipamentos.update');
Route::delete('/equipamentos/{equipamento}', [EquipamentoController::class, 'destroy'])->name('equipamentos.destroy');

// Tipo Portas
Route::get('/tipo-portas', [TipoPortaController::class, 'index'])->name('tipo-portas.index');
Route::get('/tipo-portas/create', [TipoPortaController::class, 'create'])->name('tipo-portas.create');
Route::post('/tipo-portas', [TipoPortaController::class, 'store'])->name('tipo-portas.store');
Route::get('/tipo-portas/{tipoPorta}', [TipoPortaController::class, 'show'])->name('tipo-portas.show');
Route::get('/tipo-portas/{tipoPorta}/edit', [TipoPortaController::class, 'edit'])->name('tipo-portas.edit');
Route::put('/tipo-portas/{tipoPorta}', [TipoPortaController::class, 'update'])->name('tipo-portas.update');
Route::delete('/tipo-portas/{tipoPorta}', [TipoPortaController::class, 'destroy'])->name('tipo-portas.destroy');

// Plantas
Route::get('/plantas/public/{planta}', [PlantaController::class, 'showPublic'])->name('plantas.public');
Route::get('/plantas/pdf/{planta}', [PlantaController::class, 'pdfPublic'])->name('plantas.pdf');
Route::post('/plantas/{predio}', [PlantaController::class, 'store'])->name('plantas.store');
Route::get('/plantas/{planta}/edit', [PlantaController::class, 'edit'])->name('plantas.edit');
Route::get('/plantas/{planta}/mark', [PlantaController::class, 'editMark'])->name('plantas.mark');
Route::delete('/plantas/{patch_panel_sala_id}/unmark', [PlantaController::class, 'unmark'])->name('plantas.unmark');
Route::put('/plantas/{planta}/mark', [PlantaController::class, 'mark'])->name('plantas.mark.update');
Route::put('/plantas/{planta}', [PlantaController::class, 'update'])->name('plantas.update');
Route::get('/plantas/{predio}/{planta}', [PlantaController::class, 'show'])->name('plantas.show');
Route::get('/plantas/{predio}', [PlantaController::class, 'index'])->name('plantas.index');
Route::delete('/plantas/{predio}/{planta}', [PlantaController::class, 'destroy'])->name('plantas.destroy');

// Vincular portas de patch panels a salas
Route::get('/patch-panels/{patchPanel}/selecionar-sala', [PatchPanelController::class, 'selecionarSala'])->name('patch-panels.selecionar-sala');
Route::get('/patch-panels/{patchPanel}/selecionar-tipo-porta/{sala}', [PatchPanelController::class, 'selecionarTipoPorta'])->name('patch-panels.selecionar-tipo-porta');
Route::post('/patch-panels/{patchPanel}/vincular-sala', [PatchPanelController::class, 'vincularSala'])->name('patch-panels.vincular-sala');
Route::delete('/patch-panels/{patchPanel}/desvincular-sala/{sala}', [PatchPanelController::class, 'desvincularSala'])->name('patch-panels.desvincular-sala');

// Editar tipo de porta patch panels
Route::get('/patch-panels/{patchPanel}/editar-tipo-porta/{sala}', [PatchPanelController::class, 'editarTipoPorta'])->name('patch-panels.editar-tipo-porta');
Route::put('/patch-panels/{patchPanel}/atualizar-tipo-porta/{sala}', [PatchPanelController::class, 'atualizarTipoPorta'])->name('patch-panels.atualizar-tipo-porta');

// Vincular salas a patch panels
Route::get('/salas/{sala}/selecionar-rack', [SalaController::class, 'selecionarRack'])->name('salas.selecionar-rack');
Route::get('/salas/{sala}/selecionar-patchpanel/{rack}', [SalaController::class, 'selecionarPatchPanel'])->name('salas.selecionar-patchpanel');
Route::post('/salas/{sala}/vincular-patchpanel', [SalaController::class, 'vincularPatchPanel'])->name('salas.vincular-patchpanel');
Route::delete('/salas/{sala}/desvincular-patchpanel/{patchPanel}', [SalaController::class, 'desvincularPatchPanel'])->name('salas.desvincular-patchpanel');

// Editar tipo de porta sakas 
Route::get('/salas/{sala}/editar-tipo-porta/{patchPanel}', [SalaController::class, 'editarTipoPorta'])->name('salas.editar-tipo-porta');
Route::put('/salas/{sala}/atualizar-tipo-porta/{patchPanel}', [SalaController::class, 'atualizarTipoPorta'])->name('salas.atualizar-tipo-porta');
