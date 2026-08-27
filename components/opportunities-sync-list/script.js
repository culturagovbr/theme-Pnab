/**
 * Listagem da tela de sincronização: as oportunidades vêm da API do core, já filtradas pelo
 * servidor; a elegibilidade e o último envio de cada uma vêm do plugin, por página carregada.
 */

/** Oportunidades por página. */
const SYNC_LIST_PAGE_SIZE = 20;

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
        filters: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        return {
            pageSize: SYNC_LIST_PAGE_SIZE,
            query: { ...this.filters },
            selected: {},
            status: {},
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
    },

    methods: {
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
