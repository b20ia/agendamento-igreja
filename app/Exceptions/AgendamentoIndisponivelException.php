<?php

namespace App\Exceptions;

use RuntimeException;

class AgendamentoIndisponivelException extends RuntimeException
{
    public function __construct(
        public readonly string $motivo,
        string $mensagem
    ) {
        parent::__construct($mensagem);
    }

    public static function equipeJaAgendada(): self
    {
        return new self(
            'equipe',
            'Esta equipe já possui um agendamento ativo. Para escolher outro horário, cancele o agendamento atual primeiro.'
        );
    }

    public static function horarioOcupado(): self
    {
        return new self('horario', 'Este horário já foi reservado!');
    }
}
