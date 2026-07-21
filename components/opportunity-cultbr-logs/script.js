/**
 * Conteúdo da aba "Logs CultBr": consome GET aldirblanc/opportunityCultLogs
 * (endpoint restrito a admin, como o hook que injeta a aba em Pnab\Theme::_init).
 */

/** Envios por página — espelha o default de Controller::GET_opportunityCultLogs. */
const CULTBR_LOGS_PAGE_SIZE = 20;

/** Rótulo de cada status devolvido pelo endpoint (envio e tentativa). */
const CULTBR_LOGS_STATUS_KEYS = {
    pending: 'status_pending',
    success: 'status_success',
    error: 'status_error',
    simulated: 'status_simulated',
    abandoned: 'status_abandoned',
    rejected: 'status_rejected',
};

/** Ícone que representa cada status na listagem (nomes do iconset do tema). */
const CULTBR_LOGS_STATUS_ICONS = {
    pending: 'clock',
    success: 'circle-checked',
    error: 'exclamation',
    simulated: 'code',
    abandoned: 'exchange',
    rejected: 'close',
};

app.component('opportunity-cultbr-logs', {
    template: $TEMPLATES['opportunity-cultbr-logs'],
    emits: [],

    setup() {
        const translateMessage = Utils.getTexts('opportunity-cultbr-logs');
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
            logs: [],
            total: 0,
            isLoading: false,
            isLoadingMore: false,
            hasError: false,
        };
    },

    created() {
        // Scope próprio: `new API('aldirblanc')` é um singleton por objectType:scope e já é
        // instanciado por outros componentes da tela — sem scope, o cacheMode abaixo seria
        // ignorado (ou vazaria para eles).
        this.api = new API('aldirblanc', 'cultbr-logs', { cacheMode: 'no-store' });
        this.load();
    },

    computed: {
        hasMore() {
            return this.logs.length < this.total;
        },
    },

    methods: {
        async fetchLogs(append = false) {
            const url = this.api.createUrl('opportunityCultLogs', {
                opportunityId: this.entity.id,
                skip: append ? this.logs.length : 0,
                limit: CULTBR_LOGS_PAGE_SIZE,
            });

            const response = await this.api.GET(url);
            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(body?.error ?? body?.message ?? this.translateMessage('erro_carregar'));
            }

            const rows = Array.isArray(body?.data) ? body.data : [];
            this.logs = append ? this.logs.concat(rows) : rows;
            // Sem linhas novas, o total é o que já está em tela — evita "Carregar mais" eterno
            // quando o total do servidor diverge da listagem.
            this.total = rows.length ? Number(body?.total ?? this.logs.length) : this.logs.length;
        },

        async load() {
            if (this.isLoading || this.isLoadingMore) {
                return;
            }

            this.isLoading = true;
            try {
                await this.fetchLogs();
                this.hasError = false;
            } catch (loadError) {
                console.error('Erro ao carregar logs do CultBr:', loadError);
                this.hasError = true;
                this.messages.error(loadError.message);
            } finally {
                this.isLoading = false;
            }
        },

        /** Falha ao paginar mantém em tela o que já foi carregado; só avisa o usuário. */
        async loadMore() {
            if (this.isLoading || this.isLoadingMore) {
                return;
            }

            this.isLoadingMore = true;
            try {
                await this.fetchLogs(true);
            } catch (loadMoreError) {
                console.error('Erro ao carregar mais logs do CultBr:', loadMoreError);
                this.messages.error(loadMoreError.message);
            } finally {
                this.isLoadingMore = false;
            }
        },

        statusLabel(status) {
            const messageKey = CULTBR_LOGS_STATUS_KEYS[status];
            return messageKey ? this.translateMessage(messageKey) : status;
        },

        /** Envio sem autor (sync em lote, execução por CLI) não exibe nada — ver v-if no template. */
        authorLabel(log) {
            return this.translateMessage('por_usuario', {id: log.user.id});
        },

        hasResponseBody(attempt) {
            const response = attempt.response;
            return response !== null && response !== undefined && Object.keys(response).length > 0;
        },

        statusIcon(status) {
            return CULTBR_LOGS_STATUS_ICONS[status] ?? 'circle';
        },

        /**
         * Copia o bloco de código, como nos blocos de código do GitHub.
         * `navigator.clipboard` só existe em contexto seguro (HTTPS/localhost) — em ambiente
         * servido por HTTP puro cai no execCommand, que ainda funciona nos navegadores atuais.
         */
        async copyToClipboard(content) {
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(content);
                } else if (!this.copyUsingSelection(content)) {
                    throw new Error('Cópia não suportada neste contexto.');
                }
                this.messages.success(this.translateMessage('copiado'));
            } catch (copyError) {
                console.error('Erro ao copiar para a área de transferência:', copyError);
                this.messages.error(this.translateMessage('erro_copiar'));
            }
        },

        /** Fallback: seleciona o texto num campo fora da tela e usa o copy do documento. */
        copyUsingSelection(content) {
            const field = document.createElement('textarea');
            field.value = content;
            field.setAttribute('readonly', '');
            field.style.position = 'fixed';
            field.style.top = '-9999px';
            document.body.appendChild(field);

            field.select();
            field.setSelectionRange(0, field.value.length);

            let copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (execError) {
                copied = false;
            }

            document.body.removeChild(field);

            return copied;
        },

        attemptLabel(attempt) {
            return this.translateMessage('tentativa', {
                atual: attempt.attempt,
                total: attempt.maxAttempts,
            });
        },

        /** Data e hora no locale da aplicação, via McDate (components-base). */
        formatDate(isoDate) {
            if (!isoDate) {
                return '';
            }

            const parsed = new Date(isoDate);
            if (Number.isNaN(parsed.getTime())) {
                return isoDate;
            }

            return new McDate(parsed).format({ dateStyle: 'short', timeStyle: 'medium' });
        },

        formatJson(value) {
            if (value === null || value === undefined) {
                return '';
            }
            // Resposta não-JSON é gravada como {"raw": "..."} pelo CultBrRequestLogService —
            // com _truncated quando passou do teto de tamanho.
            if (typeof value === 'object' && 'raw' in value) {
                return value._truncated
                    ? String(value.raw) + '\n\n' + this.translateMessage('resposta_truncada', {bytes: value._originalLength})
                    : String(value.raw);
            }
            try {
                return JSON.stringify(value, null, 2);
            } catch (stringifyError) {
                return String(value);
            }
        },
    },
});
