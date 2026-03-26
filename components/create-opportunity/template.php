<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    entity-terms
    mc-federative-entity-par
    mc-link
    mc-modal
    select-entity
');
?>
<mc-modal v-if="canAccess" :title="modalTitle" classes="create-modal create-opportunity-modal" button-label="<?php i::_e('Criar Oportunidade')?>" @open="createEntity()" @close="destroyEntity()">
    <template v-if="entity && !entity.id" #default="modal">
        <label class="create-modal__subtitle"><?php i::_e('Crie uma oportunidade para a Política Nacional Aldir Blanc') ?></label>
        <p v-if="!parOptionalOnCreate" class="create-modal__par-intro"><?php i::_e('Para iniciar, selecione dentro de qual meta, ação e atividade do PAR o seu instrumento será cadastrado.') ?></p>
        <form
            @submit.prevent="handleSubmit"
            class="create-modal__fields"
            :class="parOptionalOnCreate ? 'create-modal__fields--single-col' : 'create-modal__fields--two-cols'"
        >
            <div v-if="!parOptionalOnCreate" class="create-modal__col create-modal__col--par">
                <div class="create-modal__par create-modal__fields--par">
                    <mc-federative-entity-par
                        ref="parPar"
                        load-par-exercicios
                        v-model="parModel"
                        :empty-hint="text('parEmpty')"
                    ></mc-federative-entity-par>
                </div>
            </div>
            <div class="create-modal__col create-modal__col--form">
                <entity-field :entity="entity" label=<?php i::esc_attr_e("Título") ?> prop="name"></entity-field>
                <entity-field :entity="entity" prop="tipoDeEdital"></entity-field>
                <entity-terms :entity="entity" hide-required :editable="true" title="<?php i::_e('Área de Interesse') ?>" taxonomy="area"></entity-terms>
                <entity-field :entity="entity" prop="shortDescription"></entity-field>
                <small v-if="hasObjectTypeErrors()" class="field__error">{{getObjectTypeErrors().join('; ')}}</small>
                <entity-field :entity="entity" hide-required v-for="field in fields" :prop="field"></entity-field>
            </div>
        </form>
    </template>

    <template #button="modal">
        <slot :modal="modal"></slot>
    </template>

    <template #actions="modal">
        <button class="button button--primary button--icon " @click="createDraft(modal)"><?php i::_e('Criar') ?></button>
        <button class="button button--text button--text-del" @click="modal.close(); destroyEntity()"><?php i::_e('Cancelar') ?></button>
    </template>
</mc-modal>

<!-- Modal separado: "Oportunidade Criada!" (aberto após salvar) -->
<mc-modal
    v-if="showSuccessModal && createdEntity"
    ref="successModal"
    :title="text?.oportunidadeCriada || 'Oportunidade Criada!'"
    classes="create-modal create-opportunity-modal create-opportunity-success-modal"
    @close="onCloseSuccessModal()"
>
    <template #button>
        <!-- Sem botão: abre apenas via ref após o save -->
    </template>
    <template #default>
        <label><?php i::_e('Você pode completar as informações da sua oportunidade agora ou pode deixar para depois.'); ?></label>
        <br><br>
        <label><?php i::_e('Para completar e publicar sua oportunidade, acesse a área <b>Rascunhos</b> em <b>Minhas Oportunidades</b> no <b>Painel de Controle</b>.'); ?></label>
    </template>
    <template #actions="modal">
        <mc-link :entity="createdEntity" class="button button--primary-outline button--icon"><?php i::_e('Ver Oportunidade'); ?></mc-link>
        <button class="button button--secondarylight button--icon" @click="modal.close(); onCloseSuccessModal()"><?php i::_e('Completar Depois') ?></button>
        <mc-link :entity="createdEntity" route="edit" class="button button--primary button--icon"><?php i::_e('Completar Informações') ?></mc-link>
    </template>
</mc-modal>