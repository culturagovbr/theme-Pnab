/** Grade dos beneficiários do resultado: a tabela da aplicação lista as contempladas e cada célula é editável. */
/** Largura de cada coluna, pelo tamanho do que o tipo dela carrega. */
const LARGURA_DA_INSCRICAO = '160px';
const LARGURA_PADRAO = '200px';
const LARGURA_POR_TIPO = {
    cpf: '150px',
    cnpj: '170px',
    cep: '120px',
    monetario: '150px',
    lista: '240px',
};

app.component('opportunity-beneficiarios-grade', {
    template: $TEMPLATES['opportunity-beneficiarios-grade'],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-beneficiarios-grade');
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
            erro: null,
            colunas: [],
            faseFinal: null,
            query: {},
            selectedProponentTypes: [],
            selectedRanges: [],
            celulasPorId: {},
            pendenciasPorId: {},
            editando: null,
            editadas: {},
            naoGravadas: {},
            paraRegravar: {},
            salvando: {},
            salvoEm: {},
            timeouts: {},
            telaCheia: false,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'beneficiarios-grade', { cacheMode: 'no-store' });
        this.aguardando = [];
        this.carregarColunas();
    },

    unmounted() {
        Object.values(this.timeouts).forEach((timeout) => clearTimeout(timeout));
        this.encerrarTelaCheia();
    },

    computed: {
        proponentTypes() {
            return this.opcoesDe(this.entity.registrationProponentTypes, (tipo) => tipo);
        },

        ranges() {
            return this.opcoesDe(this.entity.registrationRanges, (faixa) => faixa.label);
        },

        select() {
            return 'id,number,proponentType,range,status,singleUrl';
        },

        headers() {
            const inscricao = { slug: 'number', text: this.translateMessage('inscricao'), value: 'number', visible: true, sticky: true, width: LARGURA_DA_INSCRICAO };
            const demais = this.colunas.map((coluna) => ({
                slug: coluna.coluna,
                text: coluna.obrigatoriedade === 'obrigatorio' ? `${coluna.nome} *` : coluna.nome,
                value: coluna.coluna,
                visible: true,
                width: LARGURA_POR_TIPO[coluna.tipo] ?? LARGURA_PADRAO,
            }));

            return [inscricao, ...demais];
        },

        visiveis() {
            return this.headers.map((header) => header.slug).join(',');
        },

        rotuloTelaCheia() {
            return this.telaCheia ? this.translateMessage('sair_tela_cheia') : this.translateMessage('tela_cheia');
        },
    },

    methods: {
        async carregarColunas() {
            this.isLoading = true;
            this.erro = null;

            try {
                const url = this.api.createUrl('resultadoBeneficiarios', { opportunityId: this.entity.id, registrationIds: '' });
                const response = await this.api.GET(url);
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(body?.data ?? this.translateMessage('erro_carregar'));
                }

                this.colunas = body.colunas ?? [];
                this.faseFinal = body.faseFinal ?? null;
                // GTE no lugar de EQ porque só os operadores de intervalo escapam da lista de filtros removíveis,
                // e a condição de contemplada é da tela, não um filtro que o gestor possa tirar. 10 é o maior status.
                this.query = { '@opportunity': this.faseFinal, status: 'GTE(10)' };
            } catch (error) {
                this.erro = error.message || this.translateMessage('erro_carregar');
            } finally {
                this.isLoading = false;
            }
        },

        /** A tabela devolve a inscrição; as células de execução vêm do plugin, numa chamada por página. */
        processarLinha(bruto) {
            const linha = Utils.entityRawProcessor(bruto);
            this.agendarCelulas(linha.id);

            return linha;
        },

        agendarCelulas(registrationId) {
            this.aguardando.push(registrationId);

            if (this.agendado) {
                return;
            }

            this.agendado = true;
            Promise.resolve().then(() => {
                this.agendado = false;
                this.carregarCelulas(this.aguardando.splice(0));
            });
        },

        async carregarCelulas(registrationIds) {
            if (!registrationIds.length) {
                return;
            }

            try {
                const url = this.api.createUrl('resultadoBeneficiarios', {
                    opportunityId: this.entity.id,
                    registrationIds: registrationIds.join(','),
                });
                const response = await this.api.GET(url);
                const body = await response.json().catch(() => ({}));

                (body.data ?? []).forEach((linha) => this.aplicarLinha(linha.registrationId, linha));
            } catch (error) {
                this.messages.error(this.translateMessage('erro_carregar'));
            }
        },

        /** Tela cheia para a grade: com tantas colunas, a largura do container não basta. */
        alternarTelaCheia() {
            this.telaCheia = !this.telaCheia;

            if (this.telaCheia) {
                document.body.style.overflow = 'hidden';
                document.addEventListener('keydown', this.sairComEsc);
                return;
            }

            this.encerrarTelaCheia();
        },

        encerrarTelaCheia() {
            this.telaCheia = false;
            document.body.style.overflow = '';
            document.removeEventListener('keydown', this.sairComEsc);
        },

        sairComEsc(evento) {
            if (evento.key === 'Escape') {
                this.encerrarTelaCheia();
            }
        },

        /** O multiselect indexa pelo próprio rótulo, e a vírgula no valor quebraria a lista do filtro. */
        opcoesDe(itens, rotulo) {
            if (!itens?.length) {
                return null;
            }

            return Object.fromEntries(itens.map((item) => {
                const texto = rotulo(item);
                return [String(texto).replace(/,/g, '\\,'), texto];
            }));
        },

        filtrarPorTipoDeProponente(entities) {
            this.aplicarFiltro('proponentType', this.selectedProponentTypes, entities);
        },

        filtrarPorFaixa(entities) {
            this.aplicarFiltro('range', this.selectedRanges, entities);
        },

        aplicarFiltro(campo, selecionados, entities) {
            if (selecionados.length) {
                this.query[campo] = `IN(${selecionados.toString()})`;
            } else {
                delete this.query[campo];
            }

            entities.refresh();
        },

        celulasDe(entity) {
            return this.celulasPorId[entity.id] ?? {};
        },

        /** O motivo de cada coluna que ainda impede o envio da linha. */
        pendenciasDe(entity) {
            return this.pendenciasPorId[entity.id] ?? {};
        },

        editandoCelula(entity, coluna) {
            return this.editando?.id === entity.id && this.editando?.coluna === coluna.coluna;
        },

        /**
         * A célula vive como texto e só vira campo ao ser usada: com tantas colunas editáveis e rolagem
         * infinita, manter um input por célula enche a página de controles que ninguém está usando.
         */
        abrirEdicao(entity, coluna, evento) {
            if (this.editandoCelula(entity, coluna)) {
                return;
            }

            this.editando = { id: entity.id, coluna: coluna.coluna };

            const celula = evento.currentTarget.closest('td');
            this.$nextTick(() => {
                // O mc-select abre no clique do próprio gatilho: sem isto, editar a célula custaria dois cliques.
                if (coluna.tipo === 'lista') {
                    celula?.querySelector('.mc-select__selected-option')?.click();
                    return;
                }

                celula?.querySelector('input')?.focus();
            });
        },

        encerrarEdicao() {
            this.editando = null;
        },

        /** Enter grava na hora: esperar o autosave de cada célula atrasa quem preenche a linha inteira. */
        confirmarCelula(entity, coluna, evento) {
            if (this.isTexto(coluna)) {
                this.definirCelula(entity, coluna, evento.target.value);
            }

            clearTimeout(this.timeouts[entity.id]);
            this.salvarLinha(entity.id);
            this.encerrarEdicao();
        },

        isTexto(coluna) {
            return coluna.tipo !== 'lista' && coluna.tipo !== 'monetario';
        },

        pendenciaDe(entity, coluna) {
            return this.pendenciasDe(entity)[coluna.coluna] ?? null;
        },

        naoGravadaDe(entity, coluna) {
            return this.naoGravadas[entity.id]?.[coluna.coluna] ?? null;
        },

        dicaDaCelula(entity, coluna) {
            return this.pendenciaDe(entity, coluna) ?? coluna.condicao ?? this.translateMessage('editar');
        },

        isCompleta(entity) {
            return Object.keys(this.pendenciasDe(entity)).length === 0;
        },

        situacaoDe(entity) {
            const quantas = Object.keys(this.pendenciasDe(entity)).length;

            if (quantas === 0) {
                return this.translateMessage('completa');
            }

            return quantas === 1
                ? this.translateMessage('uma_pendencia')
                : this.translateMessage('varias_pendencias').replace(':quantas', quantas);
        },

        /** A linha mostra o que está acontecendo nela agora; o que falta só aparece quando nada está. */
        estadoDe(entity) {
            if (this.salvando[entity.id]) {
                return this.translateMessage('salvando');
            }

            if (this.naoGravadas[entity.id]) {
                return this.translateMessage('nao_gravado');
            }

            if (this.salvoEm[entity.id]) {
                return this.translateMessage('salvo');
            }

            return this.isCompleta(entity) ? '' : this.situacaoDe(entity);
        },

        valorExibido(entity, coluna) {
            const valor = this.celulasDe(entity)[coluna.coluna]?.valor ?? '';

            if (typeof valor === 'object') {
                return Object.values(valor).filter((item) => item !== null && typeof item !== 'object').join(', ');
            }

            return coluna.tipo === 'monetario' ? this.formatarMoeda(valor) : this.mascarar(coluna.tipo, valor);
        },

        /** O campo monetário da aplicação usa esta mesma moeda e locale; a leitura precisa casar com ele. */
        formatarMoeda(valor) {
            const numero = this.numero(valor);

            if (numero === null) {
                return valor;
            }

            return new Intl.NumberFormat($MAPAS.config.locale, {
                style: 'currency',
                currency: $MAPAS.config.currency,
            }).format(numero);
        },

        /** O mc-select devolve {text, value}; vazio limpa a célula. */
        definirSelecaoUnica(entity, coluna, opcao) {
            this.definirCelula(entity, coluna, opcao?.value ?? '');
            clearTimeout(this.timeouts[entity.id]);
            this.salvarLinha(entity.id);
            this.encerrarEdicao();
        },

        /** O que vai para dentro da lista é o valor como está; um que não é opção deixa a lista sem escolha. */
        valorCru(entity, coluna) {
            const valor = this.celulasDe(entity)[coluna.coluna]?.valor ?? '';

            return typeof valor === 'object' ? '' : valor;
        },

        numero(valor) {
            return valor === '' || valor === null || !isFinite(Number(valor)) ? null : Number(valor);
        },

        /** Máscara é de leitura; a letra do CNPJ alfanumérico faz parte do documento. */
        mascarar(tipo, valor) {
            const limpo = String(valor).replace(/[.\-/\s]/g, '');

            if (tipo === 'cpf' && /^\d{11}$/.test(limpo)) {
                return limpo.replace(/(.{3})(.{3})(.{3})(.{2})/, '$1.$2.$3-$4');
            }

            if (tipo === 'cnpj' && /^[A-Z0-9]{12}\d{2}$/.test(limpo)) {
                return limpo.replace(/(.{2})(.{3})(.{3})(.{4})(.{2})/, '$1.$2.$3/$4-$5');
            }

            return tipo === 'cep' && /^\d{8}$/.test(limpo) ? limpo.replace(/(.{5})(.{3})/, '$1-$2') : valor;
        },

        definirCelula(entity, coluna, valorBruto) {
            // O campo monetário entrega número, e o contrato pede "15000.00".
            const digitado = coluna.tipo === 'monetario' ? this.numero(valorBruto)?.toFixed(2) ?? '' : valorBruto;
            // Célula esvaziada vai como nula: volta ao valor da ficha.
            const valor = digitado === '' ? null : digitado;

            const id = entity.id;
            this.celulasPorId[id] = { ...this.celulasDe(entity), [coluna.coluna]: { valor, fonte: 'gestor' } };
            this.editadas[id] = { ...(this.paraRegravar[id] ?? {}), ...(this.editadas[id] ?? {}), [coluna.coluna]: valor };
            delete this.paraRegravar[id];

            clearTimeout(this.timeouts[id]);
            this.timeouts[id] = setTimeout(() => this.salvarLinha(id), 1200);
        },

        async salvarLinha(registrationId) {
            const celulas = this.editadas[registrationId];
            if (!celulas || this.salvando[registrationId]) {
                return;
            }

            this.salvando[registrationId] = true;
            delete this.editadas[registrationId];

            try {
                const url = this.api.createUrl('resultadoBeneficiarioLinha', {});
                const response = await this.api.POST(url, {
                    opportunityId: this.entity.id,
                    registrationId,
                    celulas,
                });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.marcarNaoGravadas(registrationId, celulas, body?.data?.message ?? body?.data);
                    return;
                }

                delete this.naoGravadas[registrationId];
                this.aplicarLinha(registrationId, body.linha);
                this.salvoEm[registrationId] = new Date();
            } catch (error) {
                this.marcarNaoGravadas(registrationId, celulas);
            } finally {
                delete this.salvando[registrationId];

                if (this.editadas[registrationId]) {
                    this.salvarLinha(registrationId);
                }
            }
        },

        /** O digitado fica na célula, marcado, e volta na próxima gravação da linha. */
        marcarNaoGravadas(registrationId, celulas, motivo = null) {
            const texto = typeof motivo === 'string' && motivo ? motivo : this.translateMessage('erro_salvar');
            const marcadas = Object.fromEntries(Object.keys(celulas).map((coluna) => [coluna, texto]));

            this.messages.error(texto);
            this.naoGravadas[registrationId] = { ...(this.naoGravadas[registrationId] ?? {}), ...marcadas };
            this.paraRegravar[registrationId] = { ...(this.paraRegravar[registrationId] ?? {}), ...celulas };
            delete this.salvoEm[registrationId];
        },

        /** Com edição mais nova em espera, o eco do servidor descartaria o que o gestor acabou de digitar. */
        aplicarLinha(registrationId, linha) {
            if (!linha || this.editadas[registrationId]) {
                return;
            }

            this.celulasPorId[registrationId] = linha.celulas ?? {};
            this.pendenciasPorId[registrationId] = linha.pendencias ?? {};
        },
    },
});
