<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Agendamento;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function login(): View|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->route('admin.index');
        }

        return view('admin.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $adminPassword = config('services.admin.password');

        if (!$adminPassword || !hash_equals($adminPassword, $validated['password'])) {
            return back()
                ->withErrors(['password' => 'Senha inválida.'])
                ->onlyInput();
        }

        $request->session()->put('admin_authenticated', true);

        return redirect()->route('admin.index');
    }

    public function index(): View|RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        $agendamentos = Agendamento::orderByRaw("CASE dia WHEN 'sexta' THEN 1 WHEN 'sabado' THEN 2 WHEN 'domingo' THEN 3 ELSE 4 END")
            ->orderBy('horario')
            ->get();

        $resumo = [
            'total' => $agendamentos->count(),
            'ativos' => $agendamentos->where('status', 'ocupado')->where('cancelado', false)->count(),
            'cancelados' => $agendamentos->where('cancelado', true)->count(),
            'sexta' => $agendamentos->where('dia', 'sexta')->where('status', 'ocupado')->where('cancelado', false)->count(),
            'sabado' => $agendamentos->where('dia', 'sabado')->where('status', 'ocupado')->where('cancelado', false)->count(),
            'domingo' => $agendamentos->where('dia', 'domingo')->where('status', 'ocupado')->where('cancelado', false)->count(),
        ];

        try {
            $notifications = AdminNotification::latest()->limit(8)->get();
            $unreadNotifications = AdminNotification::whereNull('read_at')->count();
        } catch (\Throwable $exception) {
            Log::warning('Admin notifications could not be loaded.', [
                'message' => $exception->getMessage(),
            ]);

            $notifications = collect();
            $unreadNotifications = 0;
        }

        return view('admin.index', compact('agendamentos', 'resumo', 'notifications', 'unreadNotifications'));
    }

    public function reports(): View|RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        $agendamentos = Agendamento::orderByRaw("CASE dia WHEN 'sexta' THEN 1 WHEN 'sabado' THEN 2 WHEN 'domingo' THEN 3 ELSE 4 END")
            ->orderBy('horario')
            ->get();

        $ativos = $agendamentos->where('status', 'ocupado')->where('cancelado', false);
        $cancelados = $agendamentos->where('cancelado', true);

        $porDia = collect(['sexta', 'sabado', 'domingo'])->mapWithKeys(function ($dia) use ($ativos, $cancelados) {
            return [$dia => [
                'ativos' => $ativos->where('dia', $dia)->count(),
                'cancelados' => $cancelados->where('dia', $dia)->count(),
            ]];
        });

        $reports = [
            'total' => $agendamentos->count(),
            'ativos' => $ativos->count(),
            'cancelados' => $cancelados->count(),
            'taxa_cancelamento' => $agendamentos->count() > 0
                ? round(($cancelados->count() / $agendamentos->count()) * 100, 1)
                : 0,
            'por_dia' => $porDia,
            'cancelamentos_recentes' => $cancelados->sortByDesc('updated_at')->take(10),
        ];

        return view('admin.reports', compact('reports'));
    }

    public function settings(): View|RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        try {
            $adminPhone = Setting::getValue(
                'whatsapp_admin_phone',
                config('services.whatsapp.admin_phone')
            );
        } catch (\Throwable $exception) {
            Log::warning('Admin settings could not be loaded.', [
                'message' => $exception->getMessage(),
            ]);

            $adminPhone = config('services.whatsapp.admin_phone');
        }

        $settings = ['whatsapp_admin_phone' => $adminPhone];

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'whatsapp_admin_phone' => 'nullable|string|max:20',
        ]);

        try {
            Setting::setValue('whatsapp_admin_phone', $validated['whatsapp_admin_phone'] ?: null);
        } catch (\Throwable $exception) {
            Log::warning('Admin settings could not be saved.', [
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'whatsapp_admin_phone' => 'Não foi possível salvar agora. Verifique se as migrations foram executadas.',
            ]);
        }

        return back()->with('status', 'Configurações salvas com sucesso.');
    }

    public function markNotificationsAsRead(): RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        try {
            AdminNotification::whereNull('read_at')->update(['read_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Admin notifications could not be marked as read.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return back();
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_authenticated');

        return redirect()->route('admin.login');
    }

    private function isAuthenticated(): bool
    {
        return (bool) session('admin_authenticated');
    }
}
