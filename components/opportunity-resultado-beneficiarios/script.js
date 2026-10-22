/** Lista de beneficiários: de/para das colunas da planilha → campos da ficha do edital, com autosave. */
app.component('opportunity-resultado-beneficiarios', {
    template: $TEMPLATES['opportunity-resultado-beneficiarios'],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-resultado-beneficiarios');
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
            isLoading: true,
            hasError: false,
            campos: [],
            colunas: [],
            mapeamento: {},
            isSaving: false,
            pendingSave: false,
            salvoEm: null,
            autosaveTimeout: null,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'resultado-de-para', { cacheMode: 'no-store' });
        this.carregar();
    },

    unmounted() {
        clearTimeout(this.autosaveTimeout);
    },

    computed: {
        /** Opções do seletor com busca: "não se aplica" + cada campo da ficha (com a fase, se houver mais de uma). */
        opcoesSelect() {
            const fases = new Set(this.campos.map((campo) => campo.fase));
            const comFase = fases.size > 1;

            return [
                { value: '', label: this.translateMessage('nao_se_aplica') },
                ...this.campos.map((campo) => ({
                    value: campo.id,
                    label: comFase ? `${campo.title} — ${campo.fase}` : campo.title,
                })),
            ];
        },
    },

    methods: {
        async carregar() {
            this.isLoading = true;
            this.hasError = false;

            try {
                const url = this.api.createUrl('resultadoDePara', { opportunityId: this.entity.id });
                const response = await this.api.GET(url);
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(body?.data ?? this.translateMessage('erro_carregar'));
                }

                this.campos = body.campos ?? [];
                this.colunas = body.colunas ?? [];
                this.mapeamento = body.mapeamento ?? {};
            } catch (error) {
                this.hasError = true;
            } finally {
                this.isLoading = false;
            }
        },

        definir(coluna, opcao) {
            const valor = opcao?.value ?? '';
            this.mapeamento[coluna] = valor === '' ? null : Number(valor);

            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.salvar(), 1200);
        },

        async salvar() {
            if (this.isSaving) {
                // Mudança durante o save em voo: salva de novo ao terminar, com o estado mais novo.
                this.pendingSave = true;
                return;
            }

            this.isSaving = true;

            try {
                const url = this.api.createUrl('resultadoDePara', {});
                const response = await this.api.POST(url, { opportunityId: this.entity.id, mapeamento: this.mapeamento });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.messages.error(body?.data?.message ?? body?.data ?? this.translateMessage('erro_salvar'));
                    return;
                }

                // Com mudança mais nova em espera, aplicar o eco do servidor descartaria a edição em voo.
                if (!this.pendingSave) {
                    this.mapeamento = body.mapeamento ?? this.mapeamento;
                }
                this.salvoEm = new Date();
            } catch (error) {
                this.messages.error(this.translateMessage('erro_salvar'));
            } finally {
                this.isSaving = false;

                if (this.pendingSave) {
                    this.pendingSave = false;
                    this.salvar();
                }
            }
        },
    },
});
