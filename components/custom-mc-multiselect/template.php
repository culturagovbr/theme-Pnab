<?php
/**
 * Multiselect customizado (tema Pnab): checkboxes "Não se aplica" e "Todas as opções",
 * e campo "Outros (especificar)" quando aplicável.
 * Genérico: recebe prop (ex: segmento, pauta, etapa, territorio) e outrosProp (ex: segmentoOutros).
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    mc-multiselect
    mc-tag-list
');
?>
<div v-if="description" class="field" :class="[{error: hasErrors}, {disabled: readonly}, classes]" :data-field="prop">
    <label class="field__title" :for="propId">
        {{ description.label }}
        <span v-if="description.required" class="required">*<?php i::_e('obrigatório') ?></span>
        <slot name="info"></slot>
    </label>
    <div class="custom-mc-multiselect__row field__input">
        <div class="custom-mc-multiselect__checkboxes">
            <label class="custom-mc-multiselect__checkbox">
                <input
                    type="checkbox"
                    :checked="isNotApplicable"
                    @change="onNotApplicableChange"
                />
                <span>{{ notApplicableText }}</span>
            </label>
            <label v-if="!isNotApplicable && showAllOptions" class="custom-mc-multiselect__checkbox">
                <input
                    type="checkbox"
                    :checked="isAllOptionsSelected"
                    @change="onAllOptionsChange"
                />
                <span>{{ allOptionsLabel }}</span>
            </label>
        </div>
        <div v-if="!isNotApplicable" class="field__group custom-mc-multiselect__select">
            <mc-multiselect
                placeholder="Digite para buscar"
                :model="valueArray"
                :items="optionsForSelect"
                :preserve-order="true"
                hide-filter
                hide-button
                @selected="onSelected"
                @removed="onRemovedFromSelect"
            ></mc-multiselect>
            <mc-tag-list
                :tags="tagsForDisplay"
                :labels="description.options"
                classes="custom-mc-multiselect__tags"
                editable
                @remove="onRemove"
            ></mc-tag-list>
        </div>
        <div v-if="outrosProp && isOutrosSelected" class="custom-mc-multiselect__outros">
            <entity-field :entity="entity" :prop="outrosProp" :autosave="autosave">
                <template #info>
                    <span class="required">*<?php i::_e('obrigatório') ?></span>
                </template>
            </entity-field>
        </div>
    </div>
    <small v-if="hasErrors" class="field__error">{{ errorsText }}</small>
    <hr class="custom-mc-multiselect__separator">
</div>
