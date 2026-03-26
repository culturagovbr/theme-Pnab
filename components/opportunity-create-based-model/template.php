<?php
/**
 * Modal «Usar modelo»: PAR + título + descrição curta (integração).
 * Layout em duas colunas como «Criar oportunidade» (`create-modal__fields--two-cols` no pai).
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import("
    mc-federative-entity-par
    mc-modal
    mc-teleport-multiple
");

$this->useOpportunityAPI();
?>
<div class="opportunity-create-based-model">
    <mc-teleport-multiple
        to="body"
        :show="generating"
        :messages="generatingMessages"
    ></mc-teleport-multiple>
    <mc-modal classes="create-modal create-opportunity-modal" title="<?= i::esc_attr__('Configurações da nova oportunidade') ?>" @open="createEntity()">
        <template #default>
            <label class="create-modal__subtitle"><?php i::_e('Crie uma oportunidade para a Política Nacional Aldir Blanc') ?></label>
            <p v-if="!parOptionalOnCreate" class="create-modal__par-intro"><?php i::_e('Para iniciar, selecione dentro de qual meta, ação e atividade do PAR o seu instrumento será cadastrado.') ?></p>
            <form
                class="create-modal__fields"
                :class="parOptionalOnCreate ? 'create-modal__fields--single-col' : 'create-modal__fields--two-cols'"
                @submit.prevent
            >
                <div v-if="!parOptionalOnCreate" class="create-modal__col create-modal__col--par">
                    <div class="create-modal__par create-modal__fields--par">
                        <mc-federative-entity-par
                            ref="parInstrumentoRef"
                            load-par-exercicios
                            v-model="parSelectionModel"
                            :empty-hint="text('parEmpty')"
                        ></mc-federative-entity-par>
                    </div>
                </div>
                <div class="create-modal__col create-modal__col--form">
                    <div class="field">
                        <label class="field__title"><?= i::__('Título') ?><span class="required">*</span></label>
                        <div class="field__input">
                            <input type="text" v-model="formData.name" maxlength="255">
                        </div>
                    </div>
                    <div class="field">
                        <label class="field__title"><?= i::__('Descrição curta') ?><span class="required">*</span></label>
                        <div class="field__input">
                            <textarea
                                v-model="formData.shortDescription"
                                rows="4"
                                maxlength="400"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </form>
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
