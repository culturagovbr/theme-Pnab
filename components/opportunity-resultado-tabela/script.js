/** Questão de tabela do formulário de resultado, nas duas formas que o PAR publica (`tabela` e `Tabela Dinâmica`). */
app.component('opportunity-resultado-tabela', {
    template: $TEMPLATES['opportunity-resultado-tabela'],
    emits: ['definir'],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-resultado-tabela');
        return { translateMessage };
    },

    props: {
        questao: {
            type: Object,
            required: true,
        },
        linhas: {
            type: Array,
            default: () => [],
        },
        somenteLeitura: {
            type: Boolean,
            default: false,
        },
    },

    computed: {
        config() {
            return this.questao.config ?? {};
        },

        isSimples() {
            return Boolean(this.config.tabela);
        },

        colunas() {
            return this.isSimples
                ? (this.config.tabela.colunas ?? []).map(this.colunaSimples)
                : (this.config.columns ?? []).map(this.colunaDinamica);
        },

        minLinhas() {
            return Number((this.isSimples ? this.config.tabela.minLinhas : this.config.minRows) ?? 1);
        },

        maxLinhas() {
            return (this.isSimples ? this.config.tabela.maxLinhas : this.config.maxRows) ?? null;
        },

        podeAdicionar() {
            const acoes = this.isSimples ? this.config.tabela.acoesPermitidas : this.config.actions;

            return !this.somenteLeitura && this.permite(acoes, ['adicionar', 'create']);
        },

        podeRemover() {
            const acoes = this.isSimples ? this.config.tabela.acoesPermitidas : this.config.actions;

            return !this.somenteLeitura && this.permite(acoes, ['excluir', 'delete']);
        },

        isNoLimite() {
            return this.maxLinhas !== null && this.linhasExibidas.length >= this.maxLinhas;
        },

        rotuloAdicionar() {
            return this.config.buttonLabel || this.config.tabela?.textoBotaoAdicionar || this.translateMessage('adicionar_linha');
        },

        /** O mínimo de linhas já aparece em branco para preencher. */
        linhasExibidas() {
            if (this.somenteLeitura) {
                return this.linhas;
            }

            const emBranco = Math.max(0, this.minLinhas - this.linhas.length);

            return [...this.linhas, ...Array.from({ length: emBranco }, () => ({}))];
        },
    },

    methods: {
        colunaSimples(coluna) {
            return {
                id: coluna.id,
                nome: coluna.nome,
                tipo: coluna.tipo,
                obrigatoria: Boolean(coluna.obrigatoria),
                somenteLeitura: Boolean(coluna.somenteLeitura),
                opcoes: coluna.opcoes ?? [],
                limite: coluna.maxCaracteres ?? null,
                minimo: coluna.valorMinimo ?? null,
                maximo: coluna.valorMaximo ?? null,
                mascara: coluna.mascara ?? null,
                placeholder: coluna.placeholder ?? '',
            };
        },

        /** Na dinâmica, `max` conta caracteres na coluna de texto e valor nas numéricas. */
        colunaDinamica(coluna) {
            return {
                id: coluna.key,
                nome: coluna.label,
                tipo: coluna.type,
                obrigatoria: Boolean(coluna.required),
                somenteLeitura: Boolean(coluna.readOnly),
                opcoes: coluna.dropdownOptions?.values ?? [],
                limite: coluna.max ?? null,
                minimo: coluna.min ?? null,
                maximo: coluna.max ?? null,
                mascara: coluna.mask ?? null,
                placeholder: coluna.placeholder ?? '',
            };
        },

        /** Sem a lista de ações, a tabela é livre. */
        permite(acoes, nomes) {
            return !acoes || nomes.some((nome) => acoes.includes(nome));
        },

        isFixa(coluna) {
            return this.somenteLeitura || coluna.somenteLeitura;
        },

        campo(coluna) {
            if (this.isFixa(coluna)) {
                return 'fixo';
            }

            return {
                text: 'texto', number: 'numero', currency: 'moeda', dropdown: 'lista', escolha_unica: 'lista',
            }[coluna.tipo] ?? 'desconhecido';
        },

        mascaraDe(coluna) {
            return resultadoMascara(coluna.mascara);
        },

        exibicao(coluna, linha) {
            const valor = linha[coluna.id];

            if (valor === null || valor === undefined || valor === '') {
                return '—';
            }

            if (coluna.tipo === 'currency') {
                return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            }

            return coluna.tipo === 'number' ? Number(valor).toLocaleString('pt-BR') : String(valor);
        },

        valorDe(coluna, linha) {
            return linha[coluna.id] ?? '';
        },

        moedaDe(coluna, linha) {
            const valor = linha[coluna.id];

            return valor === null || valor === undefined || valor === '' ? null : Number(valor);
        },

        definir(indice, coluna, valor) {
            const novo = valor === '' ? null : valor;

            if ((this.linhasExibidas[indice][coluna.id] ?? null) === novo) {
                return;
            }

            const linhas = this.linhasExibidas.map((linha) => ({ ...linha }));
            linhas[indice][coluna.id] = novo;
            this.$emit('definir', linhas);
        },

        adicionar() {
            this.$emit('definir', [...this.linhasExibidas, {}]);
        },

        remover(indice) {
            this.$emit('definir', this.linhasExibidas.filter((_, i) => i !== indice));
        },
    },
});
