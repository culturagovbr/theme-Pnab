<?php

/**
 * Conteúdo da aba "Logs CultBr": envios ao CultBR desta oportunidade, no estilo "GitHub Actions".
 * Três níveis: envio (uuid) → tentativa (N/3) → payload e resposta.
 *
 * A <mc-tab> fica no part (layouts/parts/opportunity-cultbr-logs-tab.php), como nas demais abas
 * do core: assim este componente só monta quando a aba é aberta, evitando a busca em todo
 * carregamento da tela de edição.
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    mc-alert
    mc-accordion
    mc-card
    mc-icon
    mc-loading
');
?>

<!-- Faixa de leitura das demais abas de oportunidade: o .container do mc-container é um grid
     main/aside (estreita o conteúdo) e o .opportunity-container do core só recebe largura na
     action-single. -->
<div class="cultbr-logs">
    <mc-loading :condition="isLoading"></mc-loading>

    <template v-if="!isLoading">
        <mc-alert v-if="hasError" type="danger" role="alert">{{ translateMessage('erro_carregar') }}</mc-alert>

        <mc-alert v-else-if="!logs.length" type="helper">{{ translateMessage('lista_vazia') }}</mc-alert>

        <template v-else>
            <!-- Um card por envio: sem isso os runs se misturam dentro de um único bloco. -->
            <mc-card v-for="log in logs" :key="log.requestUuid" class="cultbr-logs__run-card">
                <template #content>
                    <mc-accordion class="cultbr-logs__run">
                        <template #title>
                            <span class="cultbr-logs__run-title">
                                <mc-icon
                                    class="cultbr-logs__status"
                                    :class="'cultbr-logs__status--' + log.status"
                                    :name="statusIcon(log.status)"
                                    :title="statusLabel(log.status)"
                                    :aria-label="statusLabel(log.status)"
                                ></mc-icon>
                                <code class="cultbr-logs__uuid">{{ log.requestUuid }}</code>
                                <small class="cultbr-logs__date">{{ formatDate(log.createdAt) }}</small>
                                <small v-if="log.user?.id" class="cultbr-logs__author">{{ authorLabel(log) }}</small>
                            </span>
                        </template>

                        <template #content>
                            <p v-if="!log.attempts?.length" class="cultbr-logs__empty-attempts">
                                {{ translateMessage('sem_tentativa') }}
                            </p>

                            <mc-accordion
                                v-for="attempt in log.attempts"
                                :key="log.requestUuid + '-' + attempt.attempt"
                                class="cultbr-logs__attempt"
                            >
                                <template #title>
                                    <span class="cultbr-logs__attempt-title">
                                        <mc-icon
                                            class="cultbr-logs__status"
                                            :class="'cultbr-logs__status--' + attempt.status"
                                            :name="statusIcon(attempt.status)"
                                            :title="statusLabel(attempt.status)"
                                            :aria-label="statusLabel(attempt.status)"
                                        ></mc-icon>
                                        {{ attemptLabel(attempt) }}
                                        <small v-if="attempt.httpStatus">{{ translateMessage('http', {status: attempt.httpStatus}) }}</small>
                                        <small v-if="attempt.durationMs !== null">{{ translateMessage('duracao', {ms: attempt.durationMs}) }}</small>
                                    </span>
                                </template>

                                <template #content>
                                    <dl class="cultbr-logs__meta">
                                        <dt>{{ translateMessage('enviado_em') }}</dt>
                                        <dd>{{ formatDate(attempt.sentAt) }}</dd>
                                        <dt>{{ translateMessage('endpoint') }}</dt>
                                        <dd><code>{{ attempt.httpMethod }} {{ attempt.endpoint }}</code></dd>
                                    </dl>

                                    <mc-alert v-if="attempt.errorMessage" type="danger">
                                        {{ attempt.errorMessage }}
                                    </mc-alert>

                                    <mc-accordion class="cultbr-logs__payload">
                                        <template #title>{{ translateMessage('payload') }}</template>
                                        <template #content>
                                            <div class="cultbr-logs__code">
                                                <button
                                                    class="cultbr-logs__copy"
                                                    type="button"
                                                    @click="copyToClipboard(formatJson(attempt.payload))"
                                                    :aria-label="translateMessage('copiar')"
                                                >
                                                    <mc-icon name="copy"></mc-icon>
                                                    <span>{{ translateMessage('copiar') }}</span>
                                                </button>
                                                <pre
                                                    class="cultbr-logs__json"
                                                    tabindex="0"
                                                    :aria-label="translateMessage('payload')"
                                                >{{ formatJson(attempt.payload) }}</pre>
                                            </div>
                                        </template>
                                    </mc-accordion>

                                    <mc-accordion class="cultbr-logs__response">
                                        <template #title>{{ translateMessage('resposta') }}</template>
                                        <template #content>
                                            <!-- Cabeçalhos primeiro: quando o corpo não é JSON (ou vem
                                                 vazio), é neles que está a pista do que o servidor devolveu. -->
                                            <div v-if="attempt.responseHeaders?.length" class="cultbr-logs__code">
                                                <button
                                                    class="cultbr-logs__copy"
                                                    type="button"
                                                    @click="copyToClipboard(attempt.responseHeaders.join('\n'))"
                                                    :aria-label="translateMessage('copiar')"
                                                >
                                                    <mc-icon name="copy"></mc-icon>
                                                    <span>{{ translateMessage('copiar') }}</span>
                                                </button>
                                                <pre
                                                    class="cultbr-logs__json"
                                                    tabindex="0"
                                                    :aria-label="translateMessage('cabecalhos')"
                                                >{{ attempt.responseHeaders.join('\n') }}</pre>
                                            </div>

                                            <div v-if="hasResponseBody(attempt)" class="cultbr-logs__code">
                                                <button
                                                    class="cultbr-logs__copy"
                                                    type="button"
                                                    @click="copyToClipboard(formatJson(attempt.response))"
                                                    :aria-label="translateMessage('copiar')"
                                                >
                                                    <mc-icon name="copy"></mc-icon>
                                                    <span>{{ translateMessage('copiar') }}</span>
                                                </button>
                                                <pre
                                                    class="cultbr-logs__json"
                                                    tabindex="0"
                                                    :aria-label="translateMessage('resposta')"
                                                >{{ formatJson(attempt.response) }}</pre>
                                            </div>

                                            <p v-if="!hasResponseBody(attempt) && !attempt.responseHeaders?.length">
                                                {{ translateMessage('sem_resposta') }}
                                            </p>
                                        </template>
                                    </mc-accordion>
                                </template>
                            </mc-accordion>
                        </template>
                    </mc-accordion>
                </template>
            </mc-card>

            <button
                v-if="hasMore"
                class="button button--secondary cultbr-logs__more"
                type="button"
                @click="loadMore()"
                :disabled="isLoadingMore"
                :aria-busy="isLoadingMore"
            >{{ translateMessage('carregar_mais') }}</button>
        </template>
    </template>
</div>
