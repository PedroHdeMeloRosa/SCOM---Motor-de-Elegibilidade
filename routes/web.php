<?php

use App\Http\Controllers\Coordenador\ImportacaoEditalIAController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Coordenador\GestaoEditalController;
use App\Http\Controllers\Diretor\GestaoUsuarioController;
use App\Http\Controllers\Estudante\EditalPublicoController;
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

    Route::get('/estudante/editais/{edital}', [EditalPublicoController::class, 'show'])
        ->name('estudante.editais.show');

});

// APIs sempre retornam JSON, inclusive quando a sessão expirou.
Route::middleware(['api.json', 'auth', 'perfil:ESTUDANTE'])->group(function () {
    Route::get('/api/estudante/painel', [PainelEstudanteController::class, 'obterDados'])
        ->name('api.estudante.painel');

    Route::post('/api/estudante/ficha', [PainelEstudanteController::class, 'salvarFicha'])
        ->name('api.estudante.ficha');
});

// Rotas Exclusivas da Coordenação (Protegidas por Sessão + RBAC)
Route::middleware(['auth', 'perfil:COORDENADOR'])->group(function () {
    Route::get('/coordenacao/dashboard', [GestaoEditalController::class, 'index'])->name('coordenacao.dashboard');
    Route::post('/coordenacao/editais', [GestaoEditalController::class, 'store'])->name('coordenacao.editais.store');
    Route::patch('/coordenacao/editais/{edital}', [GestaoEditalController::class, 'atualizar'])->name('coordenacao.editais.atualizar');
    Route::post('/coordenacao/editais/{edital}/publicar', [GestaoEditalController::class, 'publicar'])->name('coordenacao.editais.publicar');
    Route::post('/coordenacao/editais/{edital}/arquivar', [GestaoEditalController::class, 'arquivar'])->name('coordenacao.editais.arquivar');
    Route::post('/coordenacao/regras/{regra}/retificar', [GestaoEditalController::class, 'retificarRegra'])->name('coordenacao.regras.retificar');
    Route::post('/coordenacao/editais/escanear-ia', [ImportacaoEditalIAController::class, 'escanear'])
        ->name('coordenacao.editais.escanear-ia');
});

// Rotas Exclusivas da Diretoria (Protegidas por Sessão + RBAC)
Route::middleware(['auth', 'perfil:DIRETOR'])->group(function () {
    Route::get('/diretoria/dashboard', [GestaoUsuarioController::class, 'index'])->name('diretoria.dashboard');
    Route::patch('/diretoria/usuarios/{user}', [GestaoUsuarioController::class, 'atualizar'])->name('diretoria.usuarios.atualizar');
});