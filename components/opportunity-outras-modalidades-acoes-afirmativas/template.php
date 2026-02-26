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

            <!-- 2. Bônus de pontuação para agentes culturais -->
            <div class="field col-12">
                <div class="field__group">
                    <label class="field__checkbox">
                        <input type="checkbox" :checked="isOpcaoMarcada('bonus_agentes')" :disabled="isNaoPrevistasMarcado" @change="setOpcao('bonus_agentes', $event.target.checked)" />
                        <span>{{ text('bonusAgentes') }}</span>
                    </label>
                </div>
                <template v-if="isOpcaoMarcada('bonus_agentes') && !isNaoPrevistasMarcado">
                    <div class="opportunity-outras-modalidades-acoes-afirmativas__sublista field__input" :class="{ error: hasErrorForSublista('bonus_agentes') }">
                        <div class="opportunity-outras-modalidades-acoes-afirmativas__multiselect-wrap">
                            <mc-multiselect :model="getSublistModel('bonus_agentes')" :items="sublistItems" :placeholder="text('subcategoriasPlaceholder')" hide-button :preserve-order="true"></mc-multiselect>
                        </div>
                        <mc-tag-list editable classes="opportunity__background opportunity__color" :tags="getSublistModel('bonus_agentes')" :labels="sublistLabels"></mc-tag-list>
                        <p v-if="hasErrorForSublista('bonus_agentes')" class="field__error" role="alert">{{ text('erroSubcategoria') }}</p>
                    </div>
                </template>
            </div>

            <!-- 3. Bônus de pontuação para projetos com temáticas específicas -->
            <div class="field col-12">
                <div class="field__group">
                    <label class="field__checkbox">
                        <input type="checkbox" :checked="isOpcaoMarcada('bonus_tematicas')" :disabled="isNaoPrevistasMarcado" @change="setOpcao('bonus_tematicas', $event.target.checked)" />
                        <span>{{ text('bonusTematicas') }}</span>
                    </label>
                </div>
                <template v-if="isOpcaoMarcada('bonus_tematicas') && !isNaoPrevistasMarcado">
                    <div class="opportunity-outras-modalidades-acoes-afirmativas__sublista field__input" :class="{ error: hasErrorForSublista('bonus_tematicas') }">
                        <div class="opportunity-outras-modalidades-acoes-afirmativas__multiselect-wrap">
                            <mc-multiselect :model="getSublistModel('bonus_tematicas')" :items="sublistItems" :placeholder="text('subcategoriasPlaceholder')" hide-button :preserve-order="true"></mc-multiselect>
                        </div>
                        <mc-tag-list editable classes="opportunity__background opportunity__color" :tags="getSublistModel('bonus_tematicas')" :labels="sublistLabels"></mc-tag-list>
                        <p v-if="hasErrorForSublista('bonus_tematicas')" class="field__error" role="alert">{{ text('erroSubcategoria') }}</p>
                    </div>
                </template>
            </div>

            <!-- 4. Categoria específica -->
            <div class="field col-12">
                <div class="field__group">
                    <label class="field__checkbox">
                        <input type="checkbox" :checked="isOpcaoMarcada('categoria_especifica')" :disabled="isNaoPrevistasMarcado" @change="setOpcao('categoria_especifica', $event.target.checked)" />
                        <span>{{ text('categoriaEspecifica') }}</span>
                    </label>
                </div>
                <template v-if="isOpcaoMarcada('categoria_especifica') && !isNaoPrevistasMarcado">
                    <div class="opportunity-outras-modalidades-acoes-afirmativas__sublista field__input" :class="{ error: hasErrorForSublista('categoria_especifica') }">
                        <div class="opportunity-outras-modalidades-acoes-afirmativas__multiselect-wrap">
                            <mc-multiselect :model="getSublistModel('categoria_especifica')" :items="sublistItems" :placeholder="text('subcategoriasPlaceholder')" hide-button :preserve-order="true"></mc-multiselect>
                        </div>
                        <mc-tag-list editable classes="opportunity__background opportunity__color" :tags="getSublistModel('categoria_especifica')" :labels="sublistLabels"></mc-tag-list>
                        <p v-if="hasErrorForSublista('categoria_especifica')" class="field__error" role="alert">{{ text('erroSubcategoria') }}</p>
                    </div>
                </template>
            </div>

            <!-- 5. Edital específico -->
            <div class="field col-12">
                <div class="field__group">
                    <label class="field__checkbox">
                        <input type="checkbox" :checked="isOpcaoMarcada('edital_especifico')" :disabled="isNaoPrevistasMarcado" @change="setOpcao('edital_especifico', $event.target.checked)" />
                        <span>{{ text('editalEspecifico') }}</span>
                    </label>
                </div>
                <template v-if="isOpcaoMarcada('edital_especifico') && !isNaoPrevistasMarcado">
                    <div class="opportunity-outras-modalidades-acoes-afirmativas__sublista field__input" :class="{ error: hasErrorForSublista('edital_especifico') }">
                        <div class="opportunity-outras-modalidades-acoes-afirmativas__multiselect-wrap">
                            <mc-multiselect :model="getSublistModel('edital_especifico')" :items="sublistItems" :placeholder="text('subcategoriasPlaceholder')" hide-button :preserve-order="true"></mc-multiselect>
                        </div>
                        <mc-tag-list editable classes="opportunity__background opportunity__color" :tags="getSublistModel('edital_especifico')" :labels="sublistLabels"></mc-tag-list>
                        <p v-if="hasErrorForSublista('edital_especifico')" class="field__error" role="alert">{{ text('erroSubcategoria') }}</p>
                    </div>
                </template>
            </div>

            <!-- 6. Outra ação afirmativa prevista em legislação local -->
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
