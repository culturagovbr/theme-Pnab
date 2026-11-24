/** Questão de documento do formulário de resultado; quem guarda e retira o arquivo é o card. */

/** Teto do CultBR por arquivo. */
const ANEXO_TAMANHO_MAXIMO_MB = 10;

app.component('opportunity-resultado-anexo', {
    template: $TEMPLATES['opportunity-resultado-anexo'],
    emits: ['anexar', 'retirar'],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-resultado-anexo');
        return { translateMessage };
    },

    props: {
        questao: {
            type: Object,
            required: true,
        },
        arquivos: {
            type: Array,
            default: () => [],
        },
        // Há um arquivo em trânsito nesta questão.
        enviando: {
            type: Boolean,
            default: false,
        },
        // Há um arquivo em trânsito em qualquer questão.
        bloqueado: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            isGrandeDemais: false,
        };
    },

    computed: {
        isMultipla() {
            return Boolean(this.questao.config?.multiplos) || this.questao.tipo_questao === 'múltiplos anexos';
        },

        podeAnexar() {
            return this.isMultipla || !this.arquivos.length;
        },

        formatos() {
            return this.questao.config?.formatos ?? [];
        },

        tamanho() {
            return Math.min(this.questao.config?.tamanhoMaxMb ?? ANEXO_TAMANHO_MAXIMO_MB, ANEXO_TAMANHO_MAXIMO_MB);
        },

        dica() {
            return [
                this.formatos.length ? this.translateMessage('formatos', { formatos: this.formatos.join(', ') }) : null,
                this.translateMessage('tamanho', { tamanho: this.tamanho }),
                this.translateMessage(this.isMultipla ? 'varios_arquivos' : 'um_arquivo'),
            ].filter(Boolean).join(' ');
        },
    },

    methods: {
        anexar(event) {
            const [arquivo] = event.target.files;
            // Sem limpar, escolher de novo o mesmo arquivo não dispara o evento.
            event.target.value = '';

            // O arquivo que passa do limite nem sai: o servidor descartaria o envio inteiro.
            this.isGrandeDemais = arquivo?.size > this.tamanho * 1024 * 1024;

            if (arquivo && !this.isGrandeDemais) {
                this.$emit('anexar', arquivo);
            }
        },
    },
});
