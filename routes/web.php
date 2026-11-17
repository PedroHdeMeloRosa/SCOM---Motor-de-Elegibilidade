<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PainelEstudanteController;
use Illuminate\Support\Facades\Route;

// Rota pública inicial
Route::get('/', function () {
    return view('index');
})->name('home');

// Rotas de Autenticação
Route::get('/login', [AuthController::class, 'exibirLogin'])->name('login');
Route::post('/login', [AuthController::class, 'autenticar'])->name('login.autenticar');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Rotas de Cadastro
Route::get('/cadastro', [AuthController::class, 'exibirCadastro'])->name('cadastro');
Route::post('/cadastro', [AuthController::class, 'registrar'])->name('cadastro.registrar');

// Rotas Exclusivas do Estudante (Protegidas por Sessão + RBAC)
Route::middleware(['auth', 'perfil:ESTUDANTE'])->group(function () {
    Route::get('/area-do-estudante', function () {
        return view('estudante.painel');
    })->name('estudante.painel');

    Route::get('/api/estudante/painel', [PainelEstudanteController::class, 'obterDados'])
        ->name('api.estudante.painel');

    Route::post('/api/estudante/ficha', [PainelEstudanteController::class, 'salvarFicha'])
        ->name('api.estudante.ficha');
});

// Rotas Exclusivas da Coordenação (Protegidas por Sessão + RBAC)
Route::middleware(['auth', 'perfil:COORDENADOR'])->group(function () {
    Route::get('/coordenacao/dashboard', function () {
        return view('editor.dashboard_coordenador');
    })->name('coordenacao.dashboard');
});

// Rotas Exclusivas da Diretoria (Protegidas por Sessão + RBAC)
Route::middleware(['auth', 'perfil:DIRETOR'])->group(function () {
    Route::get('/diretoria/dashboard', function () {
        return view('editor.dashboard_diretor');
    })->name('diretoria.dashboard');
});