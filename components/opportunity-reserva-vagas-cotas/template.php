<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * Estrutura espelhada em opportunity-ranges-config (faixas/linhas).
 */

use MapasCulturais\i;

$this->import('
    mc-currency-input
    mc-icon
');
?>
<div class="opportunity-reserva-vagas-cotas" :class="{ 'field--error': hasError }">
    <div class="opportunity-reserva-vagas-cotas__header">
        <h4 class="bold">
            {{ text('sectionTitle') }}
        </h4>
        <h6>{{ text('sectionDescription') }}</h6>
        <div class="opportunity-reserva-vagas-cotas__hint">
            <strong>{{ text('infoBlockTitle') }}</strong>
            <ul>
                <li>{{ text('infoBlockItem1') }}</li>
                <li>{{ text('infoBlockItem2') }}</li>
                <li>{{ text('infoBlockItem3') }}</li>
            </ul>
        </div>
    </div>

    <div class="opportunity-reserva-vagas-cotas__content" v-for="(cota, index) in cotas" :key="index">
        <div class="field">
            <label>{{ text('descricao') }}</label>
            <input class="field__input" type="text" :value="cota.label" readonly disabled>
        </div>
        <div class="field">
            <h6>{{ text('numeroVagas') }}</h6>
            <input class="field__input" type="number" min="0" step="1" v-model.number="cota.vagas" :disabled="cota.naoAplicavel" @blur="autoSave">
        </div>
        <div class="field">
            <h6>{{ text('valorDestinado') }} (R$)</h6>
            <mc-currency-input class="field__input" :key="`valor-${index}-${cota.naoAplicavel}-${cota.valorDestinado}`" v-model.lazy="cota.valorDestinado" :disabled="cota.naoAplicavel" @blur="autoSave"></mc-currency-input>
        </div>
        <div class="field opportunity-reserva-vagas-cotas__cell-automatico">
            <h6>{{ text('automatico') }}</h6>
            <span class="opportunity-reserva-vagas-cotas__percentual" :aria-label="text('automatico')">{{ percentualCota(cota) }}</span>
        </div>
        <div class="field opportunity-reserva-vagas-cotas__cell-checkbox">
            <label class="field__group field__checkbox">
                <input type="checkbox" v-model="cota.naoAplicavel" @change="onNaoAplicavelChange(cota)">
                <span>{{ text('naoAplicavel') }}</span>
            </label>
        </div>
    </div>

    <small v-if="hasError" class="field__error" role="alert">{{ errorMessage }}</small>
</div>
