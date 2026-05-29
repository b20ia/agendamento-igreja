@extends('layouts.main')

@section('titulo','20 Anos EJC SPSP')

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <span class="tag">EJC SPSP 20 anos</span>
            <h1>20 anos de EJC SPSP em São Paulo</h1>
            <p>Venha celebrar duas décadas de fé, louvor e comunhão com a juventude da nossa igreja.</p>
            <div class="hero-actions">
                <a href="{{ route('ejc') }}" class="btn btn-confirmar">Sobre o EJC</a>
                <a href="{{ route('agendamento.index') }}" class="btn btn-cancelar">Participar agora</a>
            </div>
        </div>

        <div class="hero-cards">
            <article class="page-card">
                <h2>História</h2>
                <p>20 anos de caminhada, testemunhos e transformação entre os jovens do EJC SPSP.</p>
            </article>
            <article class="page-card">
                <h2>Celebração</h2>
                <p>Momentos especiais de louvor, oração e comunhão para marcar esse aniversário.</p>
            </article>
            <article class="page-card">
                <h2>Comunhão</h2>
                <p>Um espaço acolhedor para jovens e famílias se conectarem e crescerem juntos na fé.</p>
            </article>
        </div>
    </section>

    <section class="page-section">
        <h2 class="section-title">O que você encontra aqui</h2>
        <div class="feature-grid">
            <article class="feature-card">
                <h3>Louvor que inspira</h3>
                <p>Adoração com música, oração e mensagens que tocam o coração.</p>
            </article>
            <article class="feature-card">
                <h3>Encontros com propósito</h3>
                <p>Celebrações, estudos e momentos de comunhão para toda a juventude.</p>
            </article>
            <article class="feature-card">
                <h3>Comunidade forte</h3>
                <p>Jovens unidos pela fé, com apoio mútuo e serviço à igreja e à família.</p>
            </article>
        </div>
    </section>

    <section class="page-section">
        <h2 class="section-title">Como participar</h2>
        <ol class="info-list">
            <li>Confira a programação do evento e convide sua equipe para participar.</li>
            <li>Reserve sua vaga para o encontro e confirme os dados da equipe.</li>
            <li>Venha preparado para cantar, orar e viver um tempo especial de comunhão.</li>
        </ol>
    </section>
@endsection
