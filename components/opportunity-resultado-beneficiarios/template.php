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

            <p v-else-if="!temPlanilha" class="resultado-beneficiarios__nota">{{ planilha?.motivo ?? translateMessage('sem_integracao') }}</p>

            <template v-else>
                <p class="resultado-beneficiarios__descricao">{{ translateMessage('descricao', { modelo: planilha.nomeModelo }) }}</p>

                <p v-if="!campos.length" class="resultado-beneficiarios__nota">{{ translateMessage('sem_campos') }}</p>

                <div class="resultado-beneficiarios__campos">
                    <div v-for="coluna in colunas" :key="coluna.coluna" class="field field--horizontal resultado-beneficiarios__linha">
                        <div class="field__title">
                            <label :for="'de-para-' + coluna.coluna">{{ coluna.nome }}</label>
                            <small class="resultado-beneficiarios__exigencia" :class="{ 'resultado-beneficiarios__exigencia--obrigatoria': coluna.obrigatoriedade === 'obrigatorio' }">{{ exigenciaDe(coluna) }}</small>
                            <small v-if="coluna.observacao" class="resultado-beneficiarios__observacao">{{ coluna.observacao }}</small>
                        </div>

                        <div v-if="coluna.porTipoDeProponente" class="resultado-beneficiarios__tipos">
                            <div v-for="tipo in tiposDeProponente" :key="tipo" class="field__input">
                                <small>{{ tipo }}</small>
                                <select class="primary__border--solid" :aria-label="coluna.nome + ' - ' + tipo" :value="fonteDe(coluna, tipo)" @change="definirPorTipo(coluna, tipo, $event.target.value)">
                                    <option value="">{{ translateMessage('sem_fonte') }}</option>
                                    <optgroup v-for="grupo in gruposDeCampos" :key="grupo.titulo" :label="grupo.titulo">
                                        <option v-for="campo in grupo.campos" :key="campo.id" :value="'campo:' + campo.id">{{ campo.title }}</option>
                                    </optgroup>
                                </select>
                                <small v-if="recusaDe(coluna, tipo)" class="field__error" role="alert">{{ recusaDe(coluna, tipo) }}</small>
                            </div>
                        </div>

                        <div v-else class="field__input">
                            <select :id="'de-para-' + coluna.coluna" class="primary__border--solid" :value="fonteDe(coluna)" @change="definir(coluna, $event.target.value)">
                                <option value="">{{ translateMessage('sem_fonte') }}</option>
                                <optgroup :label="translateMessage('da_inscricao')">
                                    <option v-for="dado in dadosDaInscricao" :key="dado.fonte" :value="dado.fonte">{{ dado.title }}</option>
                                </optgroup>
                                <optgroup v-for="grupo in gruposDeCampos" :key="grupo.titulo" :label="grupo.titulo">
                                    <option v-for="campo in grupo.campos" :key="campo.id" :value="'campo:' + campo.id">{{ campo.title }}</option>
                                </optgroup>
                            </select>

                            <div v-if="valoresDe(coluna).length" class="resultado-beneficiarios__equivalencias">
                                <small>{{ translateMessage('equivalencias') }}</small>
                                <div v-for="valor in valoresDe(coluna)" :key="valor" class="field__input resultado-beneficiarios__equivalencia">
                                    <small>{{ valor }}</small>
                                    <select class="primary__border--solid" :aria-label="coluna.nome + ' - ' + valor" :value="equivalenciaDe(coluna, valor)" @change="definirEquivalencia(coluna, valor, $event.target.value)">
                                        <option value="">{{ translateMessage('sem_equivalencia') }}</option>
                                        <option v-for="opcao in coluna.opcoes" :key="opcao" :value="opcao">{{ opcao }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
