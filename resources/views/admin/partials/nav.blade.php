<nav class="admin-tabs" aria-label="Navegação administrativa">
    <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'active' : '' }}">Painel</a>
    <a href="{{ route('admin.notifications') }}" class="{{ request()->routeIs('admin.notifications') ? 'active' : '' }}">Notificações</a>
    <a href="{{ route('admin.reports') }}" class="{{ request()->routeIs('admin.reports') ? 'active' : '' }}">Relatórios</a>
    <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">Configurações</a>
</nav>
