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

    public function index(Request $request): View|RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        $search = trim($request->query('q', ''));
        $filtroDia = trim($request->query('dia', ''));
        $filtroStatus = trim($request->query('status', ''));
        $equipe = trim($request->query('equipe', ''));

        $allAgendamentos = $this->baseQuery()->get();

        $agendamentos = $this->filteredQuery($request)->get();

        $resumo = [
            'total' => $allAgendamentos->count(),
            'ativos' => $allAgendamentos->where('status', 'ocupado')->where('cancelado', false)->count(),
            'cancelados' => $allAgendamentos->where('cancelado', true)->count(),
            'sexta' => $allAgendamentos->where('dia', 'sexta')->where('status', 'ocupado')->where('cancelado', false)->count(),
            'sabado' => $allAgendamentos->where('dia', 'sabado')->where('status', 'ocupado')->where('cancelado', false)->count(),
            'domingo' => $allAgendamentos->where('dia', 'domingo')->where('status', 'ocupado')->where('cancelado', false)->count(),
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

        return view('admin.index', compact(
            'agendamentos',
            'resumo',
            'notifications',
            'unreadNotifications',
            'search',
            'filtroDia',
            'filtroStatus',
            'equipe'
        ));
    }

    public function exportAgendamentos(Request $request)
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        $agendamentos = $this->filteredQuery($request)->get();
        $fileName = 'agendamentos_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($agendamentos) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Dia', 'Horário', 'Equipe', 'Responsável', 'Telefone', 'Status', 'Cancelado', 'Motivo de cancelamento']);

            foreach ($agendamentos as $agendamento) {
                fputcsv($handle, [
                    $agendamento->dia,
                    $agendamento->horario,
                    $agendamento->equipe,
                    $agendamento->responsavel,
                    $agendamento->telefone,
                    $agendamento->cancelado ? 'Cancelado' : ($agendamento->status === 'ocupado' ? 'Ativo' : 'Livre'),
                    $agendamento->cancelado ? 'Sim' : 'Não',
                    $agendamento->motivo_cancelamento ?: '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function reports(): View|RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        $agendamentos = $this->baseQuery()->get();

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

    public function notifications(): View|RedirectResponse
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('admin.login');
        }

        try {
            $notifications = AdminNotification::latest()->get();
            $unreadNotifications = AdminNotification::whereNull('read_at')->count();
        } catch (\Throwable $exception) {
            Log::warning('Admin notifications could not be loaded.', [
                'message' => $exception->getMessage(),
            ]);

            $notifications = collect();
            $unreadNotifications = 0;
        }

        return view('admin.notifications', compact('notifications', 'unreadNotifications'));
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

    /**
     * Query base de agendamentos ordenada por dia e horário.
     */
    private function baseQuery()
    {
        return Agendamento::orderByRaw("CASE dia WHEN 'sexta' THEN 1 WHEN 'sabado' THEN 2 WHEN 'domingo' THEN 3 ELSE 4 END")
            ->orderBy('horario');
    }

    /**
     * Aplica os filtros de busca da tela de admin (busca, dia, equipe, status).
     */
    private function filteredQuery(Request $request)
    {
        $search = trim($request->query('q', ''));
        $filtroDia = trim($request->query('dia', ''));
        $filtroStatus = trim($request->query('status', ''));
        $equipe = trim($request->query('equipe', ''));

        $query = $this->baseQuery();

        if ($filtroDia !== '') {
            $query->where('dia', $filtroDia);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('dia', 'like', "%{$search}%")
                    ->orWhere('horario', 'like', "%{$search}%")
                    ->orWhere('equipe', 'like', "%{$search}%")
                    ->orWhere('responsavel', 'like', "%{$search}%")
                    ->orWhere('telefone', 'like', "%{$search}%")
                    ->orWhere('motivo_cancelamento', 'like', "%{$search}%");
            });
        }

        if ($equipe !== '') {
            $query->where('equipe', 'like', "%{$equipe}%");
        }

        if ($filtroStatus === 'ativos') {
            $query->where('status', 'ocupado')->where('cancelado', false);
        } elseif ($filtroStatus === 'cancelados') {
            $query->where('cancelado', true);
        } elseif ($filtroStatus === 'livres') {
            $query->where('status', 'livre')->where('cancelado', false);
        }

        return $query;
    }
}
