<?php
/**
 * Campo Segmento artístico-cultural com opções especiais (tema Pnab).
 * - Dois checkboxes: "Não se aplica" e "Todas as opções".
 * - Quando "Não se aplica" está marcado: oculta "Todas as opções" e o select.
 */

use MapasCulturais\i;

$this->import('
    entity-field
    mc-multiselect
    mc-tag-list
');
?>
<div v-if="description" class="field" :class="[{error: hasErrors}, {disabled: readonly}, classes]" data-field="segmento">
    <label class="field__title" :for="propId">
        {{ description.label }}
        <span v-if="description.required" class="required">*<?php i::_e('obrigatório') ?></span>
        <slot name="info"></slot>
    </label>
    <div class="opportunity-segmento-field__row field__input">
        <div class="opportunity-segmento-field__checkboxes">
            <label class="opportunity-segmento-field__checkbox">
                <input
                    type="checkbox"
                    :checked="isNaoSeAplica"
                    @change="onNaoSeAplicaChange"
                />
                <span>{{ naoSeAplicaLabel }}</span>
            </label>
            <label v-if="!isNaoSeAplica" class="opportunity-segmento-field__checkbox">
                <input
                    type="checkbox"
                    :checked="isTodasOpcoes"
                    @change="onTodasOpcoesChange"
                />
                <span>{{ todaOpcoesLabel }}</span>
            </label>
        </div>
        <div v-if="!isNaoSeAplica" class="field__group opportunity-segmento-field__select">
            <mc-multiselect
                placeholder="Digite para buscar"
                :model="segmentoArray"
                :items="segmentoOptionsForSelect"
                hide-filter
                hide-button
                @selected="onSelected"
                @removed="onRemovedFromSelect"
            ></mc-multiselect>
            <mc-tag-list
                :tags="tagsForDisplay"
                :labels="description.options"
                classes="opportunity__background"
                editable
                @remove="onRemove"
            ></mc-tag-list>
        </div>
        <div v-if="isSegmentoOutros" class="opportunity-segmento-field__outros">
            <entity-field :entity="entity" prop="segmentoOutros" :autosave="autosave">
                <template #info>
                    <span class="required">*<?php i::_e('obrigatório') ?></span>
                </template>
            </entity-field>
        </div>
    </div>
    <small v-if="hasErrors" class="field__error">{{ errorsText }}</small>
</div>
