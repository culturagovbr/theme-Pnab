/** Lista de beneficiários: de onde vem cada coluna do modelo de planilha que o CultBR define, com autosave. */
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
            planilha: null,
            campos: [],
            colunas: [],
            dadosDaInscricao: [],
            mapeamento: {},
            equivalencias: {},
            valoresSemOpcao: {},
            recusas: {},
            tiposDeProponente: [],
            isSaving: false,
            pendingSave: false,
            salvoEm: null,
            autosaveTimeout: null,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'resultado-de-para', { cacheMode: 'no-store' });
        // colunas escolhidas aqui e ainda não gravadas
        this.colunasAlteradas = new Set();
        this.equivalenciasAlteradas = new Set();
        this.carregar();
    },

    mounted() {
        window.addEventListener('beforeunload', this.gravarAntesDeSair);
    },

    beforeUnmount() {
        window.removeEventListener('beforeunload', this.gravarAntesDeSair);
    },

    unmounted() {
        clearTimeout(this.autosaveTimeout);
    },

    computed: {
        temPlanilha() {
            return this.planilha?.possui === true;
        },

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

        idsDosCampos() {
            return new Set(this.campos.map((campo) => `campo:${campo.id}`));
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

                this.planilha = body.planilha ?? null;
                this.campos = body.campos ?? [];
                this.colunas = body.colunas ?? [];
                this.dadosDaInscricao = body.dadosDaInscricao ?? [];
                this.aplicar(body);
                this.tiposDeProponente = body.tiposDeProponente ?? [];
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

        aplicar(body) {
            this.mapeamento = body.mapeamento ?? {};
            this.equivalencias = body.equivalencias ?? {};
            this.valoresSemOpcao = body.valoresSemOpcao ?? {};
        },

        /** Campo ligado no de/para que não está mais na ficha do edital. */
        isCampoAusente(fonte) {
            return typeof fonte === 'string' && fonte.startsWith('campo:') && !this.idsDosCampos.has(fonte);
        },

        recusaDe(coluna, tipo) {
            return this.recusas[`${coluna.coluna}.${tipo}`] ?? null;
        },

        valoresDe(coluna) {
            return this.valoresSemOpcao[coluna.coluna] ?? [];
        },

        equivalenciaDe(coluna, valor) {
            return this.equivalencias[coluna.coluna]?.[valor] ?? '';
        },

        definirEquivalencia(coluna, valor, opcao) {
            this.equivalencias[coluna.coluna] = { ...(this.equivalencias[coluna.coluna] ?? {}), [valor]: opcao || null };
            this.equivalenciasAlteradas.add(coluna.coluna);
            this.agendarSalvar();
        },

        fonteDe(coluna, tipo = null) {
            const fonte = this.mapeamento[coluna.coluna];

            return (tipo === null ? fonte : fonte?.[tipo]) ?? '';
        },

        definir(coluna, fonte) {
            this.mapeamento[coluna.coluna] = fonte || null;
            this.colunasAlteradas.add(coluna.coluna);
            this.agendarSalvar();
        },

        /** Documento tem um campo por tipo de proponente. */
        definirPorTipo(coluna, tipo, fonte) {
            this.mapeamento[coluna.coluna] = { ...(this.mapeamento[coluna.coluna] ?? {}), [tipo]: fonte || null };
            this.colunasAlteradas.add(coluna.coluna);
            this.agendarSalvar();
        },

        exigenciaDe(coluna) {
            return coluna.condicao ?? this.translateMessage(coluna.obrigatoriedade === 'obrigatorio' ? 'obrigatoria' : 'opcional');
        },

        isAlteradoSemGravar() {
            return this.colunasAlteradas.size > 0 || this.equivalenciasAlteradas.size > 0;
        },

        gravarPendentes() {
            if (this.isAlteradoSemGravar()) {
                clearTimeout(this.autosaveTimeout);
                this.salvar();
            }
        },

        gravarAntesDeSair(event) {
            if (this.isAlteradoSemGravar() || this.isSaving) {
                this.gravarPendentes();
                event.preventDefault();
            }
        },

        agendarSalvar() {
            clearTimeout(this.autosaveTimeout);
            this.autosaveTimeout = setTimeout(() => this.salvar(), 1200);
        },

        async salvar() {
            this.autosaveTimeout = null;

            if (this.isSaving) {
                // Mudança durante o save em voo: salva de novo ao terminar, com o estado mais novo.
                this.pendingSave = true;
                return;
            }

            this.isSaving = true;
            const colunas = [...this.colunasAlteradas];
            const equivalencias = [...this.equivalenciasAlteradas];
            this.colunasAlteradas.clear();
            this.equivalenciasAlteradas.clear();
            const naoGravou = () => {
                colunas.forEach((coluna) => this.colunasAlteradas.add(coluna));
                equivalencias.forEach((coluna) => this.equivalenciasAlteradas.add(coluna));
            };

            try {
                // Só o que mudou: o mapa inteiro passaria por cima da escolha feita em outra guia.
                const url = this.api.createUrl('resultadoDePara', {});
                const response = await this.api.POST(url, {
                    opportunityId: this.entity.id,
                    mapeamento: Object.fromEntries(colunas.map((coluna) => [coluna, this.mapeamento[coluna] ?? null])),
                    equivalencias: Object.fromEntries(equivalencias.map((coluna) => [coluna, this.equivalencias[coluna] ?? {}])),
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    naoGravou();
                    this.messages.error(body?.data?.message ?? body?.data ?? this.translateMessage('erro_salvar'));
                    return;
                }

                this.recusas = body.recusas ?? {};

                // Com mudança mais nova em espera, aplicar o eco do servidor descartaria a edição em voo.
                if (!this.pendingSave && !this.autosaveTimeout) {
                    this.aplicar(body);
                }
                this.salvoEm = new Date();
            } catch (error) {
                naoGravou();
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
