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

    <section class="admin-notifications">
        <div class="admin-table-header">
            <div>
                <h2>Notificações do site</h2>
                <span>{{ $unreadNotifications }} não lidas</span>
            </div>

            @if ($unreadNotifications > 0)
                <form method="POST" action="{{ route('admin.notifications.read') }}">
                    @csrf
                    <button type="submit" class="btn btn-cancelar">Marcar lidas</button>
                </form>
            @endif
        </div>

        <div class="admin-notification-list">
            @forelse ($notifications as $notification)
                <article class="admin-notification {{ $notification->read_at ? '' : 'unread' }}">
                    <span class="admin-notification-dot {{ $notification->type }}"></span>
                    <div>
                        <strong>{{ $notification->title }}</strong>
                        <p>{{ $notification->message }}</p>
                        <time>{{ $notification->created_at->format('d/m/Y H:i') }}</time>
                    </div>
                </article>
            @empty
                <p class="admin-empty">Nenhuma notificação registrada ainda.</p>
            @endforelse
        </div>
    </section>

    <section class="admin-table-wrap">
        <div class="admin-table-header">
            <h2>Inscrições</h2>
            <span>{{ $agendamentos->count() }} registros</span>
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
                        <th>Motivo</th>
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
                                <span class="admin-status {{ $agendamento->cancelado ? 'cancelado' : $agendamento->status }}">
                                    {{ $agendamento->cancelado ? 'Cancelado' : ($agendamento->status === 'ocupado' ? 'Ativo' : 'Livre') }}
                                </span>
                            </td>
                            <td>{{ $agendamento->motivo_cancelamento ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-empty">Nenhuma inscrição registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
