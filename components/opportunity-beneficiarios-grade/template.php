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
                <a class="beneficiarios-grade__numero" :href="entity.singleUrl" target="_blank" rel="noopener">{{ entity.number }}</a>
            </span>
            <span class="beneficiarios-grade__estado">{{ estadoDe(entity) }}</span>
        </template>

        <template v-for="coluna in colunasVisiveis" #[coluna.chave]="{entity}" :key="coluna.chave">
            <span v-if="!coluna.editavel" class="beneficiarios-grade__automatico" :class="{ 'beneficiarios-grade__automatico--pendente': celulaPendente(entity, coluna) }">{{ valorAutomatico(entity, coluna) || '—' }}</span>

            <div v-else-if="editandoCelula(entity, coluna)" class="field beneficiarios-grade__campo" :class="{ 'beneficiarios-grade__campo--pendente': celulaPendente(entity, coluna) }">
                <div class="field__input">
                    <mc-select v-if="coluna.tipo === 'selecao'" class="beneficiarios-grade__selecao" :default-value="valorCru(entity, coluna)" :placeholder="coluna.label" @change-option="definirSelecaoUnica(entity, coluna, $event)">
                        <option value="">&mdash;</option>
                        <option v-for="opcao in coluna.opcoes" :key="opcao" :value="opcao">{{ opcao }}</option>
                    </mc-select>
                    <mc-multiselect v-else-if="coluna.opcoes" class="beneficiarios-grade__opcoes" :model="selecaoAtiva" :items="itensDaColuna(coluna)" :placeholder="coluna.label" @selected="definirSelecao(entity, coluna)" @removed="definirSelecao(entity, coluna)" hide-button></mc-multiselect>
                    <mc-currency-input v-else-if="coluna.tipo === 'moeda'" :model-value="valorCru(entity, coluna)" @update:model-value="definirCelula(entity, coluna, $event)" @blur="sairDaCelula(entity, coluna, $event)" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)"></mc-currency-input>
                    <input v-else-if="coluna.tipo === 'numero'" type="number" min="0" :value="valorCru(entity, coluna)" @input="definirCelula(entity, coluna, $event.target.value)" @blur="sairDaCelula(entity, coluna, $event)" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)" @keydown.escape="encerrarEdicao">
                    <input v-else-if="coluna.tipo === 'data'" type="date" :value="dataParaCampo(entity, coluna)" @change="definirData(entity, coluna, $event.target.value)" @blur="sairDaCelula(entity, coluna, $event)" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)" @keydown.escape="encerrarEdicao">
                    <input v-else-if="coluna.chave === 'cep_local_execucao'" v-maska data-maska="#####-###" type="text" inputmode="numeric" :value="valorExibido(entity, coluna)" @input="definirCelula(entity, coluna, $event.target.value)" @blur="sairDaCelula(entity, coluna, $event)" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)" @keydown.escape="encerrarEdicao">
                    <input v-else-if="coluna.chave === 'cpfs_remunerados'" type="text" :title="translateMessage('lista_dica')" :value="valorExibido(entity, coluna)" @change="definirCelula(entity, coluna, $event.target.value)" @blur="sairDaCelula(entity, coluna, $event)" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)" @keydown.escape="encerrarEdicao">
                    <input v-else type="text" :value="valorExibido(entity, coluna)" @change="definirCelula(entity, coluna, $event.target.value)" @blur="sairDaCelula(entity, coluna, $event)" @keydown.enter.prevent="confirmarCelula(entity, coluna, $event)" @keydown.escape="encerrarEdicao">
                </div>
            </div>

            <span v-else class="beneficiarios-grade__valor" :class="{ 'beneficiarios-grade__valor--pendente': celulaPendente(entity, coluna) }" tabindex="0" role="button" :title="translateMessage('editar')" @click="abrirEdicao(entity, coluna, $event)" @focus="abrirEdicao(entity, coluna, $event)">
                {{ valorExibido(entity, coluna) || '—' }}
            </span>
        </template>
    </entity-table>
</div>
