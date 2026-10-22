/**
 * Card "Cadastrar Resultado" da Configuração de fases: parece uma fase, mas é a porta do
 * formulário de resultado do CultBR. Consome GET/POST aldirblanc/resultadoFormulario.
 */

/** Estados devolvidos pelo endpoint; sem-formulario esconde o card por regra de negócio. */
const RESULTADO_ESTADOS = {
    semFormulario: 'sem-formulario',
    aguardandoPublicacao: 'aguardando-publicacao',
    pendente: 'pendente',
    completo: 'completo',
    enviado: 'enviado',
};

/** Rótulo de cada estado visível no chip do card. */
const RESULTADO_ESTADO_KEYS = {
    'aguardando-publicacao': 'estado_aguardando_publicacao',
    'pendente': 'estado_pendente',
    'completo': 'estado_completo',
    'enviado': 'estado_enviado',
};

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
            estrutura: null,
            respostas: {},
            respostasAutomaticas: {},
            questoesSemFonte: [],
            envioHabilitado: false,
            errosFormulario: {},
            isSaving: false,
            isEnviando: false,
            salvoEm: null,
            pendingSave: null,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'resultado-formulario', { cacheMode: 'no-store' });

        if (this.preloaded) {
            this.aplicarEstado(this.preloaded);
            this.visivel = true;
            this.isLoading = false;
        } else {
            this.load();
        }
    },

    computed: {
        travado() {
            return this.estado === RESULTADO_ESTADOS.aguardandoPublicacao;
        },

        enviado() {
            return this.estado === RESULTADO_ESTADOS.enviado;
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

        aplicarEstado(body) {
            this.estado = body.estado;
            this.estrutura = body.estrutura;
            this.respostas = body.respostas ?? {};
            this.respostasAutomaticas = body.respostasAutomaticas ?? {};
            this.questoesSemFonte = body.questoesSemFonte ?? [];
            this.envioHabilitado = Boolean(body.envioHabilitado);
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
                    const motivo = typeof body?.data === 'string' ? body.data : null;
                    this.messages.error(motivo ?? this.translateMessage('erro_enviar'));
                    return;
                }

                this.messages.success(this.translateMessage('envio_agendado'));
                await this.load();
            } catch (error) {
                this.messages.error(this.translateMessage('erro_enviar'));
            } finally {
                this.isEnviando = false;
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
                const response = await this.api.POST(url, { opportunityId: this.entity.id, respostas: valores });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.errosFormulario = body?.data?.respostas ?? {};
                    const motivo = body?.data?.message ?? (typeof body?.data === 'string' ? body.data : null);
                    this.messages.error(motivo ?? this.translateMessage('erro_salvar'));
                    return;
                }

                this.errosFormulario = {};
                this.aplicarEstado(body);
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
