<?php

/**
 * Card do resultado CultBR na Configuração de fases: visual de fase (calha, marcador e caixa)
 * com cor própria, sem ser uma fase — montado pelo override de opportunity-phases-config.
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    mc-alert
    mc-icon
    mc-link
    mc-loading
    opportunity-resultado-formulario
');
?>
<div v-if="visivel || isLoading" class="resultado-cultbr-card" :class="{'resultado-cultbr-card--travado': travado}">
    <!-- Esqueleto durante a consulta à Conecta: o card não aparece "do nada". -->
    <div v-if="isLoading" class="resultado-cultbr-card__item resultado-cultbr-card__item--loading">
        <mc-loading :condition="true"></mc-loading>
    </div>

    <template v-if="!isLoading && hasError">
        <mc-alert type="danger">
            <p>{{ translateMessage('erro_carregar') }}</p>
            <button class="button button--sm button--text" @click="load">{{ translateMessage('tentar_novamente') }}</button>
        </mc-alert>
    </template>

    <div v-if="!isLoading && !hasError" class="resultado-cultbr-card__item">
        <div v-if="travado" class="resultado-cultbr-card__aviso">
            <span v-if="estadoLabel" class="resultado-cultbr-card__chip" :class="'resultado-cultbr-card__chip--' + estado">{{ estadoLabel }}</span>
            <p class="resultado-cultbr-card__nota">{{ travadoAviso }}</p>
        </div>

        <mc-alert v-if="!travado && isFormularioAtualizado" type="warning">{{ translateMessage('formulario_atualizado') }}</mc-alert>

        <mc-alert v-if="!travado && envioAlerta" :key="ultimoEnvio.situacao" :type="envioAlerta" class="resultado-cultbr-card__situacao">
            <strong>{{ translateMessage('situacao_do_envio') }}</strong>
            <p>{{ envioTexto }}</p>
            <p v-if="ultimoEnvio.mensagem">{{ ultimoEnvio.mensagem }}</p>
            <p v-if="ultimoEnvio.totalPendentes">{{ pendentesTexto }}</p>
            <ul v-if="ultimoEnvio.recusas.length">
                <li v-for="(recusa, indice) in ultimoEnvio.recusas" :key="indice"><strong>{{ rotuloDaRecusa(recusa) }}</strong><template v-if="recusa.descricao">: {{ recusa.descricao }}</template></li>
            </ul>
            <mc-link v-if="temRecusaDeLinha" :entity="entity" route="resultadoBeneficiarios" target="_blank" rel="noopener">{{ translateMessage('abrir_lista') }}</mc-link>
        </mc-alert>

        <mc-alert v-if="!travado && bloqueios.length" type="warning">
            <p>{{ translateMessage('bloqueios') }}</p>
            <ul>
                <li v-for="bloqueio in bloqueios" :key="bloqueio.nome + bloqueio.motivo"><strong v-if="bloqueio.nome">{{ bloqueio.nome }}:</strong> {{ bloqueio.motivo }}</li>
            </ul>
        </mc-alert>

        <opportunity-resultado-formulario
            v-if="!travado"
            :estado="estado"
            :estado-label="estadoLabel"
            :estrutura="estrutura"
            :respostas="respostas"
            :respostas-automaticas="respostasAutomaticas"
            :questoes-sem-fonte="questoesSemFonte"
            :erros="errosDosCampos"
            :anexos="anexos"
            :anexando="anexando"
            :planilha="planilha"
            :salvando="isSaving"
            :salvo-em="salvoEm"
            @alterado="isAlterado = $event"
            @anexar="anexar"
            @retirar="retirar"
            @salvar="salvarRespostas">
        </opportunity-resultado-formulario>

        <template v-if="!travado">
            <div v-if="pendencias.length" class="resultado-cultbr-card__impedimentos">
                <p>{{ translateMessage('pendencias') }}</p>
                <ul>
                    <li v-for="pendencia in pendencias" :key="pendencia.id_questao"><strong v-if="pendencia.nome">{{ pendencia.nome }}:</strong> {{ pendencia.motivo }}</li>
                </ul>
            </div>

            <p v-if="temErros" class="resultado-cultbr-card__nota">{{ translateMessage('erros_no_formulario') }}</p>
        </template>

        <div v-if="!travado" class="resultado-cultbr-card__envio">
            <button v-if="!enviado" class="button button--primary" :class="{ disabled: !podeEnviar }" :disabled="!podeEnviar" @click="enviar">
                {{ isEnviando ? translateMessage('enviando') : translateMessage('enviar') }}
            </button>
            <button v-else class="button button--primary" :class="{ disabled: !podeEnviar }" :disabled="!podeEnviar" @click="enviar">
                {{ isEnviando ? translateMessage('enviando') : translateMessage('retificar') }}
            </button>
        </div>
    </div>
</div>
