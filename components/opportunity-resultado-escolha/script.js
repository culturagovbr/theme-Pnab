/**
 * Questão de escolha do formulário de resultado (`radio`, `checkbox` e `múltipla escolha`). O valor é
 * o id do item ou, no item com campo complementar, {id, complemento}; fora do `radio`, uma lista deles.
 */
app.component('opportunity-resultado-escolha', {
    template: $TEMPLATES['opportunity-resultado-escolha'],
    emits: ['definir'],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-resultado-escolha');
        return { translateMessage };
    },

    props: {
        questao: {
            type: Object,
            required: true,
        },
        valor: {
            type: [String, Object, Array],
            default: null,
        },
        somenteLeitura: {
            type: Boolean,
            default: false,
        },
    },

    computed: {
        isUnica() {
            return this.questao.tipo_questao === 'radio';
        },

        itens() {
            return this.questao.opcao?.itens ?? [];
        },

        escolhas() {
            if (Array.isArray(this.valor)) {
                return this.valor;
            }

            return this.valor ? [this.valor] : [];
        },

        limite() {
            return this.questao.opcao?.limiteMarcacoes ?? null;
        },

        dica() {
            const minimo = this.questao.opcao?.limiteMinimoMarcacoes ?? null;

            return !this.isUnica && minimo > 1 ? this.translateMessage('minimo', { minimo }) : '';
        },
    },

    methods: {
        idDe(escolha) {
            return typeof escolha === 'object' ? escolha?.id : escolha;
        },

        escolhaDe(item) {
            return this.escolhas.find((escolha) => this.idDe(escolha) === item.id);
        },

        isMarcado(item) {
            return this.escolhaDe(item) !== undefined;
        },

        /** No limite de marcações, só dá para desmarcar ou trocar por uma exclusiva. */
        isBloqueado(item) {
            if (this.somenteLeitura) {
                return true;
            }

            return !this.isUnica && !this.isMarcado(item) && !item.exclusiva
                && this.limite !== null && this.escolhas.length >= this.limite;
        },

        novaEscolha(item) {
            return item.campoComplementar ? { id: item.id, complemento: '' } : item.id;
        },

        alternar(item) {
            if (this.isUnica) {
                this.$emit('definir', this.novaEscolha(item));
                return;
            }

            if (this.isMarcado(item)) {
                this.$emit('definir', this.escolhas.filter((escolha) => this.idDe(escolha) !== item.id));
                return;
            }

            const mantidas = item.exclusiva ? [] : this.escolhas.filter((escolha) => !this.isExclusiva(escolha));
            this.$emit('definir', [...mantidas, this.novaEscolha(item)]);
        },

        isExclusiva(escolha) {
            return Boolean(this.itens.find((item) => item.id === this.idDe(escolha))?.exclusiva);
        },

        complementoDe(item) {
            return this.escolhaDe(item)?.complemento ?? '';
        },

        definirComplemento(item, complemento) {
            const nova = { id: item.id, complemento };

            this.$emit('definir', this.isUnica
                ? nova
                : this.escolhas.map((escolha) => (this.idDe(escolha) === item.id ? nova : escolha)));
        },
    },
});
