<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * Outras modalidades de ações afirmativas — checkboxes, sublistas (mc-multiselect) e campo texto.
 */

use MapasCulturais\i;

$this->import('mc-multiselect
    mc-tag-list
');
?>
<div class="opportunity-outras-modalidades-acoes-afirmativas col-12">
    <div class="field col-12">
        <label class="field__title">
            {{ text('tituloSecao') }}
            <span class="required">*<?php i::_e('obrigatório') ?></span>
        </label>
        <p v-if="hasErrorNenhumaOpcao" class="field__error" role="alert">{{ text('erroNenhumaOpcao') }}</p>

        <div class="grid-12 opportunity-outras-modalidades-acoes-afirmativas__opcoes">
            <!-- 1. Não são previstas outras ações afirmativas -->
            <div class="field col-12">
                <div class="field__group">
                    <label class="field__checkbox">
                        <input type="checkbox" :checked="isNaoPrevistasMarcado" @change="setNaoPrevistas($event.target.checked)" />
                        <span>{{ text('naoPrevistas') }}</span>
                    </label>
                </div>
            </div>

            <!-- Opções com sublista (fonte: opcoesComSublista do backend) -->
            <div v-for="item in opcoesComSublista" :key="item.key" class="field col-12">
                <div class="field__group">
                    <label class="field__checkbox">
                        <input type="checkbox" :checked="isOpcaoMarcada(item.key)" :disabled="isNaoPrevistasMarcado" @change="setOpcao(item.key, $event.target.checked)" />
                        <span>{{ text(item.labelKey) }}</span>
                    </label>
                </div>
                <template v-if="isOpcaoMarcada(item.key) && !isNaoPrevistasMarcado">
                    <div class="opportunity-outras-modalidades-acoes-afirmativas__sublista field__input" :class="{ error: hasErrorForSublista(item.key) }">
                        <div class="opportunity-outras-modalidades-acoes-afirmativas__multiselect-wrap">
                            <mc-multiselect :model="getSublistModel(item.key)" :items="sublistItems" :placeholder="text('subcategoriasPlaceholder')" hide-button :preserve-order="true"></mc-multiselect>
                        </div>
                        <mc-tag-list editable classes="opportunity__background opportunity__color" :tags="getSublistModel(item.key)" :labels="sublistLabels"></mc-tag-list>
                        <p v-if="hasErrorForSublista(item.key)" class="field__error" role="alert">{{ text('erroSubcategoria') }}</p>
                    </div>
                </template>
            </div>

            <!-- Outra ação afirmativa prevista em legislação local -->
            <div class="field col-12">
                <div class="field__group">
                    <label class="field__checkbox">
                        <input type="checkbox" :checked="isOpcaoMarcada('outra_legislacao')" :disabled="isNaoPrevistasMarcado" @change="setOpcao('outra_legislacao', $event.target.checked)" />
                        <span>{{ text('outraLegislacao') }}</span>
                    </label>
                </div>
                <template v-if="isOpcaoMarcada('outra_legislacao') && !isNaoPrevistasMarcado">
                    <label class="field__title">{{ text('descrevaAcao') }}</label>
                    <div class="field__input opportunity-outras-modalidades-acoes-afirmativas__descricao-wrap" :class="{ error: hasErrorOutraLegislacao }">
                        <div class="opportunity-outras-modalidades-acoes-afirmativas__input-row">
                            <input type="text" :value="descricaoOutra" @input="setDescricaoOutra($event.target.value)" :placeholder="text('descrevaAcaoPlaceholder')" maxlength="140" />
                            <span class="opportunity-outras-modalidades-acoes-afirmativas__contador" aria-live="polite">{{ contadorCaracteres }}</span>
                        </div>
                        <p v-if="hasErrorOutraLegislacao" class="field__error" role="alert">{{ text('erroDescricao') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
