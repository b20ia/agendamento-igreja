<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', config('app.name', '20 Anos EJC SPSP'))</title>
    <link rel="stylesheet" href="{{ asset('css/agendamento.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-bar">
            <a href="{{ route('agendamento.index') }}" class="brand">
                <span class="brand-acronym">EJC</span>
                <span class="brand-name">SPSP 20 anos</span>
            </a>

            <nav class="site-nav" aria-label="Navegação principal">
                <a href="{{ route('agendamento.index') }}" class="{{ request()->routeIs('agendamento.index') ? 'active' : '' }}">Início</a>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>© {{ date('Y') }} 20 Anos EJC SPSP | Celebração e comunhão</p>
        </div>
    </footer>
</body>
</html>
