<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AdminController;

Route::redirect('/', '/agendamento');
Route::get('/agendamento', [AgendamentoController::class, 'index'])->name('agendamento.index');

Route::get('/admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'authenticate'])
    ->middleware('throttle:5,1')
    ->name('admin.authenticate');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::get('/admin/inscricoes/exportar', [AdminController::class, 'exportAgendamentos'])->name('admin.agendamentos.export');
Route::get('/admin/notificacoes', [AdminController::class, 'notifications'])->name('admin.notifications');
Route::get('/admin/relatorios', [AdminController::class, 'reports'])->name('admin.reports');
Route::get('/admin/configuracoes', [AdminController::class, 'settings'])->name('admin.settings');
Route::post('/admin/configuracoes', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
Route::post('/admin/notificacoes/marcar-lidas', [AdminController::class, 'markNotificationsAsRead'])->name('admin.notifications.read');

// API AJAX
Route::get('/api/dia/{dia}', [AgendamentoController::class, 'obterPorDia'])->name('agendamento.por-dia');
Route::get('/api/horarios', [AgendamentoController::class, 'horarios'])->name('agendamento.horarios');

Route::middleware('throttle:20,1')->group(function () {
    Route::post('/api/agendar', [AgendamentoController::class, 'agendar'])->name('agendamento.agendar');
    Route::post('/api/cancelar', [AgendamentoController::class, 'cancelar'])->name('agendamento.cancelar');
});
