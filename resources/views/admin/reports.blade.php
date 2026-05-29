@extends('layouts.main')

@section('titulo', 'Relatórios | Admin')

@php
    $dias = [
        'sexta' => 'Sexta-feira',
        'sabado' => 'Sábado',
        'domingo' => 'Domingo',
    ];
@endphp

@section('content')
    <section class="admin-header">
        <div>
            <span class="tag">Admin</span>
            <h1>Relatórios</h1>
        </div>
    </section>

    @include('admin.partials.nav')

    <section class="admin-summary" aria-label="Resumo dos relatórios">
        <div class="admin-metric">
            <span>Total</span>
            <strong>{{ $reports['total'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Ativos</span>
            <strong>{{ $reports['ativos'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Cancelados</span>
            <strong>{{ $reports['cancelados'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Cancelamento</span>
            <strong>{{ $reports['taxa_cancelamento'] }}%</strong>
        </div>
    </section>

    <section class="admin-report-grid">
        @foreach ($reports['por_dia'] as $dia => $dados)
            <article class="admin-report-card">
                <h2>{{ $dias[$dia] ?? $dia }}</h2>
                <div class="admin-report-row">
                    <span>Inscrições ativas</span>
                    <strong>{{ $dados['ativos'] }}</strong>
                </div>
                <div class="admin-report-row">
                    <span>Cancelamentos</span>
                    <strong>{{ $dados['cancelados'] }}</strong>
                </div>
            </article>
        @endforeach
    </section>

    <section class="admin-table-wrap">
        <div class="admin-table-header">
            <h2>Cancelamentos recentes</h2>
            <span>{{ $reports['cancelamentos_recentes']->count() }} registros</span>
        </div>

        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Horário</th>
                        <th>Equipe</th>
                        <th>Responsável</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports['cancelamentos_recentes'] as $agendamento)
                        <tr>
                            <td>{{ $dias[$agendamento->dia] ?? $agendamento->dia }}</td>
                            <td>{{ $agendamento->horario }}</td>
                            <td>{{ $agendamento->equipe }}</td>
                            <td>{{ $agendamento->responsavel }}</td>
                            <td>{{ $agendamento->motivo_cancelamento ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-empty">Nenhum cancelamento registrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
