<?php

namespace App\Models;

use App\Exceptions\AgendamentoIndisponivelException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Agendamento extends Model
{
    protected $table = 'agendamentos';

    protected $fillable = [
        'dia',
        'horario',
        'equipe',
        'responsavel',
        'telefone',
        'status',
        'cancelado',
        'motivo_cancelamento',
        'notificacoes_enviadas',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'notificacoes_enviadas' => 'json',
    ];

    /**
     * Scopes
     */
    public function scopePorDia($query, $dia)
    {
        return $query->where('dia', $dia);
    }

    public function scopeLivres($query)
    {
        return $query->where('status', 'livre');
    }

    public function scopeOcupados($query)
    {
        return $query->where('status', 'ocupado');
    }

    /**
     * Verifica se um horário está disponível
     */
    public static function verificarDisponibilidade($dia, $horario)
    {
        $agendamento = self::where('dia', $dia)
            ->where('horario', $horario)
            ->first();

        return !$agendamento || $agendamento->status === 'livre';
    }

    /**
     * Verifica se uma equipe ja possui um agendamento ativo.
     */
    public static function equipeJaAgendada($equipe)
    {
        $equipeNormalizada = self::normalizarNomeEquipe($equipe);

        return self::ocupados()
            ->where('cancelado', false)
            ->get()
            ->contains(function ($agendamento) use ($equipeNormalizada) {
                return self::normalizarNomeEquipe($agendamento->equipe) === $equipeNormalizada;
            });
    }

    private static function normalizarNomeEquipe($equipe)
    {
        $equipe = trim((string) $equipe);
        $equipe = preg_replace('/\s+/', ' ', $equipe);

        return mb_strtolower($equipe);
    }

    /**
     * Reserva um horário de forma atômica.
     *
     * Toda a verificação (equipe já agendada + disponibilidade do horário)
     * acontece dentro de uma transação com lock na linha do horário, para que
     * dois pedidos simultâneos não consigam reservar o mesmo slot.
     *
     * @throws AgendamentoIndisponivelException quando o horário ou a equipe estão indisponíveis.
     */
    public static function reservar($dia, $horario, $equipe, $responsavel, $telefone)
    {
        return DB::transaction(function () use ($dia, $horario, $equipe, $responsavel, $telefone) {
            $equipeNormalizada = self::normalizarNomeEquipe($equipe);

            // A equipe já tem um agendamento ativo em qualquer horário?
            $equipeAtiva = self::where('status', 'ocupado')
                ->where('cancelado', false)
                ->lockForUpdate()
                ->get()
                ->contains(fn ($a) => self::normalizarNomeEquipe($a->equipe) === $equipeNormalizada);

            if ($equipeAtiva) {
                throw AgendamentoIndisponivelException::equipeJaAgendada();
            }

            // Trava a linha do horário (se existir) para serializar pedidos concorrentes.
            $agendamento = self::where('dia', $dia)
                ->where('horario', $horario)
                ->lockForUpdate()
                ->first();

            if ($agendamento && $agendamento->status === 'ocupado' && !$agendamento->cancelado) {
                throw AgendamentoIndisponivelException::horarioOcupado();
            }

            $dados = [
                'equipe' => $equipe,
                'responsavel' => $responsavel,
                'telefone' => $telefone,
                'status' => 'ocupado',
                'cancelado' => false,
                'motivo_cancelamento' => null,
            ];

            if ($agendamento) {
                $agendamento->update($dados);

                return $agendamento;
            }

            return self::create(array_merge(['dia' => $dia, 'horario' => $horario], $dados));
        });
    }

    /**
     * Cancela um agendamento
     */
    public function cancelar($motivo)
    {
        $this->update([
            'cancelado' => true,
            'motivo_cancelamento' => $motivo,
            'status' => 'livre',
        ]);

        return $this;
    }

    /**
     * Registra uma notificação enviada
     */
    public function registrarNotificacao($tipo)
    {
        $notificacoes = $this->notificacoes_enviadas ?? [];
        
        if (!is_array($notificacoes)) {
            $notificacoes = [];
        }

        $notificacoes[$tipo] = now()->toIso8601String();
        
        $this->update(['notificacoes_enviadas' => $notificacoes]);
    }
}
