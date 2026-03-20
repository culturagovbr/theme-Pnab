<?php
/**
 * Teleport genérico com painel de bloqueio: mensagens via props (uma ou várias em sequência).
 * Intervalo entre passos: prop message-step-ms (default 3500 ms).
 *
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
?>
<teleport :to="to" :disabled="disabled">
    <div
        v-if="show"
        class="mc-teleport-multiple__blocking-overlay"
        :class="{ 'mc-teleport-multiple__blocking-overlay--pass-through': !blockInteraction }"
        role="status"
        aria-live="polite"
        aria-busy="true"
        @click="handleOverlayPointer"
        @mousedown="handleOverlayPointer"
        @touchstart="handleOverlayPointer"
    >
        <div class="mc-teleport-multiple__blocking-panel">
            <div class="mc-teleport-multiple__spinner" aria-hidden="true"></div>
            <div
                class="mc-teleport-multiple__message-track"
                :style="messageTrackStyle"
            >
                <transition name="mc-teleport-multiple-fade" mode="out-in">
                    <p
                        v-if="displayedText"
                        :key="stepIndex"
                        class="mc-teleport-multiple__blocking-text"
                    >{{ displayedText }}</p>
                </transition>
            </div>
        </div>
    </div>
</teleport>
