<?php

namespace App\Services;

use App\Models\Agendamento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sendConfirmation(Agendamento $agendamento): bool
    {
        return $this->send($agendamento->telefone, sprintf(
            "Olá, %s! Seu horário na vigília foi confirmado.\n\nEquipe: %s\nDia: %s\nHorário: %s",
            $agendamento->responsavel,
            $agendamento->equipe,
            $this->formatDay($agendamento->dia),
            $agendamento->horario
        ));
    }

    public function sendCancellation(Agendamento $agendamento): bool
    {
        return $this->send($agendamento->telefone, sprintf(
            "Olá, %s. A inscrição da equipe %s para %s às %s foi cancelada.\n\nMotivo: %s",
            $agendamento->responsavel,
            $agendamento->equipe,
            $this->formatDay($agendamento->dia),
            $agendamento->horario,
            $agendamento->motivo_cancelamento ?: 'Não informado'
        ));
    }

    public function sendProximityReminder(Agendamento $agendamento): bool
    {
        return $this->send($agendamento->telefone, sprintf(
            "Olá, %s! Lembrete: a equipe %s está agendada para a vigília hoje às %s.",
            $agendamento->responsavel,
            $agendamento->equipe,
            $agendamento->horario
        ));
    }

    private function send(string $phone, string $message): bool
    {
        $url = config('services.whatsapp.url');

        if (!$url) {
            Log::info('WhatsApp service URL not configured; message skipped.');
            return false;
        }

        try {
            $request = Http::timeout((int) config('services.whatsapp.timeout', 5))
                ->acceptJson();

            if ($token = config('services.whatsapp.token')) {
                $request = $request->withToken($token);
            }

            $response = $request->post(rtrim($url, '/') . '/send-message', [
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsApp message was not sent.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp service request failed.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
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
