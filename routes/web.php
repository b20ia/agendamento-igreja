<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgendamentoController;

Route::redirect('/', '/agendamento');
Route::get('/agendamento', [AgendamentoController::class, 'index'])->name('agendamento.index');

// API AJAX
Route::get('/api/dia/{dia}', [AgendamentoController::class, 'obterPorDia'])->name('agendamento.por-dia');
Route::get('/api/horarios', [AgendamentoController::class, 'horarios'])->name('agendamento.horarios');
Route::post('/api/agendar', [AgendamentoController::class, 'agendar'])->name('agendamento.agendar');
Route::post('/api/cancelar', [AgendamentoController::class, 'cancelar'])->name('agendamento.cancelar');
Route::post('/api/registrar-notificacao', [AgendamentoController::class, 'registrarNotificacao'])->name('agendamento.registrar-notificacao');
Route::post('/api/notificacao-proximidade', [AgendamentoController::class, 'enviarNotificacaoProximidade'])->name('agendamento.notificacao-proximidade');
