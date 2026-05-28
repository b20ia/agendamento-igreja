<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

        return self::where('status', 'ocupado')
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
     * Reserva um horário
     */
    public static function reservar($dia, $horario, $equipe, $responsavel, $telefone)
    {
        // Verificar disponibilidade
        if (!self::verificarDisponibilidade($dia, $horario)) {
            return null;
        }

        // Procura se já existe um registro
        $agendamento = self::where('dia', $dia)
            ->where('horario', $horario)
            ->first();

        if ($agendamento) {
            // Atualiza registra existente
            $agendamento->update([
                'equipe' => $equipe,
                'responsavel' => $responsavel,
                'telefone' => $telefone,
                'status' => 'ocupado',
                'cancelado' => false,
                'motivo_cancelamento' => null,
            ]);
        } else {
            // Cria novo registro
            $agendamento = self::create([
                'dia' => $dia,
                'horario' => $horario,
                'equipe' => $equipe,
                'responsavel' => $responsavel,
                'telefone' => $telefone,
                'status' => 'ocupado',
                'cancelado' => false,
                'motivo_cancelamento' => null,
            ]);
        }

        return $agendamento;
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

    /**
     * Verifica se uma notificação já foi enviada
     */
    public function notificacaoJaEnviada($tipo)
    {
        $notificacoes = $this->notificacoes_enviadas ?? [];
        return isset($notificacoes[$tipo]);
    }
}
