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
                <div v-for="tipo in TIPOS_FORMAS" :key="tipo" class="field col-12" :class="{ error: tipo === 'email' && emailDisplayError }">
                    <div class="field__group">
                        <label class="field__checkbox">
                            <input type="checkbox" :checked="isTipoMarcado(tipo)" @change="setMarcado(tipo, $event.target.checked)" />
                            <span>{{ text(tipo) }}</span>
                        </label>
                    </div>
                    <template v-if="isTipoMarcado(tipo)">
                        <label class="field__title">{{ text('descricao') }}</label>
                        <div class="field__input">
                            <input :type="tipo === 'email' ? 'email' : 'text'" :value="getDescricao(tipo)" @input="setDescricao(tipo, $event.target.value)" @blur="tipo === 'email' && validateEmailBlur()" :placeholder="tipo === 'email' ? text('descricaoPlaceholderEmail') : text('descricaoPlaceholder')" />
                        </div>
                        <p v-if="tipo === 'email' && emailDisplayError" class="field__error" role="alert">{{ emailDisplayError }}</p>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
