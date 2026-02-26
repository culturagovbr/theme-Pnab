<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * Estrutura alinhada ao entity-field e _field.scss do tema Mapas.
 */

use MapasCulturais\i;

?>
<div class="opportunity-recursos-outras-fontes col-12">
    <!-- Pergunta principal: mesmo padrão do entity-field -->
    <div class="field col-12" :class="{ error: hasError }">
        <label class="field__title">
            {{ text('perguntaPrincipal') }}
            <span class="required">*<?php i::_e('obrigatório') ?></span>
        </label>
        <small class="field__description">{{ text('subtextoPrincipal') }}</small>
        <div class="field__input">
            <select v-model="houveUtilizacao">
                <option value="sim">{{ text('sim') }}</option>
                <option value="nao">{{ text('nao') }}</option>
            </select>
        </div>
        <small v-if="hasError" class="field__error" role="alert">{{ errorMessage }}</small>
    </div>

    <template v-if="isSim">
        <div class="opportunity-recursos-outras-fontes__detalhamento col-12">
            <h4 class="opportunity-recursos-outras-fontes__detalhamento-titulo">
                {{ text('detalhamentoTitulo') }}
                <span class="required">*<?php i::_e('obrigatório') ?></span>
            </h4>
            <p v-if="!algumaFonteMarcada" class="field__error" role="alert">{{ text('alertaNenhumaFonte') }}</p>

            <div class="grid-12">
                <!-- 1. Recursos próprios -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="recursosPropriosChecked" />
                            <span>{{ text('recursosProprios') }}</span>
                        </label>
                    </div>
                    <template v-if="recursosPropriosChecked">
                        <label class="field__title">{{ text('valorRecurso') }} (R$)</label>
                        <div class="field__input">
                            <mc-currency-input :model-value="data.recursosProprios ?? 0" @update:model-value="onCurrencyChange($event, 'recursosProprios')" />
                        </div>
                    </template>
                </div>

                <!-- 2. Convênios/parcerias -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="conveniosParceriasChecked" />
                            <span>{{ text('conveniosParcerias') }}</span>
                        </label>
                    </div>
                    <template v-if="conveniosParceriasChecked">
                        <label class="field__title">{{ text('valorRecurso') }} (R$)</label>
                        <div class="field__input">
                            <mc-currency-input :model-value="data.conveniosParcerias ?? 0" @update:model-value="onCurrencyChange($event, 'conveniosParcerias')" />
                        </div>
                    </template>
                </div>

                <!-- 3. Emendas parlamentares -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="emendasParlamentaresChecked" />
                            <span>{{ text('emendasParlamentares') }}</span>
                        </label>
                    </div>
                    <template v-if="emendasParlamentaresChecked">
                        <label class="field__title">{{ text('valorRecurso') }} (R$)</label>
                        <div class="field__input">
                            <mc-currency-input :model-value="data.emendasParlamentares ?? 0" @update:model-value="onCurrencyChange($event, 'emendasParlamentares')" />
                        </div>
                    </template>
                </div>

                <!-- 4. Recursos remanescentes do ciclo 1 -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="remanescentesCiclo1Checked" />
                            <span>{{ text('remanescentesCiclo1') }}</span>
                        </label>
                    </div>
                    <template v-if="remanescentesCiclo1Checked">
                        <label class="field__title">{{ text('valorRecurso') }} (R$)</label>
                        <div class="field__input">
                            <mc-currency-input :model-value="data.remanescentesCiclo1 ?? 0" @update:model-value="onCurrencyChange($event, 'remanescentesCiclo1')" />
                        </div>
                    </template>
                </div>

                <!-- 5. Recursos de outras fontes (lista dinâmica) -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" v-model="outrasFontesChecked" />
                            <span>{{ text('outrasFontes') }}</span>
                        </label>
                    </div>
                    <template v-if="outrasFontesChecked">
                        <div v-for="(entrada, index) in outrasFontesList" :key="entrada._id || index" class="field col-12 grid-12">
                            <div class="col-12">
                                <div class="field__group" style="flex-direction: row; justify-content: space-between; align-items: center;">
                                    <span class="field__title">{{ text('fonteRecurso') }} {{ index + 1 }}</span>
                                    <button type="button" class="button button--danger button--sm" @click="removerOutraFonte(index)" :aria-label="<?php i::esc_attr_e('Remover'); ?>">
                                        <?= i::__('Excluir') ?>
                                    </button>
                                </div>
                                <div class="grid-12">
                                    <div class="field col-8 sm:col-12">
                                        <label class="field__title">{{ text('nomeFonte') }}</label>
                                        <div class="field__input">
                                            <input type="text" v-model="entrada.nomeFonte" :placeholder="text('nomeFontePlaceholder')" />
                                        </div>
                                    </div>
                                    <div class="field col-4 sm:col-12">
                                        <label class="field__title">{{ text('valorRecurso') }} (R$)</label>
                                        <div class="field__input">
                                            <mc-currency-input :model-value="entrada.valor ?? 0" @update:model-value="onOutraFonteCurrencyChange(index, $event)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <p v-if="!podeIncluirOutraFonte" class="field__error">{{ text('preenchaNomesParaIncluir') }}</p>
                            <button type="button" class="button button--primary-outline" @click="incluirOutraFonte" :disabled="!podeIncluirOutraFonte">
                                {{ text('incluir') }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
