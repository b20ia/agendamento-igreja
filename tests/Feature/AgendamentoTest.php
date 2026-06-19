<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgendamentoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Garante que estamos dentro da janela em que os agendamentos estão abertos.
        Carbon::setTestNow(Carbon::parse('2026-07-01 10:00', config('agendamento.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'dia' => 'sexta',
            'horario' => '19:00',
            'equipe' => 'Cozinha',
            'responsavel' => 'Maria',
            'telefone' => '85999999999',
        ], $overrides);
    }

    public function test_agenda_um_horario_com_sucesso(): void
    {
        $response = $this->postJson(route('agendamento.agendar'), $this->payload());

        $response->assertOk()->assertJson(['sucesso' => true]);

        $this->assertDatabaseHas('agendamentos', [
            'dia' => 'sexta',
            'horario' => '19:00',
            'equipe' => 'Cozinha',
            'status' => 'ocupado',
            'cancelado' => false,
        ]);
    }

    public function test_nao_permite_reservar_horario_ja_ocupado(): void
    {
        $this->postJson(route('agendamento.agendar'), $this->payload());

        $response = $this->postJson(route('agendamento.agendar'), $this->payload([
            'equipe' => 'Liturgia',
            'responsavel' => 'João',
        ]));

        $response->assertStatus(409)->assertJson(['sucesso' => false]);

        $this->assertSame(1, Agendamento::where('status', 'ocupado')->count());
    }

    public function test_nao_permite_mesma_equipe_em_dois_horarios(): void
    {
        $this->postJson(route('agendamento.agendar'), $this->payload());

        // Mesma equipe (com variação de caixa/acentuação) em outro horário.
        $response = $this->postJson(route('agendamento.agendar'), $this->payload([
            'horario' => '19:30',
            'equipe' => 'cozinha',
        ]));

        $response->assertStatus(409)->assertJson(['sucesso' => false]);

        $this->assertSame(1, Agendamento::where('status', 'ocupado')->count());
    }

    public function test_cancelamento_libera_o_horario(): void
    {
        $this->postJson(route('agendamento.agendar'), $this->payload());
        $agendamento = Agendamento::first();

        $cancelar = $this->postJson(route('agendamento.cancelar'), [
            'id' => $agendamento->id,
            'motivo' => 'Imprevisto',
        ]);

        $cancelar->assertOk()->assertJson(['sucesso' => true]);

        // O horário fica livre e pode ser reservado por outra equipe.
        $reagendar = $this->postJson(route('agendamento.agendar'), $this->payload([
            'equipe' => 'Ordem',
            'responsavel' => 'Ana',
        ]));

        $reagendar->assertOk()->assertJson(['sucesso' => true]);
    }

    public function test_agendamentos_fechados_fora_da_janela(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00', config('agendamento.timezone')));

        $response = $this->postJson(route('agendamento.agendar'), $this->payload());

        $response->assertStatus(403)->assertJson(['sucesso' => false]);
        $this->assertSame(0, Agendamento::count());
    }
}
