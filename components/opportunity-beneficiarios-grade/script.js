/** Grade dos beneficiários do resultado: a tabela da aplicação lista as contempladas e cada célula de execução é editável. */
/** Largura de cada coluna, pelo tamanho do que ela carrega: data cabe em pouco, vocabulário não. */
const LARGURA_DA_COLUNA = {
    number: '160px',
    identificador_edital: '220px',
    cpf_responsavel_execucao: '150px',
    cnpj_responsavel_execucao: '170px',
    valor_recebido_pnab: '150px',
    data_recebimento_recurso: '120px',
    data_inicio_execucao: '120px',
    data_termino_execucao: '120px',
    recebeu_recursos_outras_fontes: '280px',
    valor_recursos_outras_fontes: '150px',
    qtd_pessoas_remuneradas_exec: '110px',
    cpfs_remunerados: '200px',
    formato_execucao: '190px',
    cep_local_execucao: '120px',
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
            hasError: false,
            colunas: [],
            faseFinal: null,
            query: {},
            selectedProponentTypes: [],
            selectedRanges: [],
            celulasPorId: {},
            pendenciasPorId: {},
            editando: null,
            selecaoAtiva: [],
            editadas: {},
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

        /** CPF e CNPJ ocupam a mesma coluna na tela: nunca vêm preenchidos juntos. */
        colunasVisiveis() {
            const ocultas = ['identificador_edital', 'cnpj_responsavel_execucao'];

            return this.colunas.filter((coluna) => !ocultas.includes(coluna.chave));
        },

        headers() {
            const inscricao = { slug: 'number', text: this.translateMessage('inscricao'), value: 'number', visible: true, sticky: true, width: LARGURA_DA_COLUNA.number };
            const demais = this.colunasVisiveis.map((coluna) => ({
                slug: coluna.chave,
                text: this.rotuloDaColuna(coluna),
                value: coluna.chave,
                visible: true,
                width: LARGURA_DA_COLUNA[coluna.chave],
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
            this.hasError = false;

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
                this.hasError = true;
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

        /** Tela cheia para a grade: com doze colunas, a largura do container não basta. */
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

        rotuloDaColuna(coluna) {
            return coluna.chave === 'cpf_responsavel_execucao' ? this.translateMessage('documento') : coluna.label;
        },

        celulasDe(entity) {
            return this.celulasPorId[entity.id] ?? {};
        },

        pendenciasDe(entity) {
            return this.pendenciasPorId[entity.id] ?? [];
        },

        editandoCelula(entity, coluna) {
            return this.editando?.id === entity.id && this.editando?.chave === coluna.chave;
        },

        /**
         * A célula vive como texto e só vira campo ao ser usada: com dez colunas editáveis e rolagem
         * infinita, manter um input por célula enche a página de controles que ninguém está usando.
         */
        abrirEdicao(entity, coluna, evento) {
            if (this.editandoCelula(entity, coluna)) {
                return;
            }

            this.editando = { id: entity.id, chave: coluna.chave };

            // O multiselect edita o próprio array que recebe, então ele precisa ser estável entre renders.
            if (coluna.opcoes && coluna.tipo !== 'selecao') {
                this.selecaoAtiva = this.selecionadas(entity, coluna);
            }

            const celula = evento.currentTarget.closest('td');
            this.$nextTick(() => {
                // O mc-select abre no clique do próprio gatilho: sem isto, editar a célula custaria dois cliques.
                if (coluna.tipo === 'selecao') {
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
            if (coluna.tipo === 'data') {
                this.definirData(entity, coluna, evento.target.value);
            } else if (coluna.tipo !== 'moeda' && coluna.tipo !== 'selecao' && !coluna.opcoes) {
                this.definirCelula(entity, coluna, evento.target.value);
            }

            clearTimeout(this.timeouts[entity.id]);
            this.salvarLinha(entity.id);
            this.encerrarEdicao();
        },

        sairDaCelula(entity, coluna, evento) {
            if (coluna.tipo === 'data') {
                this.apagarData(entity, coluna, evento.target.value);
            }

            this.encerrarEdicao();
        },

        /** CPF e CNPJ dividem uma coluna só, e a cobrança do servidor vem no nome da dupla. */
        celulaPendente(entity, coluna) {
            const chave = coluna.chave === 'cpf_responsavel_execucao' ? 'responsavel_execucao' : coluna.chave;

            return this.pendenciasDe(entity).includes(chave);
        },

        valorAutomatico(entity, coluna) {
            if (coluna.chave !== 'cpf_responsavel_execucao') {
                return this.valorExibido(entity, coluna);
            }

            const celulas = this.celulasDe(entity);
            const documento = celulas.cpf_responsavel_execucao?.valor ?? celulas.cnpj_responsavel_execucao?.valor ?? '';

            return this.mascarar('cpf_responsavel_execucao', documento);
        },

        situacaoDe(entity) {
            const quantas = this.pendenciasDe(entity).length;

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

            if (this.salvoEm[entity.id]) {
                return this.translateMessage('salvo');
            }

            return this.pendenciasDe(entity).length ? this.situacaoDe(entity) : '';
        },

        valorExibido(entity, coluna) {
            const valor = this.celulasDe(entity)[coluna.chave]?.valor;

            if (valor === null || valor === undefined) {
                return '';
            }

            if (coluna.tipo === 'moeda') {
                return this.formatarMoeda(valor);
            }

            const comMascara = (item) => this.mascarar(coluna.chave, item);

            return Array.isArray(valor) ? valor.map(comMascara).join(', ') : comMascara(valor);
        },

        /** O campo monetário da aplicação usa esta mesma moeda e locale; a leitura precisa casar com ele. */
        formatarMoeda(valor) {
            const numero = Number(String(valor).replace(',', '.'));

            if (!isFinite(numero)) {
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

        /** O multiselect indexa pelo rótulo, e vírgula no valor quebraria a lista do contrato. */
        itensDaColuna(coluna) {
            return Object.fromEntries((coluna.opcoes ?? []).map((opcao) => [opcao.replace(/,/g, '\\,'), opcao]));
        },

        selecionadas(entity, coluna) {
            const valor = this.valorCru(entity, coluna);

            return (Array.isArray(valor) ? valor : [valor]).filter((item) => item !== '');
        },

        /** A opção exclusiva da coluna (negar outras fontes) não convive com as demais nem com um valor. */
        definirSelecao(entity, coluna) {
            const exclusiva = coluna.opcaoExclusiva;
            const negou = exclusiva !== null && this.selecaoAtiva.includes(exclusiva);

            if (negou && this.selecaoAtiva.length > 1) {
                this.selecaoAtiva.splice(0, this.selecaoAtiva.length, exclusiva);
            }

            this.definirCelula(entity, coluna, this.selecaoAtiva.join(', '));

            if (negou) {
                this.zerarValorDeOutrasFontes(entity);
            }
        },

        zerarValorDeOutrasFontes(entity) {
            const valor = this.colunas.find((coluna) => coluna.chave === 'valor_recursos_outras_fontes');

            if (valor) {
                this.definirCelula(entity, valor, '');
            }
        },

        /** O que vai para dentro do campo é o valor sem máscara; a formatação é da célula em repouso. */
        valorCru(entity, coluna) {
            const valor = this.celulasDe(entity)[coluna.chave]?.valor;

            return valor === null || valor === undefined ? '' : valor;
        },

        /** Máscara é de leitura: o que o validador do CultBr recebe continua só com os dígitos. */
        mascarar(chave, valor) {
            const digitos = String(valor ?? '').replace(/\D/g, '');

            if (chave === 'cep_local_execucao' && digitos.length === 8) {
                return digitos.replace(/(\d{5})(\d{3})/, '$1-$2');
            }

            if (chave !== 'cpf_responsavel_execucao' && chave !== 'cpfs_remunerados') {
                return valor;
            }

            if (digitos.length === 11) {
                return digitos.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            }

            return digitos.length === 14
                ? digitos.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
                : valor;
        },

        /** O contrato pede o valor como string de duas casas ("15000.00"), e o campo monetário entrega número. */
        paraOContrato(coluna, valor) {
            if (coluna.tipo !== 'moeda' || valor === '' || valor === null || valor === undefined) {
                return valor;
            }

            const numero = Number(valor);

            return isFinite(numero) ? numero.toFixed(2) : valor;
        },

        /** O CultBr recebe o CPF só com dígitos e o CEP pontuado, como no payload de referência. */
        semMascara(chave, valor) {
            return chave === 'cpfs_remunerados' ? String(valor).replace(/\D/g, '') : valor;
        },

        /** O contrato do CultBr pede dd/mm/aaaa; o campo de data da aplicação fala ISO. */
        dataParaCampo(entity, coluna) {
            const partes = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(this.valorExibido(entity, coluna));

            return partes ? `${partes[3]}-${partes[2]}-${partes[1]}` : '';
        },

        // O campo de data dispara change a cada segmento digitado, e o valor ainda incompleto chega vazio.
        definirData(entity, coluna, valorIso) {
            const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(valorIso);

            if (partes) {
                this.definirCelula(entity, coluna, `${partes[3]}/${partes[2]}/${partes[1]}`);
            }
        },

        /** Só ao sair do campo o vazio quer dizer apagar, em vez de digitação pela metade. */
        apagarData(entity, coluna, valorIso) {
            if (valorIso === '' && this.valorExibido(entity, coluna) !== '') {
                this.definirCelula(entity, coluna, '');
            }
        },

        definirCelula(entity, coluna, valorBruto) {
            const valor = coluna.tipo === 'lista'
                ? valorBruto.split(',').map((parte) => this.semMascara(coluna.chave, parte.trim())).filter((parte) => parte !== '')
                : this.paraOContrato(coluna, this.semMascara(coluna.chave, valorBruto));

            const id = entity.id;
            this.celulasPorId[id] = { ...this.celulasDe(entity), [coluna.chave]: { valor: valor === '' ? null : valor, fonte: 'gestor' } };
            this.editadas[id] = { ...(this.editadas[id] ?? {}), [coluna.chave]: valor };

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
                    this.messages.error(body?.data?.message ?? body?.data ?? this.translateMessage('erro_salvar'));
                    // A célula foi pintada antes da resposta; recusada, a tela não pode seguir mostrando o que não gravou.
                    await this.carregarCelulas([registrationId]);
                    return;
                }

                this.aplicarLinha(registrationId, body.linha);
                this.salvoEm[registrationId] = new Date();
            } catch (error) {
                this.messages.error(this.translateMessage('erro_salvar'));
            } finally {
                delete this.salvando[registrationId];

                if (this.editadas[registrationId]) {
                    this.salvarLinha(registrationId);
                }
            }
        },

        /** Com edição mais nova em espera, o eco do servidor descartaria o que o gestor acabou de digitar. */
        aplicarLinha(registrationId, linha) {
            if (!linha || this.editadas[registrationId]) {
                return;
            }

            this.celulasPorId[registrationId] = linha.celulas ?? {};
            this.pendenciasPorId[registrationId] = linha.pendencias ?? [];
        },
    },
});
