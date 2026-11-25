/**
 * Card "Cadastrar Resultado" da Configuração de fases: parece uma fase, mas é a porta do
 * formulário de resultado do CultBR. Consome GET/POST aldirblanc/resultadoFormulario.
 */

/** Estados devolvidos pelo endpoint; sem-formulario esconde o card por regra de negócio. */
const RESULTADO_ESTADOS = {
    semFormulario: 'sem-formulario',
    acaoSemResultado: 'acao-sem-resultado',
    editalNaoPublicado: 'edital-nao-publicado',
    aguardandoPublicacao: 'aguardando-publicacao',
    pendente: 'pendente',
    completo: 'completo',
    enviado: 'enviado',
};

/** Rótulo de cada estado visível no chip do card. */
const RESULTADO_ESTADO_KEYS = {
    'acao-sem-resultado': 'estado_acao_sem_resultado',
    'edital-nao-publicado': 'estado_edital_nao_publicado',
    'aguardando-publicacao': 'estado_aguardando_publicacao',
    'pendente': 'estado_pendente',
    'completo': 'estado_completo',
    'enviado': 'estado_enviado',
};

/** Alerta de cada situação do último envio. */
const RESULTADO_ENVIO_ALERTAS = {
    'aguardando': 'helper',
    'enviando': 'helper',
    'publicado': 'success',
    'simulado': 'helper',
    'nada-mudou': 'helper',
    'sem-confirmacao': 'warning',
    'recusado': 'danger',
    'nao-enviado': 'danger',
};

const RESULTADO_ENVIO_EM_ANDAMENTO = ['aguardando', 'enviando'];

const RESULTADO_TIPO_RETIFICADO = 3;

/** Intervalo da releitura enquanto o envio está em andamento. */
const RESULTADO_RELEITURA_MS = 5000;

app.component('opportunity-resultado-cultbr-card', {
    template: $TEMPLATES['opportunity-resultado-cultbr-card'],
    emits: [],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-resultado-cultbr-card');
        const messages = useMessages();
        return { translateMessage, messages };
    },

    props: {
        entity: {
            type: Entity,
            required: true,
        },
        // Estado já carregado pela aba: evita refazer o GET que ela já fez para decidir se aparece.
        preloaded: {
            type: Object,
            default: null,
        },
    },

    data() {
        return {
            visivel: false,
            isLoading: true,
            hasError: false,
            estado: null,
            motivo: null,
            estrutura: null,
            respostas: {},
            respostasAutomaticas: {},
            questoesSemFonte: [],
            envioHabilitado: false,
            pendencias: [],
            bloqueios: [],
            planilha: null,
            anexos: {},
            anexando: null,
            errosDosAnexos: {},
            ultimoEnvio: null,
            releituraTimeout: null,
            isFormularioAtualizado: false,
            isAlterado: false,
            errosFormulario: {},
            isSaving: false,
            isEnviando: false,
            salvoEm: null,
            pendingSave: null,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'resultado-formulario', { cacheMode: 'no-store' });
        this.gravacao = Promise.resolve();

        if (this.preloaded) {
            this.aplicarEstado(this.preloaded);
            this.visivel = true;
            this.isLoading = false;
        } else {
            this.load();
        }
    },

    unmounted() {
        clearTimeout(this.releituraTimeout);
    },

    computed: {
        travado() {
            return [RESULTADO_ESTADOS.aguardandoPublicacao, RESULTADO_ESTADOS.acaoSemResultado, RESULTADO_ESTADOS.editalNaoPublicado].includes(this.estado);
        },

        travadoAviso() {
            return this.motivo ?? this.translateMessage('travado_aviso');
        },

        enviado() {
            return this.estado === RESULTADO_ESTADOS.enviado;
        },

        temErros() {
            return Object.keys(this.errosFormulario).length > 0;
        },

        /** O arquivo recusado não foi guardado: aparece no campo, mas não impede o envio. */
        errosDosCampos() {
            return { ...this.recusasDasQuestoes, ...this.errosFormulario, ...this.errosDosAnexos };
        },

        /** O que o CultBR recusou no último envio, por questão, até a resposta ser alterada. */
        recusasDasQuestoes() {
            if (this.ultimoEnvio?.situacao !== 'recusado') {
                return {};
            }

            const recusadas = this.ultimoEnvio.recusas.filter((recusa) => recusa.tipo === 'questao' && recusa.id_questao && !recusa.alterada);

            return Object.fromEntries(recusadas.map((recusa) => [recusa.id_questao, recusa.descricao || this.translateMessage('recusada_pelo_cultbr')]));
        },

        podeEnviar() {
            return this.envioHabilitado && !this.isEnvioNoPrazo && !this.temErros
                && !this.isAlterado && !this.isSaving && !this.isEnviando && this.anexando === null;
        },

        /** O pedido está na fila ou saindo para o CultBR, dentro do prazo. */
        isEnvioEmAndamento() {
            return RESULTADO_ENVIO_EM_ANDAMENTO.includes(this.ultimoEnvio?.situacao) && this.isEnvioNoPrazo;
        },

        rotuloDoEnvio() {
            if (this.isEnviando || this.isEnvioEmAndamento) {
                return this.translateMessage('em_andamento');
            }

            return this.translateMessage(this.enviado ? 'retificar' : 'enviar');
        },

        /** Enquanto o envio anterior pode estar sendo processado, não cabe outro. */
        isEnvioNoPrazo() {
            return Boolean(this.ultimoEnvio?.novoPedidoEm);
        },

        envioAlerta() {
            return RESULTADO_ENVIO_ALERTAS[this.ultimoEnvio?.situacao] ?? null;
        },

        envioTexto() {
            const { situacao, em, tipo, novoPedidoEm } = this.ultimoEnvio;
            const chave = situacao === 'sem-confirmacao' && novoPedidoEm ? 'sem-confirmacao-no-prazo' : situacao;

            return this.translateMessage('envio_' + chave.replaceAll('-', '_'), {
                data: this.dataHora(em),
                hora: novoPedidoEm ? this.hora(novoPedidoEm) : '',
                tipo: this.translateMessage(tipo === RESULTADO_TIPO_RETIFICADO ? 'tipo_retificado' : 'tipo_final'),
            });
        },

        /** Recusa ou pendência de linha se resolve na lista de beneficiários. */
        temRecusaDeLinha() {
            return this.ultimoEnvio.recusas.some((recusa) => recusa.tipo === 'linha');
        },

        pendentesTexto() {
            const total = this.ultimoEnvio.totalPendentes;
            const listadas = new Set(this.ultimoEnvio.recusas.map((recusa) => recusa.inscricao)).size;
            const contagem = this.translateMessage(total === 1 ? 'pendentes_um' : 'pendentes_varios', { total });

            return total > listadas ? `${contagem} ${this.translateMessage('pendentes_listadas', { listadas })}` : contagem;
        },

        estadoLabel() {
            const key = RESULTADO_ESTADO_KEYS[this.estado];
            return key ? this.translateMessage(key) : '';
        },
    },

    methods: {
        async load() {
            this.isLoading = true;
            this.hasError = false;

            try {
                const url = this.api.createUrl('resultadoFormulario', { opportunityId: this.entity.id });
                const response = await this.api.GET(url);
                const body = await response.json().catch(() => ({}));

                // Sem permissão ou sem formulário no PAR: o card simplesmente não existe.
                if (response.status === 403 || body?.estado === RESULTADO_ESTADOS.semFormulario) {
                    this.visivel = false;
                    return;
                }

                if (!response.ok) {
                    throw new Error(body?.data ?? this.translateMessage('erro_carregar'));
                }

                this.aplicarEstado(body);
                this.visivel = true;
            } catch (error) {
                // Indisponibilidade não é "sem formulário": o card aparece com o erro e o tentar de novo.
                this.visivel = true;
                this.hasError = true;
            } finally {
                this.isLoading = false;
            }
        },

        /** Troca também as respostas da tela: só na abertura e quando a versão do formulário muda. */
        aplicarEstado(body) {
            this.estrutura = body.estrutura;
            this.respostas = body.respostas ?? {};
            this.respostasAutomaticas = body.respostasAutomaticas ?? {};
            this.questoesSemFonte = body.questoesSemFonte ?? [];
            this.isFormularioAtualizado = this.isFormularioAtualizado || Boolean(body.versaoAlterada);
            this.aplicarSituacao(body);
        },

        aplicarSituacao(body) {
            this.estado = body.estado;
            this.motivo = body.motivo ?? null;
            this.envioHabilitado = Boolean(body.envioHabilitado);
            this.pendencias = body.pendencias ?? [];
            this.bloqueios = body.bloqueios ?? [];
            this.planilha = body.planilha ?? null;
            this.anexos = body.anexos ?? {};
            this.ultimoEnvio = body.ultimoEnvio ?? null;
            this.errosFormulario = body.erros ?? {};
            this.agendarReleitura();
        },

        agendarReleitura() {
            clearTimeout(this.releituraTimeout);

            if (this.ultimoEnvio?.novoPedidoEm) {
                const atePrazo = Math.max(new Date(this.ultimoEnvio.novoPedidoEm) - Date.now(), 0) + 1000;
                const espera = RESULTADO_ENVIO_EM_ANDAMENTO.includes(this.ultimoEnvio.situacao) ? RESULTADO_RELEITURA_MS : atePrazo;
                this.releituraTimeout = setTimeout(() => this.relerSituacao(), espera);
            }
        },

        dataHora(iso) {
            return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
        },

        hora(iso) {
            return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        },

        rotuloDaRecusa(recusa) {
            if (recusa.tipo !== 'linha') {
                return recusa.nome;
            }

            return recusa.inscricao
                ? this.translateMessage('recusa_inscricao', { inscricao: recusa.inscricao, coluna: recusa.coluna })
                : this.translateMessage('recusa_linha', { linha: recusa.indice + 1, coluna: recusa.coluna });
        },

        isOutraVersao(body) {
            return body.estrutura?.id_formulario_versao !== this.estrutura?.id_formulario_versao;
        },

        /** Atualiza a situação sem tocar no que o gestor está digitando. */
        async relerSituacao() {
            try {
                const url = this.api.createUrl('resultadoFormulario', { opportunityId: this.entity.id });
                const response = await this.api.GET(url);
                const body = await response.json();

                if (response.ok) {
                    this.aplicarSituacao({ ...body, erros: this.errosFormulario });
                    return;
                }
            } catch (error) {
                console.error(error);
            }

            // A releitura que falhou não encerra o acompanhamento.
            this.agendarReleitura();
        },

        /**
         * Enviar e Retificar são a mesma chamada: publicado é imutável, e o retificado é um
         * resultado novo que o servidor monta sozinho quando já existe um. O envio é enfileirado
         * porque edital grande leva minutos para montar o corpo.
         */
        async enviar() {
            if (this.isEnviando) {
                return;
            }

            this.isEnviando = true;

            try {
                const url = this.api.createUrl('resultadoEnviar', {});
                const response = await this.api.POST(url, { opportunityId: this.entity.id });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const motivo = body?.data?.message ?? (typeof body?.data === 'string' ? body.data : null);
                    this.pendencias = body?.data?.pendencias ?? this.pendencias;
                    this.bloqueios = body?.data?.bloqueios ?? this.bloqueios;
                    this.messages.error(motivo ?? this.translateMessage('erro_enviar'));
                    await this.relerSituacao();
                    return;
                }

                this.messages.success(this.translateMessage('envio_agendado'));
                await this.relerSituacao();
            } catch (error) {
                this.messages.error(this.translateMessage('erro_enviar'));
            } finally {
                this.isEnviando = false;
            }
        },

        /** Respostas e arquivos gravam o mesmo registro no servidor: uma gravação por vez. */
        gravar(url, dados) {
            const resposta = this.gravacao.then(() => this.api.POST(url, dados));
            this.gravacao = resposta.catch(() => {});
            return resposta;
        },

        anexar(questao, arquivo) {
            const dados = new FormData();
            dados.append('id_formulario_versao', this.estrutura.id_formulario_versao);
            dados.append('id_questao', questao.id_questao);
            dados.append('arquivo', arquivo);

            return this.alterarAnexos(questao, this.api.createUrl('resultadoAnexo', { opportunityId: this.entity.id }), dados);
        },

        retirar(questao, idArquivo) {
            return this.alterarAnexos(questao, this.api.createUrl('resultadoAnexoRetirar', {}), {
                opportunityId: this.entity.id,
                id_formulario_versao: this.estrutura.id_formulario_versao,
                id_questao: questao.id_questao,
                id_arquivo: idArquivo,
            });
        },

        async alterarAnexos(questao, url, dados) {
            this.anexando = questao.id_questao;
            delete this.errosDosAnexos[questao.id_questao];

            try {
                const response = await this.gravar(url, dados);
                const body = await response.json().catch(() => ({}));

                if (!response.ok || !body.estrutura) {
                    const motivo = typeof body?.data === 'string' ? body.data : null;
                    this.errosDosAnexos[questao.id_questao] = motivo ?? this.translateMessage('erro_anexo');
                    return;
                }

                if (this.isOutraVersao(body)) {
                    this.pendingSave = null;
                    this.errosDosAnexos = {};
                    this.aplicarEstado(body);
                    return;
                }

                this.aplicarSituacao({ ...body, erros: this.errosFormulario });
            } catch (error) {
                this.errosDosAnexos[questao.id_questao] = this.translateMessage('erro_anexo');
            } finally {
                this.anexando = null;
            }
        },

        async salvarRespostas(valores) {
            if (this.isSaving) {
                // Digitação durante o save em voo: guarda a versão mais nova e salva ao terminar.
                this.pendingSave = valores;
                return;
            }

            this.isSaving = true;

            try {
                const url = this.api.createUrl('resultadoFormulario', {});
                const response = await this.gravar(url, {
                    opportunityId: this.entity.id,
                    id_formulario_versao: this.estrutura.id_formulario_versao,
                    respostas: valores,
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const motivo = typeof body?.data === 'string' ? body.data : null;
                    this.messages.error(motivo ?? this.translateMessage('erro_salvar'));
                    return;
                }

                if (this.isOutraVersao(body)) {
                    // As respostas na fila são da versão que saiu.
                    this.pendingSave = null;
                    this.aplicarEstado(body);
                    return;
                }

                this.aplicarSituacao(body);
                // Autosave: sem toast a cada digitação — o formulário mostra "Salvo às HH:MM".
                this.salvoEm = new Date();
            } catch (error) {
                this.messages.error(this.translateMessage('erro_salvar'));
            } finally {
                this.isSaving = false;

                if (this.pendingSave) {
                    const pendente = this.pendingSave;
                    this.pendingSave = null;
                    this.salvarRespostas(pendente);
                }
            }
        },
    },
});
