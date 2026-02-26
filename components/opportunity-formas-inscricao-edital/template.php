<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * Formas de inscrição previstas no edital — estrutura alinhada ao entity-field e opportunity-recursos-outras-fontes.
 */

use MapasCulturais\i;

?>
<div class="opportunity-formas-inscricao-edital col-12">
    <div class="field col-12" :class="{ error: hasError }">
        <label class="field__title">
            {{ text('perguntaPrincipal') }}
            <span class="required">*<?php i::_e('obrigatório') ?></span>
        </label>
        <div class="field__input">
            <select v-model="previstasNoEdital">
                <option value="sim">{{ text('sim') }}</option>
                <option value="nao">{{ text('nao') }}</option>
            </select>
        </div>
    </div>

    <template v-if="isSim">
        <div class="opportunity-formas-inscricao-edital__detalhamento col-12">
            <h4 class="opportunity-formas-inscricao-edital__detalhamento-titulo">
                {{ text('detalhamentoTitulo') }}
                <span class="required">*<?php i::_e('obrigatório') ?></span>
            </h4>
            <p v-if="!algumaFormaMarcada" class="field__error" role="alert">{{ text('alertaNenhumaForma') }}</p>
            <p v-if="algumaFormaMarcada && !todasDescricoesPreenchidas" class="field__error" role="alert">{{ text('alertaDescricaoObrigatoria') }}</p>

            <div class="grid-12">
                <!-- E-mail -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" :checked="isTipoMarcado('email')" @change="setMarcado('email', $event.target.checked)" />
                            <span>{{ text('email') }}</span>
                        </label>
                    </div>
                    <template v-if="isTipoMarcado('email')">
                        <label class="field__title">{{ text('descricao') }}</label>
                        <div class="field__input">
                            <input type="text" :value="getDescricao('email')" @input="setDescricao('email', $event.target.value)" :placeholder="text('descricaoPlaceholder')" />
                        </div>
                    </template>
                </div>

                <!-- Presencial -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" :checked="isTipoMarcado('presencial')" @change="setMarcado('presencial', $event.target.checked)" />
                            <span>{{ text('presencial') }}</span>
                        </label>
                    </div>
                    <template v-if="isTipoMarcado('presencial')">
                        <label class="field__title">{{ text('descricao') }}</label>
                        <div class="field__input">
                            <input type="text" :value="getDescricao('presencial')" @input="setDescricao('presencial', $event.target.value)" :placeholder="text('descricaoPlaceholder')" />
                        </div>
                    </template>
                </div>

                <!-- Correspondência -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" :checked="isTipoMarcado('correspondencia')" @change="setMarcado('correspondencia', $event.target.checked)" />
                            <span>{{ text('correspondencia') }}</span>
                        </label>
                    </div>
                    <template v-if="isTipoMarcado('correspondencia')">
                        <label class="field__title">{{ text('descricao') }}</label>
                        <div class="field__input">
                            <input type="text" :value="getDescricao('correspondencia')" @input="setDescricao('correspondencia', $event.target.value)" :placeholder="text('descricaoPlaceholder')" />
                        </div>
                    </template>
                </div>

                <!-- Oralidade -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" :checked="isTipoMarcado('oralidade')" @change="setMarcado('oralidade', $event.target.checked)" />
                            <span>{{ text('oralidade') }}</span>
                        </label>
                    </div>
                    <template v-if="isTipoMarcado('oralidade')">
                        <label class="field__title">{{ text('descricao') }}</label>
                        <div class="field__input">
                            <input type="text" :value="getDescricao('oralidade')" @input="setDescricao('oralidade', $event.target.value)" :placeholder="text('descricaoPlaceholder')" />
                        </div>
                    </template>
                </div>

                <!-- Outros -->
                <div class="field col-12">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" :checked="isTipoMarcado('outros')" @change="setMarcado('outros', $event.target.checked)" />
                            <span>{{ text('outros') }}</span>
                        </label>
                    </div>
                    <template v-if="isTipoMarcado('outros')">
                        <label class="field__title">{{ text('descricao') }}</label>
                        <div class="field__input">
                            <input type="text" :value="getDescricao('outros')" @input="setDescricao('outros', $event.target.value)" :placeholder="text('descricaoPlaceholder')" />
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
