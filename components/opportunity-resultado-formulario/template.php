<?php

/**
 * Formulário dinâmico do resultado CultBR, no padrão de campos do tema (field/_field.scss).
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-accordion
    mc-currency-input
    opportunity-resultado-escolha
    opportunity-resultado-tabela
');
?>
<div class="resultado-formulario">
    <mc-accordion v-for="(card, indiceCard) in cards" :key="card.chave" class="resultado-formulario__card">
        <template #title>
            <span class="resultado-formulario__card-cabecalho">
                <span class="resultado-formulario__card-titulo">{{ card.titulo }}</span>
                <span v-if="indiceCard === 0 && estadoLabel" class="resultado-cultbr-card__chip" :class="'resultado-cultbr-card__chip--' + estado">{{ estadoLabel }}</span>
            </span>
        </template>
        <template #content>
        <div class="grid-12">
            <template v-for="(bloco, indiceBloco) in card.blocos" :key="bloco.automaticos ? 'autos-' + indiceBloco : bloco.questao.id_questao">

            <!-- Calculado do próprio edital, empilhado numa coluna: o campo seguinte fica ao lado -->
            <div v-if="bloco.automaticos" class="field col-6 resultado-formulario__autos">
                <div v-for="questao in bloco.questoes" :key="questao.id_questao" class="resultado-formulario__auto-linha">
                    <span class="resultado-formulario__auto-rotulo">{{ questao.nome }}:</span>
                    <span class="resultado-formulario__auto">{{ automaticoFormatado(questao) }}</span>
                </div>
            </div>

            <div v-else v-for="questao in [bloco.questao]" :key="questao.id_questao" class="field" :class="[{ error: erroDe(questao) }, campoLargo(questao) ? 'col-12' : 'col-6']">
                <label class="field__title" :for="'resultado-questao-' + questao.id_questao">
                    {{ questao.nome }}
                    <span v-if="questao.requerida && !['planilha', 'espelho'].includes(tipoDoCampo(questao))" class="required">*<?php i::_e('obrigatório') ?></span>
                </label>
                <small v-if="questao.descricao" class="field__description">{{ questao.descricao }}</small>

                <opportunity-resultado-tabela v-if="tipoDoCampo(questao) === 'tabela-automatica'" :questao="questao" :linhas="valorAutomatico(questao)" somente-leitura></opportunity-resultado-tabela>

                <p v-else-if="tipoDoCampo(questao) === 'espelho'" class="resultado-formulario__espelho">{{ valorDe(questao) ?? translateMessage('espelho') }}</p>

                <div v-else-if="tipoDoCampo(questao) === 'número'" class="field__input">
                    <input type="number" :id="'resultado-questao-' + questao.id_questao" :value="numeroDe(questao)" :min="minimoDe(questao)" :max="questao.valor_maximo ?? undefined" :step="passoDe(questao)" :placeholder="questao.placeholder ?? ''" @input="definirTexto(questao, $event.target.value)">
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'moeda'" class="field__input">
                    <mc-currency-input :model-value="moedaDe(questao)" @update:model-value="definir(questao, $event)"></mc-currency-input>
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'data'" class="field__input">
                    <input type="date" :id="'resultado-questao-' + questao.id_questao" :value="valorDe(questao) ?? ''" @input="definirTexto(questao, $event.target.value)">
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'textarea'" class="field__input">
                    <textarea :id="'resultado-questao-' + questao.id_questao" rows="4" :maxlength="limiteDe(questao)" :placeholder="questao.placeholder ?? ''" :value="valorDe(questao) ?? ''" @input="definirTexto(questao, $event.target.value)"></textarea>
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'booleano'" class="field__input">
                    <select :id="'resultado-questao-' + questao.id_questao" :value="booleanoDe(questao)" @change="definirBooleano(questao, $event.target.value)">
                        <option value=""><?php i::_e('Selecione') ?></option>
                        <option value="sim">{{ questao.config?.labelSim || '<?= i::esc_attr__('Sim') ?>' }}</option>
                        <option value="nao">{{ questao.config?.labelNao || '<?= i::esc_attr__('Não') ?>' }}</option>
                    </select>
                </div>

                <opportunity-resultado-escolha v-else-if="tipoDoCampo(questao) === 'escolha'" :questao="questao" :valor="valorDe(questao)" @definir="definir(questao, $event)"></opportunity-resultado-escolha>

                <opportunity-resultado-tabela v-else-if="tipoDoCampo(questao) === 'tabela'" :questao="questao" :linhas="linhasDe(questao)" @definir="definir(questao, $event)"></opportunity-resultado-tabela>

                <div v-else-if="tipoDoCampo(questao) === 'texto' && mascaraDe(questao)" class="field__input">
                    <input type="text" :id="'resultado-questao-' + questao.id_questao" v-maska :data-maska="mascaraDe(questao)" :placeholder="questao.placeholder ?? ''" :value="valorDe(questao) ?? ''" @input="definirTexto(questao, $event.target.value)">
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'texto'" class="field__input">
                    <input type="text" :id="'resultado-questao-' + questao.id_questao" :maxlength="limiteDe(questao)" :placeholder="questao.placeholder ?? ''" :value="valorDe(questao) ?? ''" @input="definirTexto(questao, $event.target.value)">
                </div>

                <p v-else-if="tipoDoCampo(questao) === 'planilha'" class="resultado-formulario__aviso">{{ translateMessage('planilha') }}</p>

                <div v-else-if="tipoDoCampo(questao) === 'documento'" class="resultado-formulario__aviso">
                    <p>{{ translateMessage('documento') }}</p>
                    <small v-if="formatosDe(questao)">{{ translateMessage('formatos', { formatos: formatosDe(questao) }) }}</small>
                </div>

                <p v-else class="resultado-formulario__aviso resultado-formulario__aviso--erro" role="alert">{{ translateMessage('tipo_desconhecido', { tipo: questao.tipo_questao }) }}</p>

                <p v-if="erroDe(questao)" class="field__error" role="alert">{{ erroDe(questao) }}</p>
            </div>

            </template>
        </div>
        </template>
    </mc-accordion>

    <div class="resultado-formulario__status" aria-live="polite">
        <template v-if="salvando">{{ translateMessage('salvando') }}</template>
        <template v-else-if="salvoEm">{{ translateMessage('salvo_as') }} {{ salvoEm.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}</template>
    </div>
</div>
