/**
 * Listagem da tela de sincronização: as oportunidades vêm da API do core, já filtradas pelo
 * servidor; a elegibilidade e o último envio de cada uma vêm do plugin, por página carregada.
 */

const SYNC_LIST_PAGE_SIZE = 20;
const SYNC_LIST_SEARCH_DEBOUNCE_MS = 500;

/** Rótulo de cada desfecho devolvido pelo endpoint de status. */
const SYNC_LIST_STATUS_KEYS = {
    pending: 'status_pending',
    success: 'status_success',
    error: 'status_error',
    simulated: 'status_simulated',
    abandoned: 'status_abandoned',
    rejected: 'status_rejected',
};

/** Ícone de cada desfecho de envio (nomes do iconset do tema). */
const SYNC_LIST_STATUS_ICONS = {
    pending: 'clock',
    success: 'circle-checked',
    error: 'exclamation',
    simulated: 'code',
    abandoned: 'exchange',
    rejected: 'close',
};

app.component('opportunities-sync-list', {
    template: $TEMPLATES['opportunities-sync-list'],

    setup() {
        const translateMessage = Utils.getTexts('opportunities-sync-list');
        const messages = useMessages();
        return { translateMessage, messages };
    },

    props: {
        /** Publicadas e raiz que a regra aprova. */
        syncableFilters: {
            type: Object,
            default: () => ({}),
        },

        /** Publicadas e raiz, elegíveis ou não. */
        listingFilters: {
            type: Object,
            default: () => ({}),
        },

        /** Teto do endpoint de disparo; acima dele a seleção é recusada pelo servidor. */
        maxPerRequest: {
            type: Number,
            required: true,
        },
    },

    data() {
        return {
            pageSize: SYNC_LIST_PAGE_SIZE,
            onlySyncable: true,
            query: { ...this.syncableFilters },
            selected: {},
            status: {},
            isSyncing: false,
        };
    },

    created() {
        this.api = new API('aldirblanc', 'opportunities-sync', { cacheMode: 'no-store' });
    },

    computed: {
        selectedIds() {
            return Object.keys(this.selected).map(Number);
        },

        selectedCount() {
            return this.selectedIds.length;
        },

        exceedsLimit() {
            return this.selectedCount > this.maxPerRequest;
        },

        canSync() {
            return this.selectedCount > 0 && !this.exceedsLimit && !this.isSyncing;
        },

        confirmationMessage() {
            return this.translateMessage('confirmar', { total: this.selectedCount });
        },
    },

    methods: {
        /**
         * mc-entities.refresh() não zera a página: depois de "carregar mais", uma consulta nova
         * viria da página corrente e a lista abriria pelo meio.
         */
        refreshFromFirstPage(entities, debounce = 0) {
            if (this.$refs.list) {
                this.$refs.list.page = 1;
            }

            entities.refresh(debounce);
        },

        search(entities) {
            this.refreshFromFirstPage(entities, SYNC_LIST_SEARCH_DEBOUNCE_MS);
        },

        /** Troca o recorte preservando a busca em curso. */
        setOnlySyncable(onlySyncable, entities) {
            if (this.onlySyncable === onlySyncable) {
                return;
            }

            this.onlySyncable = onlySyncable;

            const keyword = entities.query['@keyword'];
            const filters = onlySyncable ? this.syncableFilters : this.listingFilters;
            const query = { ...filters };

            if (keyword) {
                query['@keyword'] = keyword;
            }

            // As duas: o mc-entities mescla a prop com a referência do created, e trocar só uma deixa vazar filtro do modo anterior.
            this.query = query;
            entities.query = query;
            this.refreshFromFirstPage(entities);
        },

        /** Cada carga pede ao plugin só o que ainda não tem: "carregar mais" acumula na mesma lista. */
        async loadStatus(entities) {
            const ids = entities
                .map((entity) => Number(entity.id))
                .filter((id) => id && !(id in this.status));

            if (!ids.length) {
                return;
            }

            try {
                const url = this.api.createUrl('opportunitiesSyncStatus', { opportunityIds: ids.join(',') });
                const response = await this.api.GET(url);
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(body?.data ?? this.translateMessage('erro_status'));
                }

                this.status = { ...this.status, ...body };
            } catch (statusError) {
                console.error('Erro ao carregar o status de sincronização:', statusError);
                this.messages.error(this.translateMessage('erro_status'));
            }
        },

        statusOf(entity) {
            return this.status[entity.id] ?? null;
        },

        /** Enquanto o status não chega, a oportunidade não é selecionável: não se sabe se pode ser enviada. */
        isSyncable(entity) {
            return this.statusOf(entity)?.syncable === true;
        },

        ineligibilityReason(entity) {
            return this.statusOf(entity)?.reason ?? null;
        },

        lastSync(entity) {
            return this.statusOf(entity)?.lastSync ?? null;
        },

        /** Desfecho fora da lista conhecida aparece como veio, em vez de sumir da tela. */
        lastSyncLabel(entity) {
            const result = this.lastSync(entity)?.result;

            return SYNC_LIST_STATUS_KEYS[result] ? this.translateMessage(SYNC_LIST_STATUS_KEYS[result]) : result;
        },

        lastSyncIcon(entity) {
            return SYNC_LIST_STATUS_ICONS[this.lastSync(entity)?.result] ?? 'clock';
        },

        isSelected(entity) {
            return Boolean(this.selected[entity.id]);
        },

        toggle(entity) {
            const selected = { ...this.selected };

            if (selected[entity.id]) {
                delete selected[entity.id];
            } else if (this.isSyncable(entity)) {
                selected[entity.id] = true;
            }

            this.selected = selected;
        },

        syncableEntities(entities) {
            return entities.filter((entity) => this.isSyncable(entity));
        },

        allSyncableSelected(entities) {
            const syncable = this.syncableEntities(entities);

            return syncable.length > 0 && syncable.every((entity) => this.isSelected(entity));
        },

        toggleAll(entities) {
            const syncable = this.syncableEntities(entities);

            if (this.allSyncableSelected(entities)) {
                const selected = { ...this.selected };
                syncable.forEach((entity) => delete selected[entity.id]);
                this.selected = selected;

                return;
            }

            const selected = { ...this.selected };
            syncable.forEach((entity) => { selected[entity.id] = true; });
            this.selected = selected;
        },

        /** O job em lote revalida a elegibilidade: o que deixou de ser sincronizável é descartado lá. */
        async sync() {
            if (!this.canSync) {
                return;
            }

            this.isSyncing = true;
            const enviados = this.selectedIds;

            try {
                const response = await this.api.POST('forceResyncOpportunities', { opportunityIds: enviados });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(typeof body?.data === 'string' ? body.data : this.translateMessage('erro_sincronizar'));
                }

                this.messages.success(this.translateMessage('enfileirado', { total: body.accepted ?? enviados.length }));
                this.selected = {};
            } catch (syncError) {
                console.error('Erro ao disparar o reenvio ao CultBR:', syncError);
                this.messages.error(syncError.message);
            } finally {
                this.isSyncing = false;
            }
        },

        /** Nova aba: voltar descartaria a seleção em andamento. */
        logsUrl(entity) {
            return `${entity.editUrl}#logs-cultbr`;
        },

        formatDate(isoDate) {
            if (!isoDate) {
                return '';
            }

            const parsed = new Date(isoDate);

            return Number.isNaN(parsed.getTime())
                ? isoDate
                : new McDate(parsed).format({ dateStyle: 'short', timeStyle: 'short' });
        },
    },
});
