<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-modal
    mc-icon
');
?>

<div class="federative-entity-selector">
    <div v-if="loading" class="federative-entity-selector__loading">
        <div class="federative-entity-selector__loading-spinner"></div>
        <p><?php i::_e('Carregando entes federados...') ?></p>
    </div>

    <div v-else-if="federativeEntities.length === 0" class="federative-entity-selector__empty">
        <mc-icon name="info" class="federative-entity-selector__empty-icon"></mc-icon>
        <p><?php i::_e('Nenhum ente federado encontrado.') ?></p>
    </div>

    <template v-else>
        <form class="entity-tabs__filters federative-entity-selector__filters panel__row" @submit="$event.preventDefault();">
            <input type="search" class="entity-tabs__search-input"
                aria-label="<?= i::__('Palavras-chave') ?>"
                placeholder="<?= i::__('Buscar por nome ou CNPJ') ?>"
                v-model="keyword">

            <label> <?= i::__("Ordernar por:") ?>
                <select class="entity-tabs__search-select primary__border--solid" v-model="order">
                    <option value="name ASC"><?= i::__('Ordem alfabética') ?></option>
                    <option value="name DESC"><?= i::__('Ordem alfabética inversa') ?></option>
                </select>
            </label>
        </form>

        <div v-if="filteredFederativeEntities.length === 0" class="federative-entity-selector__empty">
            <mc-icon name="info" class="federative-entity-selector__empty-icon"></mc-icon>
            <p><?php i::_e('Nenhum ente federado encontrado.') ?></p>
        </div>

        <div v-else class="federative-entity-selector__list">
            <div class="federative-entity-selector__items">
                <div
                    v-for="entity in filteredFederativeEntities"
                    :key="entity.id"
                    class="federative-entity-selector__item"
                    :class="{ 'federative-entity-selector__item--selected': selectedEntity?.id === entity.id }"
                    @click="selectEntity(entity)">
                    <div class="federative-entity-selector__item-info">
                        <h4 class="federative-entity-selector__item-name">{{ entity.name }}</h4>
                        <span class="federative-entity-selector__item-document">{{ entity.document }}</span>
                        <span
                            v-if="!entity.hasParData"
                            class="federative-entity-selector__item-badge">{{ text('Dados do PAR ausentes ou em análise') }}</span>
                    </div>
                    <div class="federative-entity-selector__item-check-wrapper">
                        <mc-icon v-if="selectedEntity?.id === entity.id" name="check-circle" class="federative-entity-selector__item-check"></mc-icon>
                    </div>
                </div>
            </div>

            <div class="federative-entity-selector__actions">
                <button
                    class="button button--primary button--icon"
                    :disabled="!selectedEntity || loading"
                    @click="confirmSelection">
                    <mc-icon name="check"></mc-icon>
                    <?php i::_e('Confirmar Seleção') ?>
                </button>
            </div>
        </div>
    </template>

    <mc-modal
        ref="parWarningModal"
        title="<?= i::esc_attr__('Dados do PAR ausentes ou em análise') ?>"
        classes="federative-entity-selector__par-warning"
        @close="discardPendingEntity">
        <template #button>
            <!-- Botão vazio para não renderizar o botão padrão do modal -->
        </template>
        <template #default>
            <p><?php i::_e('Os dados do PAR deste ente federado estão ausentes ou em análise, então não é possível criar novas oportunidades por ele.') ?></p>
            <p><?php i::_e('As oportunidades que você já criou continuam disponíveis para edição normalmente.') ?></p>
        </template>
        <template #actions="modal">
            <button class="button button--text" @click="modal.close()">
                <?php i::_e('Escolher outro ente') ?>
            </button>
            <button class="button button--primary" @click="confirmPendingEntity(modal)">
                <?php i::_e('Continuar mesmo assim') ?>
            </button>
        </template>
    </mc-modal>
</div>
