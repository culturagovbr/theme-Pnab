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
    mc-icon
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
                    <span v-if="questao.requerida" class="required">*<?php i::_e('obrigatório') ?></span>
                </label>
                <small v-if="questao.descricao" class="field__description">{{ questao.descricao }}</small>

                <div v-if="tipoDoCampo(questao) === 'tabela-automatica'" class="resultado-formulario__tabela resultado-formulario__tabela--auto">
                    <table>
                        <thead>
                            <tr><th v-for="coluna in colunasDe(questao)" :key="coluna.key">{{ coluna.label }}</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="(linha, indice) in valorAutomatico(questao)" :key="indice">
                                <td v-for="coluna in colunasDe(questao)" :key="coluna.key">{{ celulaAutomaticaFormatada(questao, linha, coluna) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'número'" class="field__input">
                    <input type="number" :id="'resultado-questao-' + questao.id_questao" :value="numeroDe(questao)" :min="questao.valor_minimo ?? undefined" :max="questao.valor_maximo ?? undefined" :placeholder="questao.placeholder ?? ''" @input="definirTexto(questao, $event.target.value)">
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'moeda'" class="field__input">
                    <mc-currency-input :model-value="moedaDe(questao)" @update:model-value="definir(questao, $event)"></mc-currency-input>
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'data'" class="field__input">
                    <input type="date" :id="'resultado-questao-' + questao.id_questao" :value="valorDe(questao) ?? ''" @input="definirTexto(questao, $event.target.value)">
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'textarea'" class="field__input">
                    <textarea :id="'resultado-questao-' + questao.id_questao" rows="4" :placeholder="questao.placeholder ?? ''" :value="valorDe(questao) ?? ''" @input="definirTexto(questao, $event.target.value)"></textarea>
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'booleano'" class="field__input">
                    <select :id="'resultado-questao-' + questao.id_questao" :value="booleanoDe(questao)" @change="definirBooleano(questao, $event.target.value)">
                        <option value=""><?php i::_e('Selecione') ?></option>
                        <option value="sim"><?php i::_e('Sim') ?></option>
                        <option value="nao"><?php i::_e('Não') ?></option>
                    </select>
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'múltipla escolha'" class="field__group resultado-formulario__opcoes">
                    <label v-for="item in itensDe(questao)" :key="item.id ?? item.valor" class="field__checkbox">
                        <input type="checkbox" :checked="marcado(questao, item.valor)" @change="alternarItem(questao, item.valor)">
                        <span>{{ item.label ?? item.valor }}</span>
                    </label>

                    <div v-if="questao.permite_inclusao_outros" class="resultado-formulario__outros">
                        <label class="field__title">{{ translateMessage('outros') }}</label>
                        <div class="field__input">
                            <input type="text" :value="outrosDe(questao)" @change="definirOutros(questao, $event.target.value)">
                        </div>
                    </div>
                </div>

                <div v-else-if="tipoDoCampo(questao) === 'Tabela Dinâmica'" class="resultado-formulario__tabela">
                    <table>
                        <thead>
                            <tr>
                                <th v-for="coluna in colunasDe(questao)" :key="coluna.key">{{ coluna.label }}</th>
                                <th v-if="podeRemoverLinha(questao)"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(linha, indice) in linhasDe(questao)" :key="indice">
                                <td v-for="coluna in colunasDe(questao)" :key="coluna.key">
                                    <input v-if="coluna.type === 'number'" type="number" :disabled="coluna.readOnly" :min="coluna.min ?? undefined" :value="celulaDe(questao, indice, coluna.key)" @input="definirCelula(questao, indice, coluna.key, $event.target.value)">
                                    <input v-else-if="coluna.type === 'currency'" type="number" step="0.01" :disabled="coluna.readOnly" :min="coluna.min ?? undefined" :value="celulaDe(questao, indice, coluna.key)" @input="definirCelula(questao, indice, coluna.key, $event.target.value)">
                                    <input v-else type="text" :disabled="coluna.readOnly" :maxlength="coluna.max ?? undefined" :value="celulaDe(questao, indice, coluna.key)" @input="definirCelula(questao, indice, coluna.key, $event.target.value)">
                                </td>
                                <td v-if="podeRemoverLinha(questao)" class="resultado-formulario__tabela-remover">
                                    <button type="button" class="button button--sm button--text" @click="removerLinha(questao, indice)"><?php i::_e('Remover') ?></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <button v-if="podeAdicionarLinha(questao)" type="button" class="button button--sm button--secondary" @click="adicionarLinha(questao)">
                        {{ rotuloAdicionarLinha(questao) }}
                    </button>
                </div>

                <!-- texto e qualquer tipo novo do PAR: campo de texto simples -->
                <div v-else class="field__input">
                    <input type="text" :id="'resultado-questao-' + questao.id_questao" :maxlength="questao.valor_maximo ?? undefined" :placeholder="questao.placeholder ?? ''" :value="valorDe(questao) ?? ''" @input="definirTexto(questao, $event.target.value)">
                </div>

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
