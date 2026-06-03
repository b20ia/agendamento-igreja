@extends('layouts.main')

@section('titulo', 'Notificações | Admin')

@section('content')
    <section class="admin-header">
        <div>
            <span class="tag">Admin</span>
            <h1>Notificações</h1>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-cancelar">Sair</button>
        </form>
    </section>

    @include('admin.partials.nav')

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
@endsection
