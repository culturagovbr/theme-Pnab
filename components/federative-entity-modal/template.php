<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-modal
');
?>

<mc-modal v-if="shouldShowModal" ref="modal" title="<?php i::_e('Escolher Ente Federado') ?>" classes="choose-federative-entity-modal">
    <template #default="modal">
        <div class="choose-federative-entity-modal__content">
            <div v-if="loading" class="choose-federative-entity-modal__loading">
                <div class="choose-federative-entity-modal__loading-spinner"></div>
                <p><?php i::_e('Carregando entes federados...') ?></p>
            </div>

            <div v-else-if="federativeEntities.length === 0" class="choose-federative-entity-modal__empty">
                <mc-icon name="info" class="choose-federative-entity-modal__empty-icon"></mc-icon>
                <p><?php i::_e('Nenhum ente federado encontrado.') ?></p>
            </div>

            <div v-else class="choose-federative-entity-modal__list">
                <p class="choose-federative-entity-modal__description">
                    <?php i::_e('Selecione o ente federado que deseja utilizar:') ?>
                </p>
                <div class="choose-federative-entity-modal__items">
                    <div
                        v-for="entity in federativeEntities"
                        :key="entity.id"
                        class="choose-federative-entity-modal__item"
                        :class="{ 'choose-federative-entity-modal__item--selected': selectedEntity?.id === entity.id }"
                        @click="selectEntity(entity)">
                        <div class="choose-federative-entity-modal__item-info">
                            <h4 class="choose-federative-entity-modal__item-name">{{ entity.name }}</h4>
                            <span class="choose-federative-entity-modal__item-document">{{ entity.document }}</span>
                        </div>
                        <div class="choose-federative-entity-modal__item-check-wrapper">
                            <mc-icon v-if="selectedEntity?.id === entity.id" name="check-circle" class="choose-federative-entity-modal__item-check"></mc-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template #actions="modal">
        <button
            class="button button--primary button--icon"
            :disabled="!selectedEntity"
            @click="confirmSelection(modal)">
            <mc-icon name="check"></mc-icon>
            <?php i::_e('Confirmar Seleção') ?>
        </button>
    </template>
</mc-modal>