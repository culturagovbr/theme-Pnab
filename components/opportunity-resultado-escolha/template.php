<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
?>
<div class="field__group resultado-formulario__opcoes">
    <small v-if="dica" class="field__description">{{ dica }}</small>

    <div v-for="item in itens" :key="item.id" class="resultado-formulario__opcao">
        <label class="field__checkbox">
            <input :type="isUnica ? 'radio' : 'checkbox'" :name="'resultado-escolha-' + questao.id_questao" :checked="isMarcado(item)" :disabled="isBloqueado(item)" @change="alternar(item)">
            <span>{{ item.label }}</span>
        </label>
        <small v-if="item.descricao" class="field__description">{{ item.descricao }}</small>

        <div v-if="item.campoComplementar && isMarcado(item)" class="resultado-formulario__complemento">
            <label class="field__title" :for="'resultado-complemento-' + questao.id_questao + '-' + item.id">
                {{ item.campoComplementar.nome }}
            </label>
            <div class="field__input">
                <input type="text" :id="'resultado-complemento-' + questao.id_questao + '-' + item.id" :maxlength="item.campoComplementar.limiteCaracteres ?? undefined" :placeholder="item.campoComplementar.placeholder ?? ''" :disabled="somenteLeitura" :value="complementoDe(item)" @input="definirComplemento(item, $event.target.value)">
            </div>
        </div>
    </div>
</div>
