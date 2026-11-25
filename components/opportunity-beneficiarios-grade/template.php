<?php
/**
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

$this->import('
    mc-select
    entity-table
    mc-alert
    mc-currency-input
    mc-icon
    mc-loading
    mc-multiselect
');
?>
<div class="beneficiarios-grade" :class="{ 'beneficiarios-grade--tela-cheia': telaCheia }">
    <mc-loading :condition="isLoading"></mc-loading>

    <mc-alert v-if="erro" type="danger">
        <p>{{ erro }}</p>
        <button class="button button--sm button--text" @click="carregarColunas">{{ translateMessage('tentar_novamente') }}</button>
    </mc-alert>

    <p v-else-if="!faseFinal" class="beneficiarios-grade__vazio">{{ translateMessage('sem_beneficiarios') }}</p>

    <entity-table v-else-if="!isLoading"
        type="registration"
        controller="opportunity"
        endpoint="findRegistrations"
        identifier="beneficiariosDoResultado"
        :query="query"
        :select="select"
        :headers="headers"
        :limit="50"
        order="number ASC"
        :raw-processor="processarLinha"
        required="number"
        :visible="visiveis"
        show-index
        hide-actions
        hide-advanced-filters>

        <template #title>
            <h3 class="beneficiarios-grade__titulo">{{ translateMessage('titulo') }}</h3>
        </template>

        <template #filters="{entities}">
            <div class="beneficiarios-grade__filtros">
                <mc-multiselect v-if="proponentTypes" class="col-2" :model="selectedProponentTypes" :items="proponentTypes" placeholder="<?= MapasCulturais\i::esc_attr__('Tipos de proponente') ?>" @selected="filtrarPorTipoDeProponente(entities)" @removed="filtrarPorTipoDeProponente(entities)" hide-filter hide-button></mc-multiselect>
                <mc-multiselect v-if="ranges" class="col-2" :model="selectedRanges" :items="ranges" placeholder="<?= MapasCulturais\i::esc_attr__('Faixas') ?>" @selected="filtrarPorFaixa(entities)" @removed="filtrarPorFaixa(entities)" hide-filter hide-button></mc-multiselect>
                <button class="button button--primary-outline beneficiarios-grade__alternar" :title="rotuloTelaCheia" :aria-label="rotuloTelaCheia" @click="alternarTelaCheia">
                    <mc-icon :name="telaCheia ? 'fullscreen-exit' : 'fullscreen'"></mc-icon>
                </button>
            </div>
        </template>

        <template #number="{entity}">
            <span class="beneficiarios-grade__identificacao">
                <span class="beneficiarios-grade__marca" :class="{ 'beneficiarios-grade__marca--completa': isCompleta(entity) }" :title="situacaoDe(entity)"></span>
                <a class="beneficiarios-grade__numero" :href="entity.singleUrl" target="_blank" rel="noopener">{{ entity.number }}</a>
            </span>
            <span class="beneficiarios-grade__estado">{{ estadoDe(entity) }}</span>
        </template>

        <template v-for="coluna in colunas" #[coluna.coluna]="{entity}" :key="coluna.coluna">
            <div v-if="editandoCelula(entity, coluna)" class="field beneficiarios-grade__campo" :class="{ 'beneficiarios-grade__campo--pendente': pendenciaDe(entity, coluna) }">
                <div class="field__input">
                    <mc-select v-if="coluna.tipo === 'lista'" class="beneficiarios-grade__selecao" :default-value="valorCru(entity, coluna)" :placeholder="coluna.nome" @change-option="definirSelecaoUnica(entity, coluna, $event)">
                        <option value="">&mdash;</option>
                        <option v-for="opcao in coluna.opcoes" :key="opcao" :value="opcao">{{ opcao }}</option>
                    </mc-select>
                    <mc-currency-input v-else-if="coluna.tipo === 'monetario'" :model-value="numero(valorCru(entity, coluna))" @update:model-value="definirCelula(entity, coluna, $event)" @blur="encerrarEdicao" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)"></mc-currency-input>
                    <input v-else-if="coluna.tipo === 'cep'" v-maska data-maska="#####-###" type="text" inputmode="numeric" :value="valorExibido(entity, coluna)" @change="definirCelula(entity, coluna, $event.target.value)" @blur="encerrarEdicao" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)" @keydown.escape="encerrarEdicao">
                    <input v-else type="text" :value="valorExibido(entity, coluna)" @change="definirCelula(entity, coluna, $event.target.value)" @blur="encerrarEdicao" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)" @keydown.escape="encerrarEdicao">
                </div>
            </div>

            <span v-else class="beneficiarios-grade__valor" :class="{ 'beneficiarios-grade__valor--pendente': pendenciaDe(entity, coluna) || naoGravadaDe(entity, coluna), 'beneficiarios-grade__valor--inteiro': coluna.tipo !== 'lista' && coluna.tipo !== 'texto' }" tabindex="0" role="button" :title="dicaDaCelula(entity, coluna)" @click="abrirEdicao(entity, coluna, $event)" @focus="abrirEdicao(entity, coluna, $event)">
                {{ valorExibido(entity, coluna) || '—' }}
            </span>
            <small v-if="naoGravadaDe(entity, coluna) || pendenciaDe(entity, coluna)" class="field__error beneficiarios-grade__motivo" role="alert">{{ naoGravadaDe(entity, coluna) || pendenciaDe(entity, coluna) }}</small>
        </template>
    </entity-table>
</div>
