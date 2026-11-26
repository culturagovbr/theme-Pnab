/**
 * Formulário dinâmico do resultado CultBR: renderiza qualquer estrutura publicada pelo PAR
 * (seções > etapas > questões), campo a campo por tipo de questão. Quem persiste é o card,
 * que recebe os valores pelo evento `salvar`.
 */

const FORMULARIO_TIPOS_DE_ANEXO = ['anexo', 'múltiplos anexos'];
const FORMULARIO_TIPOS_DE_ESCOLHA = ['radio', 'checkbox', 'múltipla escolha'];
const FORMULARIO_TIPOS_DE_TABELA = ['tabela', 'Tabela Dinâmica'];
const FORMULARIO_TIPOS_SIMPLES = ['texto', 'textarea', 'número', 'moeda', 'data', 'booleano'];

/** Máscaras que o PAR declara, no formato do v-maska; o CNPJ aceita letras. */
const FORMULARIO_MASCARAS = {
    cep: '#####-###',
    cpf: '###.###.###-##',
    cnpj: '**.***.***/****-##',
    documento: JSON.stringify(['###.###.###-##', '**.***.***/****-##']),
};

globalThis.resultadoMascara = (nome) => FORMULARIO_MASCARAS[nome] ?? null;

const formularioJson = (valor) => JSON.stringify(valor ?? null);
const formularioJsonPorQuestao = (respostas) => Object.fromEntries(Object.entries(respostas ?? {}).map(([id, valor]) => [id, formularioJson(valor)]));

app.component('opportunity-resultado-formulario', {
    template: $TEMPLATES['opportunity-resultado-formulario'],
    emits: ['alterado', 'salvar', 'anexar', 'retirar'],

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
        // As gravadas no servidor, a cada estado recebido: o que mudou em outra guia entra na tela.
        respostasGravadas: {
            type: Object,
            default: null,
        },
        respostasAutomaticas: {
            type: Object,
            default: () => ({}),
        },
        questoesSemFonte: {
            type: Array,
            default: () => [],
        },
        // Arquivos de cada questão de documento, por id_questao.
        anexos: {
            type: Object,
            default: () => ({}),
        },
        // Questão com arquivo em trânsito.
        anexando: {
            type: Number,
            default: null,
        },
        // Modelo de planilha do edital no CultBR; nulo antes de o edital chegar lá.
        planilha: {
            type: Object,
            default: null,
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
        // Status do envio, exibido como chip no canto do primeiro card.
        estado: {
            type: String,
            default: null,
        },
        estadoLabel: {
            type: String,
            default: '',
        },
    },

    data() {
        const valores = this.clonarRespostas();

        return {
            // Cópia editável: o que o gestor digita fica na tela mesmo quando o servidor recusa.
            valores,
            // json por questão: o que esta tela já gravou e o que o servidor tem
            gravados: formularioJsonPorQuestao(valores),
            doServidor: formularioJsonPorQuestao(valores),
            // o campo de moeda não relê o valor que muda por fora
            versoes: {},
            autosaveTimeout: null,
        };
    },

    watch: {
        // O card só troca as respostas na abertura e quando a versão do formulário muda.
        respostas() {
            clearTimeout(this.autosaveTimeout);
            this.valores = this.clonarRespostas();
            this.gravados = formularioJsonPorQuestao(this.valores);
            this.doServidor = { ...this.gravados };
        },

        respostasGravadas(respostas) {
            if (respostas) {
                this.adotar(respostas);
            }
        },

        // A recusada segue como não gravada e vai de novo na próxima gravação.
        erros(erros) {
            for (const id of Object.keys(erros ?? {})) {
                this.gravados[id] = this.doServidor[id] ?? formularioJson(null);
            }
        },

        // Autosave: como as fases, sem botão.
        valores: {
            deep: true,
            handler() {
                clearTimeout(this.autosaveTimeout);

                if (!this.pendentes.length) {
                    this.$emit('alterado', false);
                    return;
                }

                this.$emit('alterado', true);
                this.autosaveTimeout = setTimeout(() => this.salvar(), 1500);
            },
        },
    },

    mounted() {
        window.addEventListener('beforeunload', this.gravarAntesDeSair);
    },

    // trocar de aba principal desmonta o formulário
    beforeUnmount() {
        window.removeEventListener('beforeunload', this.gravarAntesDeSair);
        this.salvar();
    },

    computed: {
        /** Questões com resposta na tela que esta tela ainda não gravou. */
        pendentes() {
            return Object.keys({ ...this.gravados, ...this.valores })
                .filter((id) => formularioJson(this.valores[id]) !== (this.gravados[id] ?? formularioJson(null)));
        },

        etapas() {
            return (this.estrutura.secoes ?? []).flatMap((secao) => secao.etapas ?? []);
        },

        /** Cada etapa é um card (nasce fechado); a tabela de categorias fica dentro do card da própria etapa. */
        cards() {
            return this.etapas
                .map((etapa) => ({ chave: 'etapa-' + etapa.id_etapa, titulo: etapa.nome, blocos: this.blocosDe(etapa) }))
                .filter((card) => card.blocos.length);
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
                (questao) => !this.isSemFonte(questao) && this.isVisivel(questao),
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

        /** Todas as regras precisam casar; a regra que o servidor não conhece não esconde nada. */
        isVisivel(questao) {
            return (questao.config?.regras?.itens ?? []).every((regra) => !this.isRegraSuportada(regra) || this.casa(regra));
        },

        isRegraSuportada(regra) {
            return regra.tipo === 'visibilidade'
                && JSON.stringify(regra.efeito) === JSON.stringify({ mostrar: true })
                && ['eq', 'includes'].includes(regra.quando?.operador)
                && this.fonteDe(regra) !== undefined;
        },

        fonteDe(regra) {
            return this.etapas
                .flatMap((etapa) => etapa.questoes ?? [])
                .find((questao) => questao.id_questao_etapa === regra.quando.fonteIdQuestaoEtapa);
        },

        casa(regra) {
            const fonte = this.fonteDe(regra);
            const resposta = this.valorAutomatico(fonte) ?? this.valorDe(fonte) ?? null;
            const marcado = (escolha) => (escolha !== null && typeof escolha === 'object' ? escolha.id : escolha);

            if (regra.quando.operador === 'eq') {
                return marcado(resposta) === regra.quando.valor;
            }

            return Array.isArray(resposta) && resposta.map(marcado).includes(regra.quando.valor);
        },

        tipoDoCampo(questao) {
            const tipo = questao.tipo_questao;

            if (this.isAutomatica(questao)) {
                return FORMULARIO_TIPOS_DE_TABELA.includes(tipo) ? 'tabela-automatica' : 'texto-automatico';
            }

            if (questao.somente_leitura) {
                return 'espelho';
            }

            if (FORMULARIO_TIPOS_DE_ANEXO.includes(tipo)) {
                return this.isPlanilha(questao) ? 'planilha' : 'documento';
            }

            if (FORMULARIO_TIPOS_DE_ESCOLHA.includes(tipo)) {
                return 'escolha';
            }

            if (FORMULARIO_TIPOS_DE_TABELA.includes(tipo)) {
                return 'tabela';
            }

            return FORMULARIO_TIPOS_SIMPLES.includes(tipo) ? tipo : 'desconhecido';
        },

        /** A planilha de contemplados sai da lista de beneficiários, nunca de um arquivo. */
        isPlanilha(questao) {
            return (questao.tag ?? '').includes('planilha')
                && (questao.nome ?? '').toLowerCase().startsWith('planilha de resultados');
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

            return ['textarea', 'escolha', 'tabela', 'tabela-automatica', 'documento', 'planilha', 'desconhecido'].includes(tipo);
        },

        automaticoFormatado(questao) {
            const valor = this.valorAutomatico(questao);

            if (questao.tipo_questao === 'moeda') {
                return this.formatoMoeda(valor);
            }

            if (questao.tipo_questao === 'número') {
                return this.formatoNumero(valor);
            }

            return String(valor);
        },

        /** Só exibição: o payload leva o número cru, não o texto pontuado. */
        formatoNumero(valor) {
            const numero = Number(valor);

            return Number.isFinite(numero) && valor !== null && valor !== ''
                ? numero.toLocaleString('pt-BR')
                : String(valor ?? '');
        },

        formatoMoeda(valor) {
            const numero = Number(valor);

            return Number.isFinite(numero)
                ? numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                : String(valor ?? '');
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
            const valor = this.valorDe(questao);
            return valor === null || valor === undefined || valor === '' ? null : Number(valor);
        },

        // o campo de moeda lê texto sem dígito como zero
        esvaziaSemDigito(event) {
            if (!/\d/.test(event.target.value)) {
                event.target.value = '';
            }
        },

        minimoDe(questao) {
            return this.maisRestrito(Math.max, questao.valor_minimo, questao.config?.numericoMinimo);
        },

        passoDe(questao) {
            return questao.config?.numericoSomenteInteiros ? 1 : 'any';
        },

        /** Em texto, o limite conta caracteres. */
        limiteDe(questao) {
            return this.maisRestrito(Math.min, questao.valor_maximo, questao.config?.limiteCaracteres);
        },

        maisRestrito(compara, ...limites) {
            const declarados = limites.filter((limite) => limite !== null && limite !== undefined);
            return declarados.length ? compara(...declarados.map(Number)) : undefined;
        },

        mascaraDe(questao) {
            return resultadoMascara(questao.mascara) ?? resultadoMascara(questao.config?.padraoValidacao);
        },

        avisoDaPlanilha() {
            return this.planilha?.possui === false && this.planilha.motivo
                ? this.translateMessage('sem_planilha', { motivo: this.planilha.motivo })
                : this.translateMessage('planilha');
        },

        /** Com modelo de planilha, nenhum documento recebe arquivo: o CultBR não recebe os dois juntos. */
        isDocumentoIndisponivel(questao) {
            return this.planilha?.possui === true && this.tipoDoCampo(questao) === 'documento';
        },

        /** O que a tela não deixa responder não aparece como obrigatório. */
        isObrigatoriaNaTela(questao) {
            return questao.requerida
                && !['planilha', 'espelho'].includes(this.tipoDoCampo(questao))
                && !this.isDocumentoIndisponivel(questao);
        },

        arquivosDe(questao) {
            return this.anexos?.[questao.id_questao] ?? [];
        },

        booleanoDe(questao) {
            const valor = this.valorDe(questao);
            return valor === true ? 'sim' : valor === false ? 'nao' : '';
        },

        definirBooleano(questao, opcao) {
            this.definir(questao, opcao === 'sim' ? true : opcao === 'nao' ? false : null);
        },

        linhasDe(questao) {
            const valor = this.valorDe(questao);
            return Array.isArray(valor) ? valor : [];
        },

        /** Traz o que mudou no servidor por outra guia, menos o que está em edição ou a caminho daqui. */
        adotar(respostas) {
            const novas = formularioJsonPorQuestao(respostas);
            const pendentes = this.pendentes;

            for (const id of Object.keys({ ...this.doServidor, ...novas })) {
                const nova = novas[id] ?? formularioJson(null);
                const anterior = this.doServidor[id] ?? formularioJson(null);
                const aCaminho = (this.gravados[id] ?? formularioJson(null)) !== anterior;

                if (nova === anterior || aCaminho || pendentes.includes(id)) {
                    continue;
                }

                this.gravados[id] = nova;

                if (nova !== formularioJson(this.valores[id])) {
                    this.valores[id] = JSON.parse(nova);
                    this.versoes[id] = (this.versoes[id] ?? 0) + 1;
                }
            }

            this.doServidor = novas;
        },

        gravarAntesDeSair(event) {
            if (this.pendentes.length || this.salvando) {
                this.salvar();
                event.preventDefault();
            }
        },

        /** Só o que mudou: o formulário inteiro passaria por cima da edição de outra guia. */
        salvar() {
            clearTimeout(this.autosaveTimeout);
            const ids = this.pendentes;

            if (!ids.length) {
                return;
            }

            const alteradas = JSON.parse(JSON.stringify(Object.fromEntries(ids.map((id) => [id, this.valores[id] ?? null]))));

            for (const id of ids) {
                this.gravados[id] = formularioJson(alteradas[id]);
            }

            this.$emit('alterado', false);
            this.$emit('salvar', alteradas);
        },
    },
});
