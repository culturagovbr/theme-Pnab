<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    entity-terms
    mc-link
    mc-loading
    mc-modal
    select-entity
');
?>
<mc-modal v-if="canAccess" :title="modalTitle" classes="create-modal create-opportunity-modal" button-label="<?php i::_e('Criar Oportunidade')?>" @open="createEntity()" @close="destroyEntity()">
    <template v-if="entity && !entity.id" #default="modal">
        <label class="create-modal__subtitle"><?php i::_e('Crie uma oportunidade para a Política Nacional Aldir Blanc') ?></label>
        <p class="create-modal__par-intro"><?php i::_e('Para iniciar, selecione dentro de qual meta, ação e atividade do PAR o seu instrumento será cadastrado.') ?></p>
        <form @submit.prevent="handleSubmit" class="create-modal__fields create-modal__fields--two-cols">
            <div class="create-modal__col create-modal__col--par">
                <div class="create-modal__par create-modal__fields--par">
                    <div v-if="parLoading" class="create-modal__par-loading">
                        <mc-loading :condition="true"><?php i::_e('Carregando...') ?></mc-loading>
                    </div>
                    <template v-else>
                        <p v-if="parExercicios.length === 0" class="create-modal__par-empty"><?php i::_e('Nenhum exercício disponível para o ente federado selecionado.') ?></p>
                        <template v-else>
                            <div class="field" :class="{ error: parErrors.exercicio }">
                                <label class="field__title"><?php i::_e('Exercício') ?> <span class="required">*<?php i::_e('obrigatório') ?></span></label>
                                <div class="field__input">
                                    <select v-model="parExercicioId" required @change="onParExercicioChange">
                                        <option value=""><?php i::_e('Selecionar') ?></option>
                                        <option v-for="ex in parExercicios" :key="ex.id" :value="ex.id">{{ ex.ano }}</option>
                                    </select>
                                </div>
                                <small v-if="parErrors.exercicio" class="field__error">{{ parErrorMsg('exercicio') }}</small>
                            </div>
                            <div class="field" :class="{ error: parErrors.meta }">
                                <label class="field__title"><?php i::_e('Meta') ?> <span class="required">*<?php i::_e('obrigatório') ?></span></label>
                                <div class="field__input">
                                    <select v-model="parMetaId" required :disabled="!parExercicioId" @change="onParMetaChange">
                                        <option value=""><?php i::_e('Selecionar') ?></option>
                                        <option v-for="m in parMetas" :key="m.id" :value="m.id">{{ m.nome }}</option>
                                    </select>
                                </div>
                                <small v-if="parErrors.meta" class="field__error">{{ parErrorMsg('meta') }}</small>
                            </div>
                            <div class="field" :class="{ error: parErrors.acao }">
                                <label class="field__title"><?php i::_e('Ação') ?> <span class="required">*<?php i::_e('obrigatório') ?></span></label>
                                <div class="field__input">
                                    <select v-model="parAcaoId" required :disabled="!parMetaId" @change="onParAcaoChange">
                                        <option value=""><?php i::_e('Selecionar') ?></option>
                                        <option v-for="a in parAcoes" :key="a.id" :value="a.id">{{ a.nome }}</option>
                                    </select>
                                </div>
                                <small v-if="parErrors.acao" class="field__error">{{ parErrorMsg('acao') }}</small>
                            </div>
                            <div class="field" :class="{ error: parErrors.atividade }">
                                <label class="field__title"><?php i::_e('Atividade') ?> <span class="required">*<?php i::_e('obrigatório') ?></span></label>
                                <div class="field__input">
                                    <select v-model="parAtividadeId" required :disabled="!parAcaoId">
                                        <option value=""><?php i::_e('Selecionar') ?></option>
                                        <option v-for="at in parAtividades" :key="at.id" :value="at.id">{{ at.nome }}</option>
                                    </select>
                                </div>
                                <small v-if="parErrors.atividade" class="field__error">{{ parErrorMsg('atividade') }}</small>
                            </div>
                        </template>
                    </template>
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