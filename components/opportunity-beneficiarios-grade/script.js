/** Grade dos beneficiários do resultado: a tabela da aplicação lista as contempladas e cada célula de execução é editável. */
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
            const inscricao = { slug: 'number', text: this.translateMessage('inscricao'), value: 'number', visible: true, sticky: true };
            const demais = this.colunasVisiveis.map((coluna) => ({
                slug: coluna.chave,
                text: this.rotuloDaColuna(coluna),
                value: coluna.chave,
                visible: true,
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

            const comMascara = (item) => this.mascarar(coluna.chave, item);

            return Array.isArray(valor) ? valor.map(comMascara).join(', ') : comMascara(valor);
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

        /** O gestor digita com ou sem pontuação; o que fica gravado é sempre só o dígito. */
        semMascara(chave, valor) {
            const soDigitos = ['cpfs_remunerados', 'cep_local_execucao'].includes(chave);

            return soDigitos ? String(valor).replace(/\D/g, '') : valor;
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
                : this.semMascara(coluna.chave, valorBruto);

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
