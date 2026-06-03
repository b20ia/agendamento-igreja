@extends('layouts.main')

@section('titulo', 'Painel Admin | 20 Anos EJC SPSP')

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
            <h1>Painel de inscrições</h1>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-cancelar">Sair</button>
        </form>
    </section>

    @include('admin.partials.nav')

    <section class="admin-summary" aria-label="Resumo dos agendamentos">
        <div class="admin-metric">
            <span>Total</span>
            <strong>{{ $resumo['total'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Ativos</span>
            <strong>{{ $resumo['ativos'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Cancelados</span>
            <strong>{{ $resumo['cancelados'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Sexta</span>
            <strong>{{ $resumo['sexta'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Sábado</span>
            <strong>{{ $resumo['sabado'] }}</strong>
        </div>
        <div class="admin-metric">
            <span>Domingo</span>
            <strong>{{ $resumo['domingo'] }}</strong>
        </div>
    </section>

    <section class="admin-table-wrap" aria-label="Painel de inscrições">
        <div class="admin-table-header">
            <div>
                <h2>Inscrições</h2>
                <span>{{ $agendamentos->count() }} registros{{ $search ? ' para "' . $search . '"' : '' }}</span>
            </div>

            <div class="admin-table-actions">
                <a href="{{ route('admin.agendamentos.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-outline">Exportar CSV</a>
            </div>
        </div>

        <div class="admin-filter-panel">
            <form method="GET" action="{{ route('admin.index') }}" class="admin-search-form">
                <select id="dia" name="dia" aria-label="Filtrar por dia">
                    <option value="">Todos os dias</option>
                    <option value="sexta" {{ $filtroDia === 'sexta' ? 'selected' : '' }}>Sexta</option>
                    <option value="sabado" {{ $filtroDia === 'sabado' ? 'selected' : '' }}>Sábado</option>
                    <option value="domingo" {{ $filtroDia === 'domingo' ? 'selected' : '' }}>Domingo</option>
                </select>

                <select id="status" name="status" aria-label="Filtrar por status">
                    <option value="">Todos os status</option>
                    <option value="ativos" {{ $filtroStatus === 'ativos' ? 'selected' : '' }}>Ativos</option>
                    <option value="cancelados" {{ $filtroStatus === 'cancelados' ? 'selected' : '' }}>Cancelados</option>
                    <option value="livres" {{ $filtroStatus === 'livres' ? 'selected' : '' }}>Livres</option>
                </select>

                <input id="equipe" name="equipe" type="text" value="{{ $equipe }}" placeholder="Equipe..." aria-label="Filtrar por equipe" />
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Busca livre" aria-label="Buscar inscrições" />
                <button type="submit" class="btn btn-primary">Filtrar</button>
                @if ($search || $filtroDia || $filtroStatus || $equipe)
                    <a href="{{ route('admin.index') }}" class="btn btn-link">Redefinir filtros</a>
                @endif
            </form>

            @if ($search || $filtroDia || $filtroStatus || $equipe)
                <div class="admin-filter-summary" aria-live="polite">
                    <span>Filtros ativos:</span>
                    @if ($filtroDia)
                        <span class="admin-filter-pill">Dia: {{ $dias[$filtroDia] ?? ucfirst($filtroDia) }}</span>
                    @endif
                    @if ($filtroStatus)
                        <span class="admin-filter-pill">Status: {{ ucfirst($filtroStatus) }}</span>
                    @endif
                    @if ($equipe)
                        <span class="admin-filter-pill">Equipe: {{ $equipe }}</span>
                    @endif
                    @if ($search)
                        <span class="admin-filter-pill">Busca: "{{ $search }}"</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Horário</th>
                        <th>Equipe</th>
                        <th>Responsável</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Motivos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agendamentos as $agendamento)
                        <tr>
                            <td>{{ $dias[$agendamento->dia] ?? $agendamento->dia }}</td>
                            <td>{{ $agendamento->horario }}</td>
                            <td>{{ $agendamento->equipe }}</td>
                            <td>{{ $agendamento->responsavel }}</td>
                            <td>{{ $agendamento->telefone }}</td>
                            <td>
                                <span
                                    class="admin-status {{ $agendamento->cancelado ? 'cancelado' : $agendamento->status }}"
                                    title="{{ $agendamento->cancelado ? 'Cancelado' : ($agendamento->status === 'ocupado' ? 'Inscrição ativa' : 'Horário livre') }}"
                                >
                                    {{ $agendamento->cancelado ? 'Cancelado' : ($agendamento->status === 'ocupado' ? 'Ativo' : 'Livre') }}
                                </span>
                            </td>
                            <td>{{ $agendamento->motivo_cancelamento ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-empty">
                                {{ $search ? 'Nenhuma inscrição encontrada para "' . $search . '".' : 'Nenhuma inscrição registrada ainda.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
