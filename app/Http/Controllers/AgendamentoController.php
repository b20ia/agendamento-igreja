<?php

namespace App\Http\Controllers;

use App\Exceptions\AgendamentoIndisponivelException;
use App\Models\AdminNotification;
use App\Models\Agendamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgendamentoController extends Controller
{
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
        // Não expomos telefone/responsável aqui: são dados pessoais e o
        // telefone é usado como prova de identidade no cancelamento.
        $agendamentos = Agendamento::where('dia', $dia)
            ->get(['id', 'dia', 'horario', 'equipe', 'status', 'cancelado']);

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

        try {
            $agendamento = Agendamento::reservar(
                $validated['dia'],
                $validated['horario'],
                $validated['equipe'],
                $validated['responsavel'],
                $validated['telefone']
            );
        } catch (AgendamentoIndisponivelException $exception) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => $exception->getMessage(),
            ], 409);
        }

        $this->createAdminNotification($agendamento, 'booking');

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Agendamento realizado com sucesso!',
            'agendamento' => $agendamento->fresh(),
        ]);
    }

    /**
     * Cancela um agendamento (AJAX)
     */
    public function cancelar(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:agendamentos,id',
            'telefone' => 'required|string|max:20',
            'motivo' => 'required|string|max:255',
        ]);

        $agendamento = Agendamento::find($validated['id']);

        if (!$agendamento || $agendamento->status !== 'ocupado') {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Agendamento não encontrado ou já foi cancelado.',
            ], 404);
        }

        if (!$this->telefonesConferem($validated['telefone'], $agendamento->telefone)) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'O telefone informado não confere com o usado na inscrição.',
            ], 422);
        }

        $agendamento->cancelar($validated['motivo']);

        $this->createAdminNotification($agendamento, 'cancellation');

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Agendamento cancelado com sucesso!',
            'agendamento' => $agendamento->fresh(),
        ]);
    }

    /**
     * Retorna os horários disponíveis para um dia
     */
    public function horarios()
    {
        return response()->json(config('agendamento.horarios', []));
    }

    /**
     * Define a janela geral em que novos agendamentos podem ser feitos.
     */
    private function agendamentoEstaAberto(): bool
    {
        $timezone = config('agendamento.timezone', 'America/Fortaleza');
        $agora = Carbon::now($timezone);

        $fechaEm = config('agendamento.abertura.fecha_em');

        if ($fechaEm && $agora->lt(Carbon::parse($fechaEm, $timezone))) {
            return true;
        }

        foreach (config('agendamento.abertura.janelas', []) as [$inicio, $fim]) {
            if ($agora->between(
                Carbon::parse($inicio, $timezone),
                Carbon::parse($fim, $timezone),
                false
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compara o telefone informado no cancelamento com o usado na inscrição,
     * considerando apenas os dígitos (ignora parênteses, espaços e traços).
     */
    private function telefonesConferem(string $informado, ?string $cadastrado): bool
    {
        $apenasDigitos = static fn ($valor) => preg_replace('/\D/', '', (string) $valor);

        $informado = $apenasDigitos($informado);
        $cadastrado = $apenasDigitos($cadastrado);

        return $informado !== '' && $informado === $cadastrado;
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
