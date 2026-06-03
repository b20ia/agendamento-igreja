<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Agendamento;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgendamentoController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    /**
     * Mostra a página principal
     */
    public function index()
    {
        $dias = ['sexta', 'sabado', 'domingo'];
        $equipesPadrao = config('agendamento.equipes', []);

        return view('agendamento.index', compact('dias', 'equipesPadrao'));
    }

    /**
     * Retorna os agendamentos de um dia (AJAX)
     */
    public function obterPorDia($dia)
    {
        $agendamentos = Agendamento::where('dia', $dia)->get();

        return response()->json($agendamentos);
    }

    /**
     * Registra um novo agendamento (AJAX)
     */
    public function agendar(Request $request)
    {
        $validated = $request->validate([
            'dia' => 'required|in:sexta,sabado,domingo',
            'horario' => 'required|string',
            'equipe' => 'required|string|max:100',
            'responsavel' => 'required|string|max:100',
            'telefone' => 'required|string|max:20',
        ]);

        if (!$this->agendamentoEstaAberto()) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Os agendamentos estão fechados neste horário.',
            ], 403);
        }

        if (Agendamento::equipeJaAgendada($validated['equipe'])) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Esta equipe já possui um agendamento ativo. Para escolher outro horário, cancele o agendamento atual primeiro.',
            ], 409);
        }

        // Verifica disponibilidade do horário
        if (!Agendamento::verificarDisponibilidade($validated['dia'], $validated['horario'])) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Este horário já foi reservado!',
            ], 409);
        }

        // Tenta reservar o horário
        $agendamento = Agendamento::reservar(
            $validated['dia'],
            $validated['horario'],
            $validated['equipe'],
            $validated['responsavel'],
            $validated['telefone']
        );

        if ($agendamento) {
            $this->whatsApp->sendConfirmation($agendamento);
            $agendamento->registrarNotificacao('confirmacao');

            $this->createAdminNotification($agendamento, 'booking');

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Agendamento realizado com sucesso!',
                'agendamento' => $agendamento->fresh(),
            ]);
        }

        return response()->json([
            'sucesso' => false,
            'mensagem' => 'Erro ao realizar o agendamento.',
        ], 500);
    }

    /**
     * Cancela um agendamento (AJAX)
     */
    public function cancelar(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:agendamentos,id',
            'motivo' => 'required|string|max:255',
        ]);

        $agendamento = Agendamento::find($validated['id']);

        if (!$agendamento || $agendamento->status !== 'ocupado') {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Agendamento não encontrado ou já foi cancelado.',
            ], 404);
        }

        $agendamento->cancelar($validated['motivo']);

        $this->whatsApp->sendCancellation($agendamento);
        $agendamento->registrarNotificacao('cancelamento');

        $this->createAdminNotification($agendamento, 'cancellation');

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Agendamento cancelado com sucesso!',
            'agendamento' => $agendamento->fresh(),
        ]);
    }

    /**
     * Registra notificação de proximidade (chamado pelo JS)
     */
    public function enviarNotificacaoProximidade(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:agendamentos,id',
        ]);

        $agendamento = Agendamento::find($validated['id']);

        if (!$agendamento || $agendamento->cancelado) {
            return response()->json([
                'sucesso' => false,
            ], 404);
        }

        if ($agendamento->notificacaoJaEnviada('proximidade')) {
            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Notificação de proximidade já enviada.',
            ]);
        }

        if ($this->whatsApp->sendProximityReminder($agendamento)) {
            $agendamento->registrarNotificacao('proximidade');
        }

        return response()->json([
            'sucesso' => true,
        ]);
    }

    /**
     * Registra notificação enviada (AJAX)
     */
    public function registrarNotificacao(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:agendamentos,id',
            'tipo' => 'required|in:confirmacao,proximidade,cancelamento',
        ]);

        $agendamento = Agendamento::find($validated['id']);
        $agendamento->registrarNotificacao($validated['tipo']);

        return response()->json([
            'sucesso' => true,
        ]);
    }

    /**
     * Retorna os horários disponíveis para um dia
     */
    public function horarios()
    {
        return response()->json($this->obterHorariosPorDia());
    }

    /**
     * Define os horários disponíveis
     */
    private function obterHorariosPorDia()
    {
        return [
            'sexta' => [
                '19:00', '19:30', '20:00', '20:30', '21:00', '21:30'
            ],
            'sabado' => [
                '07:30', '08:00', '08:30', '09:00', '09:30', '10:00',
                '10:30', '11:00', '11:30', '12:00', '12:30', '13:00',
                '13:30', '14:00', '14:30', '15:30', '16:00', '16:30',
                '17:00', '17:30', '18:00'
            ],
            'domingo' => [
                '07:30', '08:00', '08:30', '09:00', '09:30', '10:00',
                '10:30', '11:00', '11:30', '12:00', '12:30', '13:00',
                '13:30', '14:00', '14:30', '15:30'
            ],
        ];
    }

    /**
     * Define a janela geral em que novos agendamentos podem ser feitos.
     */
    private function agendamentoEstaAberto(): bool
    {
        $agora = Carbon::now('America/Fortaleza');

        return $agora->lt(Carbon::create(2026, 7, 24, 21, 0, 0, 'America/Fortaleza'))
            || $agora->between(
                Carbon::create(2026, 7, 25, 7, 0, 0, 'America/Fortaleza'),
                Carbon::create(2026, 7, 25, 20, 0, 0, 'America/Fortaleza'),
                false
            )
            || $agora->between(
                Carbon::create(2026, 7, 26, 7, 0, 0, 'America/Fortaleza'),
                Carbon::create(2026, 7, 26, 15, 0, 0, 'America/Fortaleza'),
                false
            );
    }

    private function createAdminNotification(Agendamento $agendamento, string $type): void
    {
        $title = $type === 'booking' ? 'Nova inscrição' : 'Inscrição cancelada';
        $message = $type === 'booking'
            ? sprintf(
                '%s se inscreveu para %s às %s.',
                $agendamento->equipe,
                $this->formatDay($agendamento->dia),
                $agendamento->horario
            )
            : sprintf(
                '%s cancelou %s às %s. Motivo: %s',
                $agendamento->equipe,
                $this->formatDay($agendamento->dia),
                $agendamento->horario,
                $agendamento->motivo_cancelamento ?: 'Não informado'
            );

        try {
            AdminNotification::create([
                'agendamento_id' => $agendamento->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Admin site notification was not registered.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function formatDay(string $day): string
    {
        return [
            'sexta' => 'sexta-feira',
            'sabado' => 'sábado',
            'domingo' => 'domingo',
        ][$day] ?? $day;
    }
}
