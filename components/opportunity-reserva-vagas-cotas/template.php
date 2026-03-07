<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * Estrutura espelhada em opportunity-ranges-config (faixas/linhas).
 */

use MapasCulturais\i;

$this->import('
    mc-confirm-button
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

    <div class="opportunity-reserva-vagas-cotas__header-row">
        <span class="opportunity-reserva-vagas-cotas__th">{{ text('descricao') }}</span>
        <span class="opportunity-reserva-vagas-cotas__th">{{ text('numeroVagas') }}</span>
        <span class="opportunity-reserva-vagas-cotas__th">{{ text('valorDestinado') }} (R$)</span>
        <span class="opportunity-reserva-vagas-cotas__th">{{ text('automatico') }}</span>
        <span class="opportunity-reserva-vagas-cotas__th">Ações</span>
    </div>

    <div class="opportunity-reserva-vagas-cotas__content" v-for="(cota, index) in cotas" :key="index">
        <div class="field">
            <input v-if="isCotaFixa(index)" class="field__input" type="text" :value="cota.label" readonly disabled>
            <input v-else class="field__input" type="text" v-model.trim="cota.label" :placeholder="text('descricao')" @blur="autoSave">
        </div>
        <div class="field">
            <input class="field__input" type="number" min="0" step="1" v-model.number="cota.vagas" :disabled="isCotaFixa(index) && cota.naoAplicavel" @blur="autoSave">
        </div>
        <div class="field">
            <mc-currency-input class="field__input" :key="`valor-${index}-${cota.naoAplicavel}-${cota.valorDestinado}`" v-model.lazy="cota.valorDestinado" :disabled="isCotaFixa(index) && cota.naoAplicavel" @blur="autoSave"></mc-currency-input>
        </div>
        <div class="field opportunity-reserva-vagas-cotas__cell-automatico">
            <span v-if="isCotaFixa(index)" class="opportunity-reserva-vagas-cotas__percentual" :aria-label="text('automatico')">{{ percentualCota(cota) }}</span>
            <span v-else class="opportunity-reserva-vagas-cotas__percentual">—</span>
        </div>
        <div class="field opportunity-reserva-vagas-cotas__cell-checkbox">
            <label v-if="isCotaFixa(index)" class="field__group field__checkbox">
                <input type="checkbox" v-model="cota.naoAplicavel" @change="onNaoAplicavelChange(cota)">
                <span>{{ text('naoAplicavel') }}</span>
            </label>
            <mc-confirm-button v-else @confirm="removeCota(index)">
                <template #button="{open}">
                    <button type="button" class="opportunity-reserva-vagas-cotas__delete" @click="open()" :aria-label="text('excluirCota')">
                        <mc-icon name="trash" class="danger__color"></mc-icon>
                        <span>{{ text('excluirCota') }}</span>
                    </button>
                </template>
                <template #message>
                    {{ text('confirmExcluirCota') }}
                </template>
            </mc-confirm-button>
        </div>
    </div>

    <div class="opportunity-reserva-vagas-cotas__add">
        <button type="button" class="button button--primary button--icon opportunity-reserva-vagas-cotas__add-btn" @click="addCota">
            <mc-icon name="add"></mc-icon>
            <label>{{ text('adicionarCota') }}</label>
        </button>
    </div>

    <small v-if="hasError" class="field__error" role="alert">{{ errorMessage }}</small>
</div>
