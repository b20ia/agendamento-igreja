@extends('layouts.main')

@section('titulo', '20 Anos EJC SPSP')

@section('content')
    <section class="page-section intro-section">
        <span class="tag">EJC SPSP</span>
        <h1>20 anos de EJC em São Paulo</h1>
        <p>Celebramos duas décadas de adoração, oração e crescimento espiritual na comunidade jovem da igreja.</p>
    </section>

    <section class="page-section">
        <h2 class="section-title">O que vivenciamos</h2>
        <div class="feature-grid">
            <article class="feature-card">
                <h3>Louvor e Adoração</h3>
                <p>Momentos de canto, reflexão e encontro com Deus que abrem o coração para a ação do Espírito Santo.</p>
            </article>
            <article class="feature-card">
                <h3>Estudos e Reflexões</h3>
                <p>Temas bíblicos e experiências de vida que incentivam a maturidade espiritual e a vivência cristã autêntica.</p>
            </article>
            <article class="feature-card">
                <h3>Comunhão em Equipe</h3>
                <p>Integração entre grupos, partilha de dons e fortalecimento do corpo de Cristo por meio da amizade e do serviço.</p>
            </article>
        </div>
    </section>

    <section class="page-section">
        <h2 class="section-title">Como participar</h2>
        <ol class="info-list">
            <li>Converse com sua equipe e escolha o melhor momento para celebrar conosco.</li>
            <li>Garanta sua vaga no evento e confirme os dados do responsável pela equipe.</li>
            <li>Venha preparado para cantar, orar e viver uma experiência transformadora.</li>
        </ol>
    </section>

    <section class="page-section">
        <h2 class="section-title">Por que o EJC é importante?</h2>
        <p>O EJC ajuda os jovens a fortalecerem sua caminhada com Cristo, a descobrirem sua identidade na fé e a experimentarem uma igreja viva e acolhedora.</p>
        <a href="{{ route('agendamento.index') }}" class="btn btn-confirmar">Participar do evento</a>
    </section>
@endsection
