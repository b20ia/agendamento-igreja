<?php

return [
    // Fuso horário usado para abrir/fechar os agendamentos.
    'timezone' => 'America/Fortaleza',

    // Lista de equipes sugeridas no formulário de agendamento.
    // Atualize aqui sempre que quiser adicionar equipes fixas.
    'equipes' => [
        'Jotado',
        'Liturgia',
        'Vigília',
        'Trânsito',
        'Lanchinho',
        'Bandinha',
        'Ordem',
        'Imprensaria',
        'Visitantes',
        'Correio',
        'Boa Vontade',
        'Minibox',
        'Externa',
        'Cozinha',
        'Animadores',
        'Equipe 20 anos',
        'Círculos',
    ],

    // Horários disponíveis para reserva, por dia.
    'horarios' => [
        'sexta' => [
            '19:00', '19:30', '20:00', '20:30', '21:00', '21:30',
        ],
        'sabado' => [
            '07:30', '08:00', '08:30', '09:00', '09:30', '10:00',
            '10:30', '11:00', '11:30', '12:00', '12:30', '13:00',
            '13:30', '14:00', '14:30', '15:30', '16:00', '16:30',
            '17:00', '17:30', '18:00',
        ],
        'domingo' => [
            '07:30', '08:00', '08:30', '09:00', '09:30', '10:00',
            '10:30', '11:00', '11:30', '12:00', '12:30', '13:00',
            '13:30', '14:00', '14:30', '15:30',
        ],
    ],

    // Janelas em que novos agendamentos podem ser feitos.
    // Cada item é um intervalo [início, fim] no formato 'Y-m-d H:i'.
    // Antes da primeira janela os agendamentos ficam abertos; fora dos
    // intervalos (depois do início da primeira janela) ficam fechados.
    'abertura' => [
        // Fica aberto até este momento (início do evento na sexta).
        'fecha_em' => '2026-07-24 21:00',
        // Janelas em que reabre durante o evento.
        'janelas' => [
            ['2026-07-25 07:00', '2026-07-25 20:00'],
            ['2026-07-26 07:00', '2026-07-26 15:00'],
        ],
    ],
];
