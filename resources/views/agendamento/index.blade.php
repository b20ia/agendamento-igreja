@extends('layouts.main')

@section('titulo', '20 Anos EJC SPSP')

@section('content')
    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-label">Escolher dia</span>
        </div>
        <div class="step-line"></div>
        <div class="step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-label">Horário</span>
        </div>
        <div class="step-line"></div>
        <div class="step" data-step="3">
            <span class="step-number">3</span>
            <span class="step-label">Confirmar</span>
        </div>
    </div>

    <section class="page-section page-intro">
        <div class="intro-copy">
            <span class="tag">20 anos</span>
            <h1>Reserve o seu horário na vigília</h1>
            <p>Escolha o dia ideal, selecione o momento disponível e venha sentir a presença de Deus.</p>
        </div>
    </section>

    <section class="selector-section">
        <p class="label-dia">Selecione o dia:</p>
        <div class="dias-container">
            <button class="dia-btn active" data-dia="sexta">
                <span class="dia-nome">Sexta</span>
                <span class="dia-data">24 de Julho</span>
            </button>
            <button class="dia-btn" data-dia="sabado">
                <span class="dia-nome">Sábado</span>
                <span class="dia-data">25 de Julho</span>
            </button>
            <button class="dia-btn" data-dia="domingo">
                <span class="dia-nome">Domingo</span>
                <span class="dia-data">26 de Julho</span>
            </button>
        </div>
    </section>

    <section class="horarios-section">
        <h2 class="horarios-titulo">Horários Disponíveis</h2>
        <div class="horarios-grid" id="horariosGrid">
            <!-- Preenchido pelo JavaScript -->
        </div>
    </section>

    <!-- Modal de inscrição -->
    <div id="modalAgendamento" class="modal">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2 class="modal-titulo">Confirmar participação</h2>

            <form id="formAgendamento" class="form-agendamento">
                @csrf

                <div class="form-group">
                    <label for="diaAgendamento">Dia</label>
                    <input type="text" id="diaAgendamento" class="form-input" readonly>
                </div>

                <div class="form-group">
                    <label for="horarioAgendamento">Horário</label>
                    <input type="text" id="horarioAgendamento" class="form-input" readonly>
                </div>

                <div class="form-group">
                    <label for="nomeEquipe">Nome da Equipe *</label>
                    <input
                        type="text"
                        id="nomeEquipe"
                        name="equipe"
                        class="form-input"
                        placeholder="Ex: Equipe de Louvor"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="nomeResponsavel">Responsável *</label>
                    <input
                        type="text"
                        id="nomeResponsavel"
                        name="responsavel"
                        class="form-input"
                        placeholder="Ex: João Silva"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="telefonePessoa">Telefone *</label>
                    <input
                        type="tel"
                        id="telefonePessoa"
                        name="telefone"
                        class="form-input"
                        placeholder="Ex: (11) 99999-9999"
                        pattern="\([0-9]{2}\) [0-9]{4,5}-[0-9]{4}"
                        required
                    >
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-cancelar" id="btnCancelar">Cancelar</button>
                    <button type="submit" class="btn btn-confirmar">Confirmar participação</button>
                </div>
            </form>

            <div id="mensagemFeedback" class="mensagem-feedback"></div>
        </div>
    </div>

    <!-- Modal de Cancelamento -->
    <div id="modalCancelamento" class="modal">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2 class="modal-titulo">Cancelar inscrição</h2>

            <form id="formCancelamento" class="form-agendamento">
                @csrf

                <div class="form-group">
                    <label for="diaAgendamentoCancelamento">Dia</label>
                    <input type="text" id="diaAgendamentoCancelamento" class="form-input" readonly>
                </div>

                <div class="form-group">
                    <label for="horarioAgendamentoCancelamento">Horário</label>
                    <input type="text" id="horarioAgendamentoCancelamento" class="form-input" readonly>
                </div>

                <div class="form-group">
                    <label for="equipeAgendamentoCancelamento">Equipe</label>
                    <input type="text" id="equipeAgendamentoCancelamento" class="form-input" readonly>
                </div>

                <div class="form-group">
                    <label for="motivoCancelamento">Por que está desistindo? *</label>
                    <textarea
                        id="motivoCancelamento"
                        name="motivo"
                        class="form-input"
                        placeholder="Ex: Equipe indisponível, problema na agenda, outros..."
                        rows="4"
                        required
                    ></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-cancelar" id="btnCancelarCancelamento">Voltar</button>
                    <button type="submit" class="btn btn-confirmar btn-danger">Confirmar Cancelamento</button>
                </div>
            </form>

            <div id="mensagemFeedbackCancelamento" class="mensagem-feedback"></div>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay"></div>

    <script src="{{ asset('js/agendamento.js') }}"></script>
@endsection
