<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

$this->import('
    mc-card
    mc-alert
    mc-link
    mc-loading
    mc-select
');
?>
<mc-card class="resultado-beneficiarios">
    <template #title>
        <h3 class="resultado-beneficiarios__titulo">{{ translateMessage('titulo') }}</h3>
    </template>
    <template #content>
        <mc-loading :condition="isLoading"></mc-loading>

        <template v-if="!isLoading">
            <mc-alert v-if="hasError" type="danger">
                <p>{{ translateMessage('erro_carregar') }}</p>
                <button class="button button--sm button--text" @click="carregar">{{ translateMessage('tentar_novamente') }}</button>
            </mc-alert>

            <template v-else>
                <p class="resultado-beneficiarios__descricao">{{ translateMessage('descricao') }}</p>

                <p v-if="!campos.length" class="resultado-beneficiarios__nota">{{ translateMessage('sem_campos') }}</p>

                <div class="resultado-beneficiarios__campos">
                    <div v-for="coluna in colunas" :key="coluna.chave" class="field field--horizontal resultado-beneficiarios__linha">
                        <label class="field__title">{{ coluna.label }}</label>
                        <div class="field__input">
                            <mc-select
                                :default-value="mapeamento[coluna.chave] ?? ''"
                                :options="opcoesSelect"
                                :placeholder="translateMessage('nao_se_aplica')"
                                show-filter
                                @change-option="definir(coluna.chave, $event)">
                            </mc-select>
                        </div>
                    </div>
                </div>

                <template v-if="tiposDeProponente.length">
                    <p class="resultado-beneficiarios__descricao">{{ translateMessage('descricao_documento') }}</p>

                    <div class="resultado-beneficiarios__campos">
                        <div v-for="tipo in tiposDeProponente" :key="tipo" class="field field--horizontal resultado-beneficiarios__linha">
                            <label class="field__title">{{ tipo }}</label>
                            <div class="field__input">
                                <mc-select
                                    :default-value="documentoPorTipo[tipo] ?? ''"
                                    :options="opcoesSelect"
                                    :placeholder="translateMessage('nao_se_aplica')"
                                    show-filter
                                    @change-option="definirDocumento(tipo, $event)">
                                </mc-select>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="resultado-beneficiarios__acoes">
                    <mc-link class="button button--primary" :entity="entity" route="resultadoBeneficiarios" icon="list" target="_blank" rel="noopener">
                        {{ translateMessage('abrir_grade') }}
                    </mc-link>
                </div>

                <div class="resultado-beneficiarios__status" aria-live="polite">
                    <template v-if="isSaving">{{ translateMessage('salvando') }}</template>
                    <template v-else-if="salvoEm">{{ translateMessage('salvo_as') }} {{ salvoEm.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) }}</template>
                </div>
            </template>
        </template>
    </template>
</mc-card>
