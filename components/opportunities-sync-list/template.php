<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    mc-avatar
    mc-confirm-button
    mc-entities
    mc-icon
');
?>

<mc-entities
    ref="list"
    type="opportunity"
    select="id,name,type,files.avatar"
    order="name ASC"
    :query="query"
    :limit="pageSize"
    @fetch="loadStatus">

    <template #header="{ entities }">
        <form class="opportunities-sync__filters panel__row" @submit="$event.preventDefault();">
            <input type="search" class="entity-tabs__search-input"
                :aria-label="translateMessage('palavras_chave')"
                :placeholder="translateMessage('buscar')"
                v-model="entities.query['@keyword']"
                @keyup="search(entities)">

            <div class="opportunities-sync__modes" role="group" :aria-label="translateMessage('modo')">
                <button type="button" class="opportunities-sync__mode"
                    :class="{ 'opportunities-sync__mode--active': onlySyncable }"
                    :aria-pressed="onlySyncable"
                    @click="setOnlySyncable(true, entities)">
                    {{ translateMessage('modo_sincronizaveis') }}
                </button>

                <button type="button" class="opportunities-sync__mode"
                    :class="{ 'opportunities-sync__mode--active': !onlySyncable }"
                    :aria-pressed="!onlySyncable"
                    @click="setOnlySyncable(false, entities)">
                    {{ translateMessage('modo_todas') }}
                </button>
            </div>
        </form>

        <div class="opportunities-sync__actions panel__row">
            <label class="opportunities-sync__select-all">
                <input type="checkbox"
                    :checked="allSyncableSelected(entities)"
                    :disabled="!syncableEntities(entities).length"
                    @change="toggleAll(entities)">
                {{ translateMessage('selecionar_todas') }}
            </label>

            <div class="opportunities-sync__summary">
                <span class="opportunities-sync__counter"> {{ translateMessage('selecionadas', { total: selectedCount }) }} </span>

                <span class="opportunities-sync__limit" v-if="exceedsLimit">
                    {{ translateMessage('acima_do_teto', { max: maxPerRequest }) }}
                </span>

                <mc-confirm-button
                    :title="translateMessage('sincronizar')"
                    :message="confirmationMessage"
                    :yes="translateMessage('confirmar_sim')"
                    :no="translateMessage('confirmar_nao')"
                    @confirm="sync()">
                    <template #button="{ open }">
                        <button type="button" class="button button--primary opportunities-sync__sync" :disabled="!canSync" @click="open()">
                            {{ translateMessage('sincronizar') }}
                        </button>
                    </template>
                </mc-confirm-button>
            </div>
        </div>
    </template>

    <template #empty>
        <p class="panel__row"> {{ translateMessage('sem_oportunidades') }} </p>
    </template>

    <template #default="{ entities }">
        <ul class="opportunities-sync__list">
            <li class="opportunity-sync-card" v-for="entity in entities" :key="entity.id"
                :class="{ 'opportunity-sync-card--blocked': ineligibilityReason(entity) }">

                <label class="opportunity-sync-card__select">
                    <input type="checkbox"
                        :checked="isSelected(entity)"
                        :disabled="!isSyncable(entity)"
                        @change="toggle(entity)">
                </label>

                <mc-avatar :entity="entity" size="xsmall"></mc-avatar>

                <div class="opportunity-sync-card__info">
                    <h2 class="opportunity-sync-card__name"> {{ entity.name }} </h2>
                    <p class="opportunity-sync-card__meta">
                        <span class="opportunity-sync-card__id"> {{ translateMessage('id', { id: entity.id }) }} </span>
                        <span class="opportunity-sync-card__type" v-if="entity.type?.name"> {{ entity.type.name }} </span>
                    </p>
                    <p class="opportunity-sync-card__reason" v-if="ineligibilityReason(entity)">
                        {{ ineligibilityReason(entity) }}
                    </p>
                </div>

                <div class="opportunity-sync-card__status">
                    <template v-if="lastSync(entity)">
                        <mc-icon :name="lastSyncIcon(entity)"></mc-icon>
                        <span> {{ lastSyncLabel(entity) }} </span>
                        <time :datetime="lastSync(entity).date"> {{ formatDate(lastSync(entity).date) }} </time>
                    </template>
                    <span v-else-if="statusUnavailable(entity)" class="opportunity-sync-card__never">
                        {{ translateMessage('situacao_indisponivel') }}
                    </span>
                    <span v-else class="opportunity-sync-card__never"> {{ translateMessage('nunca_enviada') }} </span>
                </div>

                <a class="opportunity-sync-card__logs" :href="logsUrl(entity)" target="_blank" rel="noopener">
                    {{ translateMessage('logs') }}
                </a>
            </li>
        </ul>
    </template>
</mc-entities>
