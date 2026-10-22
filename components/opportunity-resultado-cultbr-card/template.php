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
        <!-- Aguardando publicação: ainda sem formulário; o aviso carrega o status. -->
        <div v-if="travado" class="resultado-cultbr-card__aviso">
            <span v-if="estadoLabel" class="resultado-cultbr-card__chip" :class="'resultado-cultbr-card__chip--' + estado">{{ estadoLabel }}</span>
            <p class="resultado-cultbr-card__nota">{{ translateMessage('travado_aviso') }}</p>
        </div>

        <opportunity-resultado-formulario
            v-if="!travado"
            :estado="estado"
            :estado-label="estadoLabel"
            :estrutura="estrutura"
            :respostas="respostas"
            :respostas-automaticas="respostasAutomaticas"
            :questoes-sem-fonte="questoesSemFonte"
            :erros="errosFormulario"
            :salvando="isSaving"
            :salvo-em="salvoEm"
            @salvar="salvarRespostas">
        </opportunity-resultado-formulario>
    </div>
</div>
