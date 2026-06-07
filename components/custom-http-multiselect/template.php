<?php
/**
 * Multiselect HTTP paginado.
 *
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-icon
    mc-multiselect
    mc-tag-list
');
?>
<div class="custom-http-multiselect" :class="classes" :data-field="prop">
    <div v-if="loading" class="custom-http-multiselect__status">
        <div class="custom-http-multiselect__spinner"></div>
        <p><?php i::_e('Estamos nos conectando à API do CultBR') ?></p>
    </div>

    <div v-else-if="hasLoadError" class="custom-http-multiselect__status custom-http-multiselect__status--error">
        <mc-icon name="alert-circle" class="custom-http-multiselect__status-icon"></mc-icon>
        <p>{{ loadErrorMessage || text('Não foi possível carregar as ações do PAR.') }}</p>
    </div>

    <div v-else-if="hasNoItems" class="custom-http-multiselect__status custom-http-multiselect__status--error">
        <mc-icon name="alert-circle" class="custom-http-multiselect__status-icon"></mc-icon>
        <p><?php i::_e('Não recebemos dados pela API CultBr') ?></p>
    </div>

    <div v-else class="field" :data-field="prop">
        <label class="field__title" :for="propId">
            {{ label }}
            <slot name="info"></slot>
        </label>

        <div class="custom-http-multiselect__row field__input">
            <div class="field__group custom-http-multiselect__select">
                <mc-multiselect
                    placeholder="Digite para buscar"
                    :model="selectedValues"
                    :items="optionsForSelect"
                    :preserve-order="true"
                    hide-filter
                    hide-button
                    :disabled="loading"
                    @selected="onSelected"
                    @removed="onRemoved"
                ></mc-multiselect>

                <mc-tag-list
                    v-if="selectedValues.length"
                    :tags="selectedValues"
                    :labels="selectedLabels"
                    classes="custom-http-multiselect__tags"
                    editable
                    @remove="onTagRemove"
                ></mc-tag-list>
            </div>

            <div v-if="hasPagination" class="custom-http-multiselect__actions">
                <div class="custom-http-multiselect__pagination-actions">
                    <button type="button" class="button button--sm custom-http-multiselect__pagination-button" :disabled="pagination.previous === null" :aria-label="text('Anterior')" @click="loadPage(pagination.previous)">
                        <mc-icon name="arrow-left"></mc-icon>
                    </button>
                    <span class="custom-http-multiselect__pagination-info">{{ paginationInfo }}</span>
                    <button type="button" class="button button--sm custom-http-multiselect__pagination-button" :disabled="pagination.next === null" :aria-label="text('Próxima')" @click="loadPage(pagination.next)">
                        <mc-icon name="arrow-right"></mc-icon>
                    </button>
                </div>
            </div>

            <hr class="custom-http-multiselect__separator">
        </div>
    </div>
</div>
