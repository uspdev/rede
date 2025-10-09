<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SnapshotController;
use App\Http\Controllers\Api\EquipamentoController;
use App\Http\Controllers\Api\MacController;
use App\Http\Controllers\Api\PortaController;

Route::post('equipamentos',[EquipamentoController::class,'store']);

#Route::post('portas',[PortaController::class,'store']);
#Route::post('snapshots',[SnapshotController::class,'store']);
#Route::post('macs',[MacController::class,'store']);

