<?php

namespace Database\Seeders;

use App\Models\Agendamento;
use Illuminate\Database\Seeder;

class AgendamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horarios = [
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

        foreach ($horarios as $dia => $horariosDodia) {
            foreach ($horariosDodia as $horario) {
                // Verifica se já existe para evitar duplicatas
                $existe = Agendamento::where('dia', $dia)
                    ->where('horario', $horario)
                    ->exists();

                if (!$existe) {
                    Agendamento::create([
                        'dia' => $dia,
                        'horario' => $horario,
                        'equipe' => null,
                        'responsavel' => null,
                        'telefone' => null,
                        'status' => 'livre',
                    ]);
                }
            }
        }
    }
}
