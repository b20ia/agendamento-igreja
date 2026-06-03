// =============================================
// Sistema de Agendamento - JavaScript
// =============================================

class SistemaAgendamento {
    constructor() {
        this.diaAtual = 'sexta';
        this.horariosDisponiveis = {};
        this.agendamentos = {};
        this.horariosCarregados = false;
        this.notificacoesProximidadePendentes = new Set();
        
        // Mapping de dias para português
        this.diasNomes = {
            'sexta': 'Sexta-feira',
            'sabado': 'Sábado',
            'domingo': 'Domingo'
        };

        this.datasEvento = {
            'sexta': '2026-07-24',
            'sabado': '2026-07-25',
            'domingo': '2026-07-26'
        };

        // Armazena dados do agendamento atual
        this.agendamentoAtual = {
            dia: null,
            horario: null
        };

        this.userNotifications = [];

        // Armazena dados do cancelamento atual
        this.cancelamentoAtual = {
            id: null,
            dia: null,
            horario: null,
            equipe: null
        };

        this.inicializar();
    }

    // =============================================
    // INICIALIZAÇÃO
    // =============================================

    inicializar() {
        this.carregarNotificacoesUsuario();
        this.configurarEventos();
        this.renderizarEstadoHorarios('Carregando horários...');

        this.obterHorarios()
            .then(() => this.carregarAgendamentos(this.diaAtual))
            .catch(() => this.renderizarEstadoHorarios('Não foi possível carregar os horários. Tente atualizar a página.', 'erro'));
    }

    carregarNotificacoesUsuario() {
        try {
            const saved = localStorage.getItem('siteUserNotifications');
            this.userNotifications = saved ? JSON.parse(saved) : [];
        } catch (error) {
            this.userNotifications = [];
        }

        this.renderizarNotificacoesUsuario();
    }

    salvarNotificacoesUsuario() {
        try {
            localStorage.setItem('siteUserNotifications', JSON.stringify(this.userNotifications));
        } catch (error) {
            console.warn('Não foi possível salvar as notificações do usuário localmente.', error);
        }
    }

    adicionarNotificacaoUsuario(type, title, message) {
        const notificacao = {
            id: `${Date.now()}-${Math.random()}`,
            type,
            title,
            message,
            createdAt: new Date().toISOString(),
        };

        this.userNotifications.unshift(notificacao);
        this.userNotifications = this.userNotifications.slice(0, 6);
        this.salvarNotificacoesUsuario();
        this.renderizarNotificacoesUsuario();
    }

    renderizarNotificacoesUsuario() {
        const list = document.getElementById('userNotificationsList');
        const count = document.getElementById('userNotificationsCount');
        const dropdown = document.getElementById('userNotificationsDropdown');
        if (!list || !count || !dropdown) return;

        const notificationCount = this.userNotifications.length;
        count.textContent = notificationCount;
        count.style.display = notificationCount > 0 ? 'inline-flex' : 'none';

        if (notificationCount === 0) {
            list.innerHTML = '<p class="bell-dropdown-empty">Nenhuma notificação ainda.</p>';
        } else {
            list.innerHTML = this.userNotifications.map((notification) => `
                <article class="bell-notification-item ${notification.type}" data-notification-id="${notification.id}" style="cursor: pointer;">
                    <strong>${this.escaparHtml(notification.title)}</strong>
                    <p>${this.escaparHtml(notification.message)}</p>
                    <span class="bell-notification-time">${new Date(notification.createdAt).toLocaleString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</span>
                </article>
            `).join('');

            // Adiciona eventos de clique às notificações
            list.querySelectorAll('.bell-notification-item').forEach((item) => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = item.getAttribute('data-notification-id');
                    this.removerNotificacaoUsuario(id);
                });
            });
        }

        dropdown.classList.toggle('vazio', notificationCount === 0);
    }

    removerNotificacaoUsuario(id) {
        this.userNotifications = this.userNotifications.filter((n) => n.id !== id);
        this.salvarNotificacoesUsuario();
        this.renderizarNotificacoesUsuario();
    }

    abrirNotificacoesUsuario() {
        const dropdown = document.getElementById('userNotificationsDropdown');
        const bell = document.getElementById('userNotificationBell');
        if (!dropdown || !bell) return;

        const expanded = bell.getAttribute('aria-expanded') === 'true';
        bell.setAttribute('aria-expanded', String(!expanded));
        dropdown.setAttribute('aria-hidden', String(expanded));
        dropdown.classList.toggle('ativo', !expanded);
    }

    fecharNotificacoesUsuario() {
        const dropdown = document.getElementById('userNotificationsDropdown');
        const bell = document.getElementById('userNotificationBell');
        if (!dropdown || !bell) return;

        bell.setAttribute('aria-expanded', 'false');
        dropdown.setAttribute('aria-hidden', 'true');
        dropdown.classList.remove('ativo');
    }

    configurarEventos() {
        // Botões de seleção de dia
        document.querySelectorAll('.dia-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => this.selecionarDia(e));
        });

        const bellButton = document.getElementById('userNotificationBell');
        const bellClose = document.getElementById('userNotificationsClose');

        if (bellButton) {
            bellButton.addEventListener('click', (e) => {
                e.stopPropagation();
                this.abrirNotificacoesUsuario();
            });
        }

        if (bellClose) {
            bellClose.addEventListener('click', (e) => {
                e.stopPropagation();
                this.fecharNotificacoesUsuario();
            });
        }

        document.addEventListener('click', (event) => {
            const dropdown = document.getElementById('userNotificationsDropdown');
            const bell = document.getElementById('userNotificationBell');
            if (!dropdown || !bell || !dropdown.classList.contains('ativo')) return;
            if (bell.contains(event.target) || dropdown.contains(event.target)) return;
            this.fecharNotificacoesUsuario();
        });

        

        // Modal de agendamento
        document.getElementById('btnCancelar').addEventListener('click', () => this.fecharModalAgendamento());
        document.getElementById('modalOverlay').addEventListener('click', () => this.fecharModais());
        document.querySelector('.modal-close').addEventListener('click', () => this.fecharModalAgendamento());

        // Formulário de agendamento
        document.getElementById('formAgendamento').addEventListener('submit', (e) => this.enviarAgendamento(e));

        // Modal de cancelamento
        document.getElementById('btnCancelarCancelamento').addEventListener('click', () => this.fecharModalCancelamento());
        document.querySelectorAll('.modal-close').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                if (e.currentTarget.closest('#modalCancelamento')) {
                    this.fecharModalCancelamento();
                }
            });
        });

        // Formulário de cancelamento
        document.getElementById('formCancelamento').addEventListener('submit', (e) => this.enviarCancelamento(e));

        // Auto-fechar mensagens de feedback
        this.configurarAutoFecharMensagens();
    }

    // =============================================
    // DIAS
    // =============================================

    selecionarDia(event) {
        const btn = event.currentTarget;
        const diaClicado = btn.dataset.dia;

        // Remover classe active de todos os botões
        document.querySelectorAll('.dia-btn').forEach((b) => {
            b.classList.remove('active');
        });

        // Adicionar classe active ao botão clicado
        btn.classList.add('active');

        // Atualizar dia atual
        this.diaAtual = diaClicado;
        this.atualizarPassos(2);

        // Carregar agendamentos do novo dia
        this.carregarAgendamentos(diaClicado);
    }


    // =============================================
    // HORÁRIOS
    // =============================================

    obterHorarios() {
        return fetch('/api/horarios')
            .then((response) => {
                if (!response.ok) throw new Error('Erro ao buscar horários');
                return response.json();
            })
            .then((data) => {
                this.horariosDisponiveis = data;
                this.horariosCarregados = true;
            })
            .catch((error) => {
                console.error('Erro:', error);
                throw error;
            });
    }

    // =============================================
    // AGENDAMENTOS
    // =============================================

    carregarAgendamentos(dia, mostrarCarregando = true) {
        if (!this.horariosCarregados) {
            this.renderizarEstadoHorarios('Carregando horários...');
            return Promise.resolve();
        }

        if (mostrarCarregando) {
            this.renderizarEstadoHorarios('Atualizando horários...');
        }

        return fetch(`/api/dia/${dia}`)
            .then((response) => {
                if (!response.ok) throw new Error('Erro ao carregar agendamentos');
                return response.json();
            })
            .then((data) => {
                this.agendamentos[dia] = data;
                this.renderizarHorarios(dia);
            })
            .catch((error) => {
                console.error('Erro:', error);
                this.renderizarEstadoHorarios('Não foi possível atualizar os horários. Verifique sua conexão e tente novamente.', 'erro');
            });
    }

    renderizarHorarios(dia) {
        const gridHorarios = document.getElementById('horariosGrid');
        gridHorarios.innerHTML = '';

        const horarios = this.horariosDisponiveis[dia] || [];
        const agendamentos = this.agendamentos[dia] || [];

        if (horarios.length === 0) {
            this.renderizarEstadoHorarios('Nenhum horário cadastrado para este dia.');
            return;
        }

        // Criar mapa de agendamentos por horário
        const mapaAgendamentos = {};
        agendamentos.forEach((agendamento) => {
            mapaAgendamentos[agendamento.horario] = agendamento;
        });

        // Renderizar cada horário
        horarios.forEach((horario) => {
            const agendamento = mapaAgendamentos[horario];
            const card = this.criarCardHorario(dia, horario, agendamento);
            gridHorarios.appendChild(card);
        });
    }

    criarCardHorario(dia, horario, agendamento) {
        const card = document.createElement('div');
        card.className = 'horario-card';

        const estaOcupado = agendamento && agendamento.status === 'ocupado' && !agendamento.cancelado;
        const agendamentoAberto = this.agendamentoEstaAberto();

        if (estaOcupado) {
            card.classList.add('ocupado');
            card.innerHTML = `
                <div class="horario-time">${horario}</div>
                <div class="horario-status">Ocupado</div>
                <div class="horario-equipe">${this.escaparHtml(agendamento.equipe)}</div>
                <button class="btn-desmarcar" type="button" aria-label="Desmarcar ${this.escaparHtml(agendamento.equipe)}">
                    Desmarcar
                </button>
            `;
            
            // Adicionar evento ao botão de desmarcar
            card.querySelector('.btn-desmarcar').addEventListener('click', (e) => {
                e.stopPropagation();
                this.abrirModalCancelamento(agendamento);
            });
        } else if (!agendamentoAberto) {
            card.classList.add('desabilitado');
            card.innerHTML = `
                <div class="horario-time">${horario}</div>
                <div class="horario-status">Fechado para agendamento</div>
            `;
        } else {
            card.classList.add('livre');
            card.innerHTML = `
                <div class="horario-time">${horario}</div>
                <div class="horario-status">Disponível</div>
            `;
            card.addEventListener('click', () => {
                this.abrirModalAgendamento(dia, horario);
            });
        }

        return card;
    }

    agendamentoEstaAberto() {
        const agora = new Date();
        const sextaFechamento = new Date(2026, 6, 24, 21, 0, 0);
        const sabadoAbertura = new Date(2026, 6, 25, 7, 0, 0);
        const sabadoFechamento = new Date(2026, 6, 25, 20, 0, 0);
        const domingoAbertura = new Date(2026, 6, 26, 7, 0, 0);
        const domingoFechamento = new Date(2026, 6, 26, 15, 0, 0);

        return agora < sextaFechamento
            || (agora >= sabadoAbertura && agora < sabadoFechamento)
            || (agora >= domingoAbertura && agora < domingoFechamento);
    }

    

    // =============================================
    // MODAL DE AGENDAMENTO
    // =============================================

    abrirModalAgendamento(dia, horario) {
        this.agendamentoAtual = { dia, horario };
        this.atualizarPassos(3);

        // Preencher campos
        document.getElementById('diaAgendamento').value = this.diasNomes[dia];
        document.getElementById('horarioAgendamento').value = horario;

        // Limpar formulário
        document.getElementById('nomeEquipe').value = '';
        document.getElementById('nomeResponsavel').value = '';
        document.getElementById('telefonePessoa').value = '';

        // Limpar mensagem de feedback
        const feedback = document.getElementById('mensagemFeedback');
        feedback.innerHTML = '';
        feedback.classList.remove('sucesso', 'erro');

        // Mostrar modal
        document.getElementById('modalOverlay').classList.add('ativo');
        document.getElementById('modalAgendamento').classList.add('ativo');

        // Focar no primeiro campo
        document.getElementById('nomeEquipe').focus();
    }

    fecharModalAgendamento() {
        document.getElementById('modalOverlay').classList.remove('ativo');
        document.getElementById('modalAgendamento').classList.remove('ativo');
        this.atualizarPassos(2);
    }

    // =============================================
    // MODAL DE CANCELAMENTO
    // =============================================

    abrirModalCancelamento(agendamento) {
        this.cancelamentoAtual = {
            id: agendamento.id,
            dia: agendamento.dia,
            horario: agendamento.horario,
            equipe: agendamento.equipe
        };

        // Preencher campos
        document.getElementById('diaAgendamentoCancelamento').value = this.diasNomes[agendamento.dia];
        document.getElementById('horarioAgendamentoCancelamento').value = agendamento.horario;
        document.getElementById('equipeAgendamentoCancelamento').value = agendamento.equipe;
        document.getElementById('motivoCancelamento').value = '';

        // Limpar mensagem de feedback
        const feedback = document.getElementById('mensagemFeedbackCancelamento');
        feedback.innerHTML = '';
        feedback.classList.remove('sucesso', 'erro');

        // Mostrar modal
        document.getElementById('modalOverlay').classList.add('ativo');
        document.getElementById('modalCancelamento').classList.add('ativo');
        this.atualizarPassos(3);

        // Focar no textarea
        document.getElementById('motivoCancelamento').focus();
    }

    fecharModalCancelamento() {
        document.getElementById('modalOverlay').classList.remove('ativo');
        document.getElementById('modalCancelamento').classList.remove('ativo');
        this.atualizarPassos(2);
    }

    fecharModais() {
        this.fecharModalAgendamento();
        this.fecharModalCancelamento();
    }

    // =============================================
    // AGENDAMENTO
    // =============================================

    enviarAgendamento(event) {
        event.preventDefault();

        const btnConfirmar = event.target.querySelector('[type="submit"]');
        const textoBtnOriginal = btnConfirmar.textContent;

        // Desabilitar botão
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Enviando...';

        const dados = {
            dia: this.agendamentoAtual.dia,
            horario: this.agendamentoAtual.horario,
            equipe: document.getElementById('nomeEquipe').value,
            responsavel: document.getElementById('nomeResponsavel').value,
            telefone: document.getElementById('telefonePessoa').value,
        };

        fetch('/api/agendar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            },
            body: JSON.stringify(dados),
        })
            .then((response) => response.json())
            .then((result) => {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = textoBtnOriginal;

                if (result.sucesso) {
                    this.mostrarMensagem(result.mensagem, 'sucesso');
                    this.adicionarNotificacaoUsuario('booking', 'Agendamento confirmado', result.mensagem);

                    // Atualizar agendamentos
                    setTimeout(() => {
                        this.carregarAgendamentos(this.agendamentoAtual.dia);
                        this.fecharModalAgendamento();
                    }, 1500);
                } else {
                    this.mostrarMensagem(result.mensagem, 'erro');
                }
            })
            .catch((error) => {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = textoBtnOriginal;
                console.error('Erro:', error);
                this.mostrarMensagem('Erro ao processar agendamento', 'erro');
            });
    }

    // =============================================
    // CANCELAMENTO
    // =============================================

    enviarCancelamento(event) {
        event.preventDefault();

        const btnConfirmar = event.target.querySelector('[type="submit"]');
        const textoBtnOriginal = btnConfirmar.textContent;

        // Desabilitar botão
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Cancelando...';

        const dados = {
            id: this.cancelamentoAtual.id,
            motivo: document.getElementById('motivoCancelamento').value,
        };

        fetch('/api/cancelar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            },
            body: JSON.stringify(dados),
        })
            .then((response) => response.json())
            .then((result) => {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = textoBtnOriginal;

                if (result.sucesso) {
                    this.mostrarMensagemCancelamento(result.mensagem, 'sucesso');
                    this.adicionarNotificacaoUsuario('cancellation', 'Inscrição cancelada', result.mensagem);

                    // Atualizar agendamentos
                    setTimeout(() => {
                        this.carregarAgendamentos(this.cancelamentoAtual.dia);
                        this.fecharModalCancelamento();
                    }, 1500);
                } else {
                    this.mostrarMensagemCancelamento(result.mensagem, 'erro');
                }
            })
            .catch((error) => {
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = textoBtnOriginal;
                console.error('Erro:', error);
                this.mostrarMensagemCancelamento('Erro ao cancelar agendamento', 'erro');
            });
    }

    // =============================================
    // FEEDBACK
    // =============================================

    mostrarMensagem(mensagem, tipo) {
        const feedback = document.getElementById('mensagemFeedback');
        feedback.textContent = mensagem;
        feedback.classList.remove('sucesso', 'erro');
        feedback.classList.add(tipo);
    }

    mostrarMensagemCancelamento(mensagem, tipo) {
        const feedback = document.getElementById('mensagemFeedbackCancelamento');
        feedback.textContent = mensagem;
        feedback.classList.remove('sucesso', 'erro');
        feedback.classList.add(tipo);
    }

    configurarAutoFecharMensagens() {
        // Auto-fechar mensagens de sucesso após 3 segundos
        setInterval(() => {
            const feedbacks = document.querySelectorAll('.mensagem-feedback.sucesso');
            feedbacks.forEach((feedback) => {
                setTimeout(() => {
                    feedback.classList.remove('sucesso');
                }, 3000);
            });
        }, 500);
    }

    renderizarEstadoHorarios(mensagem, tipo = '') {
        const gridHorarios = document.getElementById('horariosGrid');
        if (!gridHorarios) return;

        gridHorarios.innerHTML = `
            <div class="horarios-status-message ${tipo}">
                ${this.escaparHtml(mensagem)}
            </div>
        `;
    }

    atualizarPassos(etapaAtual) {
        document.querySelectorAll('.progress-steps .step').forEach((step) => {
            const etapa = Number(step.dataset.step);
            step.classList.toggle('active', etapa === etapaAtual);
            step.classList.toggle('completed', etapa < etapaAtual);
        });
    }

    escaparHtml(valor) {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // =============================================
    // ATUALIZAÇÃO EM TEMPO REAL (Polling)
    // =============================================

    iniciarAtualizacaoEmTempoReal() {
        // Atualizar a cada 5 segundos
        setInterval(() => {
            this.carregarAgendamentos(this.diaAtual, false);
        }, 5000);
    }

    // =============================================
    // NOTIFICAÇÃO DE PROXIMIDADE
    // =============================================

    iniciarVerificacaoProximidade() {
        // Verificar a cada 30 segundos se há agendamentos próximos
        setInterval(() => {
            this.verificarAgendamentosProximos();
        }, 30000);
    }

    verificarAgendamentosProximos() {
        const agora = new Date();
        const diaAtual = this.obterDiaAtual();

        if (!diaAtual || !this.agendamentos[diaAtual]) return;

        this.agendamentos[diaAtual].forEach((agendamento) => {
            if (agendamento.cancelado || agendamento.notificacoes_enviadas?.proximidade) return;
            if (this.notificacoesProximidadePendentes.has(agendamento.id)) return;

            const [horas, minutos] = agendamento.horario.split(':');
            const [ano, mes, dia] = this.datasEvento[diaAtual].split('-').map(Number);
            const agendamentoTime = new Date(ano, mes - 1, dia, parseInt(horas), parseInt(minutos), 0);

            const diferenca = (agendamentoTime - agora) / 1000 / 60; // em minutos

            // Se está entre 10 e 15 minutos antes
            if (diferenca > 10 && diferenca <= 15) {
                this.notificacoesProximidadePendentes.add(agendamento.id);

                // Enviar notificação de proximidade via API
                fetch('/api/notificacao-proximidade', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        id: agendamento.id,
                    }),
                })
                    .then((response) => response.json())
                    .then((result) => {
                        if (result.sucesso) {
                            agendamento.notificacoes_enviadas = {
                                ...(agendamento.notificacoes_enviadas || {}),
                                proximidade: new Date().toISOString(),
                            };
                            this.adicionarNotificacaoUsuario('proximidade', 'Lembrete de proximidade', `Seu horário para ${this.escaparHtml(agendamento.equipe)} às ${agendamento.horario} está próximo.`);
                        } else {
                            this.notificacoesProximidadePendentes.delete(agendamento.id);
                        }
                    })
                    .catch((error) => {
                        this.notificacoesProximidadePendentes.delete(agendamento.id);
                        console.error('Erro ao enviar notificação de proximidade:', error);
                    });
            }
        });
    }

    obterDiaAtual() {
        const hoje = new Date();
        const ano = hoje.getFullYear();
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const dia = String(hoje.getDate()).padStart(2, '0');
        const dataAtual = `${ano}-${mes}-${dia}`;

        if (dataAtual === this.datasEvento.sexta) return 'sexta';
        if (dataAtual === this.datasEvento.sabado) return 'sabado';
        if (dataAtual === this.datasEvento.domingo) return 'domingo';

        return null;
    }

}

// =============================================
// FORMATAÇÃO DE TELEFONE
// =============================================

function formatarTelefone(valor) {
    valor = valor.replace(/\D/g, '');
    
    if (valor.length > 11) {
        return valor.slice(0, 11);
    }

    if (valor.length <= 2) {
        return valor;
    } else if (valor.length <= 6) {
        return `(${valor.slice(0, 2)}) ${valor.slice(2)}`;
    } else {
        return `(${valor.slice(0, 2)}) ${valor.slice(2, 7)}-${valor.slice(7)}`;
    }
}

// =============================================
// INICIALIZAÇÃO
// =============================================

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar sistema
    const sistema = new SistemaAgendamento();

    // Iniciar atualização em tempo real
    sistema.iniciarAtualizacaoEmTempoReal();

    // Iniciar verificação de proximidade
    sistema.iniciarVerificacaoProximidade();

    // Formatação de telefone
    const inputTelefone = document.getElementById('telefonePessoa');
    if (inputTelefone) {
        inputTelefone.addEventListener('input', (e) => {
            e.target.value = formatarTelefone(e.target.value);
        });
    }

    // Fechar modal ao pressionar ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            sistema.fecharModais();
        }
    });
});
