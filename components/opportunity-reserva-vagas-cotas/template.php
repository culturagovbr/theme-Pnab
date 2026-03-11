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

    <small class="opportunity-reserva-vagas-cotas__total">
        <span class="opportunity-reserva-vagas-cotas__total-label">Total</span>
        <span class="opportunity-reserva-vagas-cotas__total-vagas">{{ totalVagas }}</span>
        <span class="opportunity-reserva-vagas-cotas__total-valor">{{ totalAllocatedValueFormatted }}</span>
        <span class="opportunity-reserva-vagas-cotas__total-automatico">{{ totalPercentFormatted }}</span>
        <span class="opportunity-reserva-vagas-cotas__total-acoes"></span>
    </small>

    <div class="opportunity-reserva-vagas-cotas__content" v-for="(quota, index) in quotas" :key="index" :data-field-identifier="getFieldIdentifier(index)" :class="{ 'opportunity-reserva-vagas-cotas__content--lei': isLawQuota(index), 'opportunity-reserva-vagas-cotas__content--ampla': isGeneralCompetition(index), 'opportunity-reserva-vagas-cotas__content--extra': isExtraQuota(index), 'opportunity-reserva-vagas-cotas__content--error': hasErrorForIndex(index) }">
        <div class="field">
            <input v-if="isFixedQuota(index)" class="field__input" type="text" :value="quota.label" readonly disabled>
            <input v-else class="field__input" type="text" v-model.trim="quota.label" :placeholder="text('descricao')" @blur="onBlurField(index)">
        </div>
        <div class="field">
            <input class="field__input" type="number" min="0" step="1" v-model.number="quota.vagas" :disabled="isFixedQuota(index) && quota.naoAplicavel" @blur="onBlurField(index)">
        </div>
        <div class="field">
            <mc-currency-input class="field__input" :key="`valor-${index}-${quota.naoAplicavel}-${quota.valorDestinado}`" v-model.lazy="quota.valorDestinado" :disabled="isFixedQuota(index) && quota.naoAplicavel" @blur="onBlurField(index)"></mc-currency-input>
        </div>
        <div class="field opportunity-reserva-vagas-cotas__cell-automatico">
            <span v-if="isFixedQuota(index)" class="opportunity-reserva-vagas-cotas__percentual" :aria-label="text('automatico')">{{ quotaPercent(quota) }}</span>
            <span v-else class="opportunity-reserva-vagas-cotas__percentual">—</span>
        </div>
        <div class="field opportunity-reserva-vagas-cotas__cell-checkbox">
            <label v-if="isFixedQuota(index)" class="field__group field__checkbox">
                <input type="checkbox" v-model="quota.naoAplicavel" @change="onNotApplicableChange(quota)">
                <span>{{ text('naoAplicavel') }}</span>
            </label>
            <span v-else-if="isPendingNewQuota(index)" class="opportunity-reserva-vagas-cotas__actions-pending">
                <button type="button" class="opportunity-reserva-vagas-cotas__confirm" @click="confirmNewQuota()" :aria-label="text('confirmarCota')">
                    <mc-icon name="check" class="success__color"></mc-icon>
                    <span>{{ text('confirmarCota') }}</span>
                </button>
                <button type="button" class="opportunity-reserva-vagas-cotas__delete" @click="cancelNewQuota()" :aria-label="text('cancelarAdicaoCota')">
                    <mc-icon name="trash" class="danger__color"></mc-icon>
                    <span>{{ text('cancelarAdicaoCota') }}</span>
                </button>
            </span>
            <mc-confirm-button v-else @confirm="removeQuota(index)">
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
        <button type="button" class="button button--primary button--icon opportunity-reserva-vagas-cotas__add-btn" @click="addQuota" :disabled="pendingNewQuotaIndex !== null">
            <mc-icon name="add"></mc-icon>
            <label>{{ text('adicionarCota') }}</label>
        </button>
    </div>

    <small v-if="hasError" class="field__error" role="alert">{{ errorMessage }}</small>
</div>
