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
');
?>
<div v-if="visivel" class="mc-stepper-vertical-wrapper resultado-cultbr-card" :class="{'resultado-cultbr-card--travado': travado}">
    <mc-loading :condition="isLoading"></mc-loading>

    <template v-if="!isLoading && hasError">
        <mc-alert type="danger">
            <p>{{ translateMessage('erro_carregar') }}</p>
            <button class="button button--sm button--text" @click="load">{{ translateMessage('tentar_novamente') }}</button>
        </mc-alert>
    </template>

    <div v-if="!isLoading && !hasError" class="resultado-cultbr-card__item">
        <span class="resultado-cultbr-card__marcador"><mc-icon name="exchange"></mc-icon></span>

        <section class="resultado-cultbr-card__box">
            <header class="resultado-cultbr-card__header">
                <div class="resultado-cultbr-card__info">
                    <h3 class="resultado-cultbr-card__titulo">{{ translateMessage('titulo') }}</h3>
                    <div class="resultado-cultbr-card__subtitulo">{{ translateMessage('subtitulo') }}</div>
                </div>

                <div class="resultado-cultbr-card__estado">
                    <span class="resultado-cultbr-card__chip" :class="'resultado-cultbr-card__chip--' + estado">{{ estadoLabel }}</span>

                    <a v-if="!travado" class="resultado-cultbr-card__expandir" @click="toggle">
                        <template v-if="!expanded">{{ translateMessage('expandir') }}</template>
                        <template v-if="expanded">{{ translateMessage('diminuir') }}</template>
                        <mc-icon :name="expanded ? 'arrowPoint-up' : 'arrowPoint-down'"></mc-icon>
                    </a>
                </div>
            </header>

            <main v-if="travado" class="resultado-cultbr-card__body">
                <p class="resultado-cultbr-card__nota">{{ translateMessage('travado_aviso') }}</p>
            </main>

            <main v-if="!travado && expanded" class="resultado-cultbr-card__body">
                <p class="resultado-cultbr-card__nota">{{ translateMessage('formulario_em_breve') }}</p>

                <div class="resultado-cultbr-card__acoes">
                    <button class="button button--primary button--md" :disabled="!envioHabilitado || isSending" @click="enviar">
                        {{ translateMessage('enviar') }}
                    </button>
                </div>
            </main>
        </section>
    </div>
</div>
