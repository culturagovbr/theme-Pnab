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
            tiposDeProponente: [],
            documentoPorTipo: {},
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
        /** Campos agrupados pela etapa da ficha: o mesmo título se repete de uma etapa para outra. */
        gruposDeCampos() {
            const grupos = new Map();

            for (const campo of this.campos) {
                const titulo = this.tituloDoGrupo(campo);
                if (!grupos.has(titulo)) {
                    grupos.set(titulo, []);
                }
                grupos.get(titulo).push(campo);
            }

            return [...grupos].map(([titulo, campos]) => ({ titulo, campos }));
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
                this.tiposDeProponente = body.tiposDeProponente ?? [];
                this.documentoPorTipo = body.documentoPorTipo ?? {};
            } catch (error) {
                this.hasError = true;
            } finally {
                this.isLoading = false;
            }
        },

        tituloDoGrupo(campo) {
            const fases = new Set(this.campos.map((item) => item.fase));
            const etapa = campo.etapa || this.translateMessage('sem_etapa');

            return fases.size > 1 ? `${campo.fase} · ${etapa}` : etapa;
        },

        definir(coluna, valor) {
            this.mapeamento[coluna] = this.idDoCampo(valor);
            this.agendarSalvar();
        },

        definirDocumento(tipo, valor) {
            this.documentoPorTipo[tipo] = this.idDoCampo(valor);
            this.agendarSalvar();
        },

        idDoCampo(valor) {
            return valor === '' ? null : Number(valor);
        },

        agendarSalvar() {
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
                const response = await this.api.POST(url, {
                    opportunityId: this.entity.id,
                    mapeamento: this.mapeamento,
                    documentoPorTipo: this.documentoPorTipo,
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.messages.error(body?.data?.message ?? body?.data ?? this.translateMessage('erro_salvar'));
                    return;
                }

                // Com mudança mais nova em espera, aplicar o eco do servidor descartaria a edição em voo.
                if (!this.pendingSave) {
                    this.mapeamento = body.mapeamento ?? this.mapeamento;
                    this.documentoPorTipo = body.documentoPorTipo ?? this.documentoPorTipo;
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
