app.component('federative-entity-par-tree', {
    template: $TEMPLATES['federative-entity-par-tree'],

    setup() {
        const text = Utils.getTexts('federative-entity-par-tree')
        return { text }
    },

    data() {
        return {
            exercicios: [],
        }
    },

    mounted() {
        const dados = typeof $MAPAS !== 'undefined' ? $MAPAS.requestedEntity?.parExercicios : null
        this.exercicios = Array.isArray(dados) ? dados : []
    },

    computed: {
        hasExercicios() {
            return Array.isArray(this.exercicios) && this.exercicios.length > 0
        },
    },

    methods: {
        metasOf(exercicio) {
            return Array.isArray(exercicio?.metas) ? exercicio.metas : []
        },

        acoesOf(meta) {
            return Array.isArray(meta?.acoes) ? meta.acoes : []
        },

        atividadesOf(acao) {
            return Array.isArray(acao?.atividades) ? acao.atividades : []
        },

        formatCurrency(value) {
            const number = Number(value)

            if (!Number.isFinite(number)) {
                return ''
            }

            return number.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
        },
    },
})
