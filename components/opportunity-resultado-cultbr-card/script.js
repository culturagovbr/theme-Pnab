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
    },

    data() {
        return {
            visivel: false,
            expanded: false,
            isLoading: true,
            hasError: false,
            estado: null,
            estrutura: null,
            respostas: {},
            envioHabilitado: false,
            isSending: false,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'resultado-formulario', { cacheMode: 'no-store' });
        this.load();
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

                this.estado = body.estado;
                this.estrutura = body.estrutura;
                this.respostas = body.respostas ?? {};
                this.envioHabilitado = Boolean(body.envioHabilitado);
                this.visivel = true;
                this.$nextTick(() => this.estenderTrilhaDoStepper());
            } catch (error) {
                // Indisponibilidade não é "sem formulário": o card aparece com o erro e o tentar de novo.
                this.visivel = true;
                this.hasError = true;
            } finally {
                this.isLoading = false;
            }
        },

        /** O stepper apaga a trilha na última fase; com o card presente, ela segue até ele. */
        estenderTrilhaDoStepper() {
            this.$el?.parentElement
                ?.querySelector('.opportunity-phases-config')
                ?.classList.add('com-resultado-cultbr');
        },

        toggle() {
            if (this.travado) {
                return;
            }

            this.expanded = !this.expanded;
        },

        async enviar() {
            if (!this.envioHabilitado || this.isSending) {
                return;
            }

            this.isSending = true;

            try {
                const url = this.api.createUrl('resultadoEnvio', {});
                const response = await this.api.POST(url, { opportunityId: this.entity.id });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(body?.data ?? this.translateMessage('erro_enviar'));
                }

                this.messages.success(this.translateMessage('envio_aceito'));
                await this.load();
            } catch (error) {
                this.messages.error(error?.message ?? this.translateMessage('erro_enviar'));
            } finally {
                this.isSending = false;
            }
        },
    },
});
