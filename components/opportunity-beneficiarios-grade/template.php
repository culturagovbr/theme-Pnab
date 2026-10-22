<?php
/**
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

$this->import('
    entity-table
    mc-alert
    mc-icon
    mc-loading
    mc-multiselect
');
?>
<div class="beneficiarios-grade" :class="{ 'beneficiarios-grade--tela-cheia': telaCheia }">
    <mc-loading :condition="isLoading"></mc-loading>

    <mc-alert v-if="hasError" type="danger">
        <p>{{ translateMessage('erro_carregar') }}</p>
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
                <span class="beneficiarios-grade__marca" :class="{ 'beneficiarios-grade__marca--completa': !pendenciasDe(entity).length }" :title="situacaoDe(entity)"></span>
                <span class="beneficiarios-grade__numero">{{ entity.number }}</span>
            </span>
            <span class="beneficiarios-grade__estado">{{ estadoDe(entity) }}</span>
        </template>

        <template v-for="coluna in colunasVisiveis" #[coluna.chave]="{entity}" :key="coluna.chave">
            <span v-if="!coluna.editavel" class="beneficiarios-grade__automatico">{{ valorAutomatico(entity, coluna) || '—' }}</span>
            <div v-else class="field beneficiarios-grade__campo" :class="{ 'beneficiarios-grade__campo--pendente': pendenciasDe(entity).includes(coluna.chave) }">
                <div class="field__input">
                    <input v-if="coluna.tipo === 'numero'" type="number" min="0" :value="valorExibido(entity, coluna)" @input="definirCelula(entity, coluna, $event.target.value)">
                    <input v-else-if="coluna.tipo === 'moeda'" type="number" step="0.01" min="0" :value="valorExibido(entity, coluna)" @input="definirCelula(entity, coluna, $event.target.value)">
                    <input v-else-if="coluna.tipo === 'data'" type="date" :value="dataParaCampo(entity, coluna)" @change="definirData(entity, coluna, $event.target.value)" @blur="apagarData(entity, coluna, $event.target.value)">
                    <input v-else-if="coluna.tipo === 'lista'" type="text" :title="translateMessage('lista_dica')" :value="valorExibido(entity, coluna)" @change="definirCelula(entity, coluna, $event.target.value)">
                    <input v-else type="text" :value="valorExibido(entity, coluna)" @change="definirCelula(entity, coluna, $event.target.value)">
                </div>
            </div>
        </template>
    </entity-table>
</div>
