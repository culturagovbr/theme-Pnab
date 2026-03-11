<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-confirm-button
    mc-currency-input 
    mc-icon
');
?>
<div class="opportunity-ranges-config">
    <div class="opportunity-ranges-config__header">
        <h4 class="bold">
            <?= $this->text('header-title', i::__('Configurações de categorias')) ?>
            <?php $this->info('editais-oportunidades -> configuracoes -> faixas-linhas') ?>
        </h4>
        <h6><?= $this->text('header-description', i::__('Crie e configure as categorias abaixo, inserindo um breve resumo, quantidade e valor de cada uma delas.')) ?></h6>
        <small v-if="hasError" class="field__error">
            <template v-for="(err, idx) in errors" :key="idx">
                <span>{{ err }}</span><br v-if="idx < errors.length - 1" />
            </template>
        </small>
    </div>

    
    <div class="opportunity-ranges-config__content" v-for="(range, index) in entity.registrationRanges" :key="index">
        <div class="field">
            <label><?= $this->text('input-label', i::__('Categoria')) ?> {{index+1}}</label>
            <input class="field__input" type="text" v-model="range.label" @blur="autoSaveRange(range)" :ref="'description-' + index" placeholder="<?= $this->text('input-placeholder', i::__('Descrição da categoria')) ?>">
        </div>
        
        <div class="field opportunity-ranges-config__field-limit" :class="{'error': hasVacanciesError}">
            <h6><?= i::__('Quantidade de vagas') ?></h6>
            <input class="field__input" type="number" v-model="range.limit" @blur="autoSaveRange(range)">
        </div>
            
        <div class="field opportunity-ranges-config__field-value" :class="{'error': hasValuesError}">
            <h6><?= i::__('Valor') ?></h6>
            <mc-currency-input class="field__input" v-model.lazy="range.value" @blur="autoSaveRange(range)"></mc-currency-input>
        </div>
            
        <mc-confirm-button @confirm="removeRange(index)">
            <template #button="{open}">
                <div class="field__trash">
                    <mc-icon class="danger__color" name="trash" @click="open()"></mc-icon>
                </div>
            </template>
            <template #message="message">
                <?= $this->text('confirm-deletion', i::__('Deseja deletar a categoria?')) ?>
            </template>
        </mc-confirm-button>
    </div>
    
    <div class="opportunity-ranges-config__button">
        <button class="opportunity-ranges-config__button__add button button--primary button--icon" @click="addRange">
            <mc-icon name="add"></mc-icon><label><?= $this->text('add-button', i::__("Adicionar Categoria")) ?></label>
        </button>
    </div>
    
</div>

