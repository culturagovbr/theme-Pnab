<?php
/**
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

$this->import('
    mc-alert
    mc-card
    mc-icon
    mc-loading
');
?>
<mc-card class="beneficiarios-grade" :class="{ 'beneficiarios-grade--tela-cheia': telaCheia }">
    <template #title>
        <div class="beneficiarios-grade__cabecalho">
            <div class="beneficiarios-grade__identidade">
                <h3 class="beneficiarios-grade__titulo">{{ translateMessage('titulo') }}</h3>
                <p v-if="linhas.length" class="beneficiarios-grade__meta">
                    <span class="beneficiarios-grade__edital">{{ identificadorDoEdital }}</span>
                    <span>{{ contempladas }}</span>
                </p>
            </div>
            <button v-if="linhas.length" class="button button--primary-outline beneficiarios-grade__alternar" :title="rotuloTelaCheia" :aria-label="rotuloTelaCheia" @click="alternarTelaCheia">
                <mc-icon :name="telaCheia ? 'fullscreen-exit' : 'fullscreen'"></mc-icon>
            </button>
        </div>
    </template>
    <template #content>
        <mc-loading :condition="isLoading"></mc-loading>

        <template v-if="!isLoading">
            <mc-alert v-if="hasError" type="danger">
                <p>{{ translateMessage('erro_carregar') }}</p>
                <button class="button button--sm button--text" @click="carregar">{{ translateMessage('tentar_novamente') }}</button>
            </mc-alert>

            <template v-else>
                <p v-if="!linhas.length" class="beneficiarios-grade__vazio">{{ translateMessage('sem_beneficiarios') }}</p>

                <template v-else>
                    <p class="beneficiarios-grade__descricao">{{ translateMessage('descricao') }}</p>

                    <div class="beneficiarios-grade__rolagem">
                        <table class="beneficiarios-grade__tabela">
                            <thead>
                                <tr>
                                    <th class="beneficiarios-grade__fixa">{{ translateMessage('inscricao') }}</th>
                                    <th v-for="coluna in colunasVisiveis" :key="coluna.chave" :class="classeDaColuna(coluna)">
                                        {{ rotuloDaColuna(coluna) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="linha in linhas" :key="linha.registrationId">
                                    <th scope="row" class="beneficiarios-grade__fixa beneficiarios-grade__linha">
                                        <span class="beneficiarios-grade__identificacao">
                                            <span class="beneficiarios-grade__marca" :class="{ 'beneficiarios-grade__marca--completa': !linha.pendencias.length }" :title="situacaoDaLinha(linha)"></span>
                                            <span class="beneficiarios-grade__numero">{{ linha.number }}</span>
                                        </span>
                                        <span class="beneficiarios-grade__estado">{{ estadoDaLinha(linha) }}</span>
                                    </th>
                                    <td v-for="coluna in colunasVisiveis" :key="coluna.chave" :class="classeDaColuna(coluna)">
                                        <span v-if="!coluna.editavel" class="beneficiarios-grade__automatico">{{ valorAutomatico(linha, coluna) || '—' }}</span>
                                        <div v-else class="field beneficiarios-grade__campo" :class="{ 'beneficiarios-grade__campo--pendente': linha.pendencias.includes(coluna.chave) }">
                                            <div class="field__input">
                                                <input v-if="coluna.tipo === 'numero'" type="number" min="0" :value="valorExibido(linha, coluna)" @input="definirCelula(linha, coluna, $event.target.value)">
                                                <input v-else-if="coluna.tipo === 'moeda'" type="number" step="0.01" min="0" :value="valorExibido(linha, coluna)" @input="definirCelula(linha, coluna, $event.target.value)">
                                                <input v-else-if="coluna.tipo === 'data'" type="date" :value="dataParaCampo(linha, coluna)" @change="definirData(linha, coluna, $event.target.value)">
                                                <input v-else-if="coluna.tipo === 'lista'" type="text" :title="translateMessage('lista_dica')" :value="valorExibido(linha, coluna)" @change="definirCelula(linha, coluna, $event.target.value)">
                                                <input v-else type="text" :value="valorExibido(linha, coluna)" @change="definirCelula(linha, coluna, $event.target.value)">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="total > limit" class="beneficiarios-grade__rodape">
                        <span class="beneficiarios-grade__intervalo">{{ intervalo }}</span>
                        <div class="beneficiarios-grade__botoes">
                            <button class="button button--primary-outline" :disabled="skip === 0" @click="paginar(skip - limit)">
                                {{ translateMessage('anterior') }}
                            </button>
                            <button class="button button--primary-outline" :disabled="!temProxima" @click="paginar(skip + limit)">
                                {{ translateMessage('proxima') }}
                            </button>
                        </div>
                    </div>
                </template>
            </template>
        </template>
    </template>
</mc-card>
