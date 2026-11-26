<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-currency-input
');
?>
<div class="resultado-formulario__tabela" :class="{ 'resultado-formulario__tabela--auto': somenteLeitura }">
    <table>
        <thead>
            <tr>
                <th v-for="coluna in colunas" :key="coluna.id">
                    {{ coluna.nome }}
                    <span v-if="coluna.obrigatoria && !isFixa(coluna)" class="required">*</span>
                </th>
                <th v-if="podeRemover"></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(linha, indice) in linhasExibidas" :key="indice">
                <td v-for="coluna in colunas" :key="coluna.id">
                    <span v-if="campo(coluna) === 'fixo'">{{ exibicao(coluna, linha) }}</span>
                    <input v-else-if="campo(coluna) === 'numero'" type="number" :aria-label="coluna.nome" :min="coluna.minimo ?? undefined" :max="coluna.maximo ?? undefined" :placeholder="coluna.placeholder" :value="valorDe(coluna, linha)" @input="definir(indice, coluna, $event.target.value)">
                    <mc-currency-input v-else-if="campo(coluna) === 'moeda'" :aria-label="coluna.nome" :model-value="moedaDe(coluna, linha)" @input="esvaziaSemDigito" @update:model-value="definir(indice, coluna, $event)"></mc-currency-input>
                    <select v-else-if="campo(coluna) === 'lista'" :aria-label="coluna.nome" :value="valorDe(coluna, linha)" @change="definir(indice, coluna, $event.target.value)">
                        <option value=""><?php i::_e('Selecione') ?></option>
                        <option v-for="opcao in coluna.opcoes" :key="opcao" :value="opcao">{{ opcao }}</option>
                    </select>
                    <input v-else-if="campo(coluna) === 'texto' && mascaraDe(coluna)" v-maska :data-maska="mascaraDe(coluna)" type="text" :aria-label="coluna.nome" :placeholder="coluna.placeholder" :value="valorDe(coluna, linha)" @input="definir(indice, coluna, $event.target.value)">
                    <input v-else-if="campo(coluna) === 'texto'" type="text" :aria-label="coluna.nome" :maxlength="coluna.limite ?? undefined" :placeholder="coluna.placeholder" :value="valorDe(coluna, linha)" @input="definir(indice, coluna, $event.target.value)">
                    <span v-else class="field__error">{{ translateMessage('coluna_desconhecida', { tipo: coluna.tipo }) }}</span>
                </td>
                <td v-if="podeRemover" class="resultado-formulario__tabela-remover">
                    <button type="button" class="button button--sm button--text" @click="remover(indice)"><?php i::_e('Remover') ?></button>
                </td>
            </tr>
        </tbody>
    </table>

    <button v-if="podeAdicionar" type="button" class="button button--sm button--secondary" :disabled="isNoLimite" @click="adicionar">
        {{ rotuloAdicionar }}
    </button>
</div>
