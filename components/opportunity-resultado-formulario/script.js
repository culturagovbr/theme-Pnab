/**
 * Formulário dinâmico do resultado CultBR: renderiza qualquer estrutura publicada pelo PAR
 * (seções > etapas > questões), campo a campo por tipo de questão. Quem persiste é o card,
 * que recebe os valores pelo evento `salvar`.
 */

/** Questões sem campo nesta fase da integração. */
const FORMULARIO_TIPOS_OCULTOS = ['anexo', 'visualização simplificada edital'];

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
        estrutura: {
            type: Object,
            required: true,
        },
        respostas: {
            type: Object,
            default: () => ({}),
        },
        respostasAutomaticas: {
            type: Object,
            default: () => ({}),
        },
        questoesSemFonte: {
            type: Array,
            default: () => [],
        },
        erros: {
            type: Object,
            default: () => ({}),
        },
        salvando: {
            type: Boolean,
            default: false,
        },
        salvoEm: {
            type: Date,
            default: null,
        },
    },

    data() {
        return {
            // Cópia editável; o que está salvo continua sendo do card.
            valores: this.clonarRespostas(),
            autosaveTimeout: null,
        };
    },

    watch: {
        respostas() {
            this.valores = this.clonarRespostas();
        },

        // Autosave: como as fases, sem botão. O pós-save devolve o salvo igual ao local e não re-dispara.
        valores: {
            deep: true,
            handler() {
                if (JSON.stringify(this.valores) === JSON.stringify(this.clonarRespostas())) {
                    return;
                }

                clearTimeout(this.autosaveTimeout);
                this.autosaveTimeout = setTimeout(() => this.salvar(), 1500);
            },
        },
    },

    unmounted() {
        clearTimeout(this.autosaveTimeout);
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
                    && !this.isDataPublicacao(questao)
                    && !this.isSemFonte(questao)
                    && this.dependenciaSatisfeita(questao),
            );
        },

        /** Automáticos consecutivos empilham numa coluna só; o campo seguinte fica ao lado. */
        blocosDe(etapa) {
            const blocos = [];

            for (const questao of this.questoesVisiveis(etapa)) {
                const ultimo = blocos[blocos.length - 1];

                if (this.tipoDoCampo(questao) === 'texto-automatico') {
                    if (ultimo?.automaticos) {
                        ultimo.questoes.push(questao);
                    } else {
                        blocos.push({ automaticos: true, questoes: [questao] });
                    }
                } else {
                    blocos.push({ automaticos: false, questao });
                }
            }

            return blocos;
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
            if (this.isAutomatica(questao)) {
                return questao.tipo_questao === 'Tabela Dinâmica' ? 'tabela-automatica' : 'texto-automatico';
            }

            return questao.tipo_questao;
        },

        isAutomatica(questao) {
            return this.valorAutomatico(questao) !== undefined;
        },

        isSemFonte(questao) {
            return this.questoesSemFonte.some((id) => Number(id) === Number(questao.id_questao));
        },

        valorAutomatico(questao) {
            return this.respostasAutomaticas?.[questao.id_questao]
                ?? this.respostasAutomaticas?.[String(questao.id_questao)];
        },

        /** Campos curtos dividem a linha; os longos (tabelas, textos, opções) ocupam inteira. */
        campoLargo(questao) {
            const tipo = this.tipoDoCampo(questao);

            return ['textarea', 'múltipla escolha', 'Tabela Dinâmica', 'tabela-automatica',
                'visualização simplificada edital'].includes(tipo);
        },

        automaticoFormatado(questao) {
            const valor = this.valorAutomatico(questao);

            if (questao.tipo_questao === 'moeda') {
                return this.formatoMoeda(valor);
            }

            return String(valor);
        },

        formatoMoeda(valor) {
            const numero = Number(valor);

            return Number.isFinite(numero)
                ? numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                : String(valor ?? '');
        },

        celulaAutomaticaFormatada(questao, linha, coluna) {
            const valor = linha[coluna.key];

            if (valor === null || valor === undefined) {
                return '—';
            }

            return coluna.type === 'currency' ? this.formatoMoeda(valor) : String(valor);
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

        salvar() {
            this.$emit('salvar', this.valores);
        },
    },
});
