<?php
/**
 * Modal "Título do edital" para usar modelo oficial.
 * Versão Pnab: sem opção de vincular o edital a uma entidade (Projeto, Evento, Espaço, Agente).
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import("
    mc-modal
");
?>
<div class="opportunity-create-based-model">
    <teleport to="body">
        <div
            v-if="generating"
            class="opportunity-create-based-model__blocking-overlay"
            role="alert"
            aria-live="polite"
            aria-busy="true"
            @click.prevent
            @mousedown.prevent
            @touchstart.prevent
        >
            <div class="opportunity-create-based-model__blocking-panel">
                <div class="opportunity-create-based-model__spinner" aria-hidden="true"></div>
                <p class="opportunity-create-based-model__blocking-text">
                    {{ text('Estamos gerando a oportunidade a partir do modelo…') }}
                </p>
            </div>
        </div>
    </teleport>
    <mc-modal classes="create-modal create-opportunity-modal" title="<?= i::__('Título do edital') ?>" @open="createEntity()">
        <template #default>
            <div class="create-modal__fields">
                <div class="field">
                    <label><?= i::__('Defina um título para o Edital que deseja criar') ?><span class="required">*</span></label>
                    <input type="text" v-model="formData.name">
                </div>
            </div>
        </template>

        <template v-if="!sendSuccess" #actions="modal">
            <button class="button button--text button--text-del" :disabled="generating" @click="modal.close()"><?= i::__('cancelar') ?></button>
            <button class="button button--primary" :disabled="generating" @click="save(modal)"><?= i::__('Começar') ?></button>
        </template>

        <template #button="modal">
            <button type="button" :disabled="generating" @click="modal.open();" class="button button--primary button--icon"><?= i::__('Usar modelo') ?></button>
        </template>
    </mc-modal>
</div>
