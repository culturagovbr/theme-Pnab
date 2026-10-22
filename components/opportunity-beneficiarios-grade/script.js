/** Grade dos beneficiários do resultado: uma linha por contemplada, com as células de execução editáveis. */
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
            linhas: [],
            total: 0,
            skip: 0,
            limit: 50,
            editadas: {},
            salvando: {},
            salvoEm: {},
            timeouts: {},
            telaCheia: false,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'beneficiarios-grade', { cacheMode: 'no-store' });
        this.carregar();
    },

    unmounted() {
        Object.values(this.timeouts).forEach((timeout) => clearTimeout(timeout));
        this.encerrarTelaCheia();
    },

    computed: {
        temProxima() {
            return this.skip + this.limit < this.total;
        },

        /** O identificador do edital é o mesmo em toda linha: vira contexto do card, não coluna. */
        identificadorDoEdital() {
            return this.linhas[0]?.celulas?.identificador_edital?.valor ?? '';
        },

        /** CPF e CNPJ ocupam a mesma coluna na tela: nunca vêm preenchidos juntos. */
        colunasVisiveis() {
            const ocultas = ['identificador_edital', 'cnpj_responsavel_execucao'];

            return this.colunas.filter((coluna) => !ocultas.includes(coluna.chave));
        },

        contempladas() {
            return this.translateMessage('contagem').replace(':total', this.numero(this.total));
        },

        rotuloTelaCheia() {
            return this.telaCheia ? this.translateMessage('sair_tela_cheia') : this.translateMessage('tela_cheia');
        },

        intervalo() {
            const de = this.total === 0 ? 0 : this.skip + 1;
            const ate = Math.min(this.skip + this.limit, this.total);

            return this.translateMessage('intervalo')
                .replace(':de', this.numero(de))
                .replace(':ate', this.numero(ate))
                .replace(':total', this.numero(this.total));
        },
    },

    methods: {
        numero(valor) {
            return valor.toLocaleString('pt-BR');
        },

        classeDaColuna(coluna) {
            return [`is-${coluna.tipo}`, { 'is-obrigatoria': coluna.obrigatoria }];
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

        async carregar() {
            this.isLoading = true;
            this.hasError = false;

            try {
                const url = this.api.createUrl('resultadoBeneficiarios', {
                    opportunityId: this.entity.id,
                    skip: this.skip,
                    limit: this.limit,
                });
                const response = await this.api.GET(url);
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(body?.data ?? this.translateMessage('erro_carregar'));
                }

                this.colunas = body.colunas ?? [];
                this.linhas = body.data ?? [];
                this.total = body.total ?? 0;
                this.limit = body.limit ?? this.limit;
            } catch (error) {
                this.hasError = true;
            } finally {
                this.isLoading = false;
            }
        },

        async paginar(novoSkip) {
            if (novoSkip < 0 || novoSkip >= Math.max(this.total, 1)) {
                return;
            }

            this.skip = novoSkip;
            await this.carregar();
        },

        rotuloDaColuna(coluna) {
            return coluna.chave === 'cpf_responsavel_execucao' ? this.translateMessage('documento') : coluna.label;
        },

        valorAutomatico(linha, coluna) {
            if (coluna.chave !== 'cpf_responsavel_execucao') {
                return this.valorExibido(linha, coluna);
            }

            return linha.celulas.cpf_responsavel_execucao?.valor ?? linha.celulas.cnpj_responsavel_execucao?.valor ?? '';
        },

        situacaoDaLinha(linha) {
            const quantas = linha.pendencias.length;

            if (quantas === 0) {
                return this.translateMessage('completa');
            }

            return quantas === 1
                ? this.translateMessage('uma_pendencia')
                : this.translateMessage('varias_pendencias').replace(':quantas', quantas);
        },

        /** A linha mostra o que está acontecendo nela agora; o que falta só aparece quando nada está. */
        estadoDaLinha(linha) {
            if (this.salvando[linha.registrationId]) {
                return this.translateMessage('salvando');
            }

            if (this.salvoEm[linha.registrationId]) {
                return this.translateMessage('salvo');
            }

            return linha.pendencias.length ? this.situacaoDaLinha(linha) : '';
        },

        valorExibido(linha, coluna) {
            const valor = linha.celulas[coluna.chave]?.valor;

            if (valor === null || valor === undefined) {
                return '';
            }

            return Array.isArray(valor) ? valor.join(', ') : valor;
        },

        /** O contrato do CultBr pede dd/mm/aaaa; o campo de data da aplicação fala ISO. */
        dataParaCampo(linha, coluna) {
            const valor = this.valorExibido(linha, coluna);
            const partes = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(valor);

            return partes ? `${partes[3]}-${partes[2]}-${partes[1]}` : '';
        },

        definirData(linha, coluna, valorIso) {
            const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(valorIso);

            this.definirCelula(linha, coluna, partes ? `${partes[3]}/${partes[2]}/${partes[1]}` : '');
        },

        definirCelula(linha, coluna, valorBruto) {
            const valor = coluna.tipo === 'lista'
                ? valorBruto.split(',').map((parte) => parte.trim()).filter((parte) => parte !== '')
                : valorBruto;

            linha.celulas[coluna.chave] = { valor: valor === '' ? null : valor, fonte: 'gestor' };

            const id = linha.registrationId;
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
        aplicarLinha(registrationId, linhaNova) {
            if (!linhaNova || this.editadas[registrationId]) {
                return;
            }

            const indice = this.linhas.findIndex((linha) => linha.registrationId === registrationId);
            if (indice > -1) {
                this.linhas[indice] = linhaNova;
            }
        },
    },
});
