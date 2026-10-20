/**
 * Formulário dinâmico do resultado CultBR: renderiza qualquer estrutura publicada pelo PAR
 * (seções > etapas > questões), campo a campo por tipo de questão. Quem persiste é o card,
 * que recebe os valores pelo evento `salvar`.
 */

/** Questões sem campo nesta fase da integração. */
const FORMULARIO_TIPOS_OCULTOS = ['anexo'];

/** A data de publicação é automática em qualquer formulário do PAR — a tag a identifica. */
const FORMULARIO_TAG_DATA_PUBLICACAO = 'data_publicacao';

app.component('opportunity-resultado-formulario', {
    template: $TEMPLATES['opportunity-resultado-formulario'],
    emits: ['salvar'],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-resultado-formulario');
        return { translateMessage };
    },

    props: {
        entity: {
            type: Entity,
            required: true,
        },
        estrutura: {
            type: Object,
            required: true,
        },
        respostas: {
            type: Object,
            default: () => ({}),
        },
        erros: {
            type: Object,
            default: () => ({}),
        },
        dataPublicacao: {
            type: String,
            default: null,
        },
        salvando: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            // Cópia editável; o que está salvo continua sendo do card.
            valores: this.clonarRespostas(),
        };
    },

    watch: {
        respostas() {
            this.valores = this.clonarRespostas();
        },
    },

    computed: {
        etapas() {
            return (this.estrutura.secoes ?? []).flatMap((secao) => secao.etapas ?? []);
        },
    },

    methods: {
        clonarRespostas() {
            const bruto = JSON.parse(JSON.stringify(this.respostas ?? {}));

            // Vazio chega do PHP como []; como Array, os ids virariam índices esparsos no payload.
            return Array.isArray(bruto) ? Object.fromEntries(Object.entries(bruto)) : bruto;
        },

        questoesVisiveis(etapa) {
            return (etapa.questoes ?? []).filter(
                (questao) => !FORMULARIO_TIPOS_OCULTOS.includes(questao.tipo_questao)
                    && this.dependenciaSatisfeita(questao),
            );
        },

        dependenciaSatisfeita(questao) {
            if (!questao.depende_de) {
                return true;
            }

            const valor = this.valores[questao.depende_de];

            return !(valor === undefined || valor === null || valor === ''
                || (Array.isArray(valor) && !valor.length));
        },

        tipoDoCampo(questao) {
            return this.isDataPublicacao(questao) ? 'data-automatica' : questao.tipo_questao;
        },

        isDataPublicacao(questao) {
            return (questao.tag ?? '').startsWith(FORMULARIO_TAG_DATA_PUBLICACAO);
        },

        erroDe(questao) {
            return this.erros?.[questao.id_questao] ?? this.erros?.[String(questao.id_questao)] ?? null;
        },

        valorDe(questao) {
            return this.valores[questao.id_questao];
        },

        definir(questao, valor) {
            this.valores[questao.id_questao] = valor;
        },

        definirTexto(questao, texto) {
            this.definir(questao, texto === '' ? null : texto);
        },

        numeroDe(questao) {
            const valor = this.valorDe(questao);
            return valor === null || valor === undefined ? '' : valor;
        },

        moedaDe(questao) {
            const valor = Number(this.valorDe(questao));
            return Number.isFinite(valor) ? valor : 0;
        },

        booleanoDe(questao) {
            const valor = this.valorDe(questao);
            return valor === true ? 'sim' : valor === false ? 'nao' : '';
        },

        definirBooleano(questao, opcao) {
            this.definir(questao, opcao === 'sim' ? true : opcao === 'nao' ? false : null);
        },

        itensDe(questao) {
            return questao.opcao?.itens ?? [];
        },

        selecionados(questao) {
            const valor = this.valorDe(questao);
            return Array.isArray(valor) ? valor : valor ? [valor] : [];
        },

        marcado(questao, valor) {
            return this.selecionados(questao).includes(valor);
        },

        alternarItem(questao, valor) {
            const atual = new Set(this.selecionados(questao));
            atual.has(valor) ? atual.delete(valor) : atual.add(valor);
            this.definir(questao, [...atual]);
        },

        /** O que está selecionado fora do vocabulário é o texto livre de "outros". */
        outrosDe(questao) {
            const conhecidos = new Set(this.itensDe(questao).map((item) => item.valor));
            return this.selecionados(questao).filter((valor) => !conhecidos.has(valor)).join('; ');
        },

        definirOutros(questao, texto) {
            const conhecidos = new Set(this.itensDe(questao).map((item) => item.valor));
            const doVocabulario = this.selecionados(questao).filter((valor) => conhecidos.has(valor));
            this.definir(questao, texto ? [...doVocabulario, texto] : doVocabulario);
        },

        colunasDe(questao) {
            return questao.config?.columns ?? [];
        },

        linhasDe(questao) {
            const valor = this.valorDe(questao);
            return Array.isArray(valor) ? valor : [];
        },

        rotuloAdicionarLinha(questao) {
            return questao.config?.buttonLabel || this.translateMessage('adicionar_linha');
        },

        podeAdicionarLinha(questao) {
            return (questao.config?.actions ?? []).includes('create');
        },

        podeRemoverLinha(questao) {
            return (questao.config?.actions ?? []).includes('delete');
        },

        adicionarLinha(questao) {
            const linha = {};
            for (const coluna of this.colunasDe(questao)) {
                linha[coluna.key] = null;
            }
            this.definir(questao, [...this.linhasDe(questao), linha]);
        },

        removerLinha(questao, indice) {
            this.definir(questao, this.linhasDe(questao).filter((_, i) => i !== indice));
        },

        definirCelula(questao, indice, chave, valor) {
            const linhas = this.linhasDe(questao).map(
                (linha, i) => (i === indice ? { ...linha, [chave]: valor === '' ? null : valor } : linha),
            );
            this.definir(questao, linhas);
        },

        celulaDe(questao, indice, chave) {
            const valor = this.linhasDe(questao)[indice]?.[chave];
            return valor === null || valor === undefined ? '' : valor;
        },

        dataPublicacaoFormatada() {
            if (!this.dataPublicacao) {
                return this.translateMessage('data_na_publicacao');
            }

            const data = new Date(this.dataPublicacao);
            return Number.isNaN(data.getTime())
                ? this.dataPublicacao
                : data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        },

        salvar() {
            this.$emit('salvar', this.valores);
        },
    },
});
