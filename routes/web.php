<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PainelEstudanteController;

Route::get('/', function () {
    return view('index');
});

Route::get('/area-do-estudante', function () {
    return view('estudante.painel');
})->name('estudante.painel');

Route::get('/login', function () {
    return view('estudante.login');
})->name('login');

Route::post('/login', [AuthController::class, 'autenticar'])->name('login.autenticar');


use App\Models\Edital;
Route::get('/api/estudante/painel', [PainelEstudanteController::class, 'obterDados']);

Route::post('/api/estudante/ficha', [PainelEstudanteController::class, 'salvarFicha']);

Route::post('/logout', function (Illuminate\Http\Request $request) {
    Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->json(['sucesso' => true]);
})->name('logout');