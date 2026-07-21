<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    confirm-before-exit
    custom-http-multiselect
    mc-federative-entity-par
    mc-alert
    mc-modal
    entity-admins
    entity-cover
    entity-field
    entity-file
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-links
    mc-currency-input
    entity-owner
    entity-profile
    entity-related-agents
    entity-seals
    entity-social-media
    entity-status
    entity-terms
    link-opportunity
    mc-container
    custom-mc-multiselect
    opportunity-recursos-outras-fontes
    opportunity-formas-inscricao-edital
    opportunity-outras-modalidades-acoes-afirmativas
');
?>
<div class="opportunity-basic-info__container">
    <entity-status v-if="!entity.isModel" :entity="entity"></entity-status>

    <mc-card v-if="isOfficialModelAdmin">
        <template #content>
            <div v-if="!parActionsLoading" class="opportunity-basic-info__official-model-alert">
                <mc-alert type="warning">
                    <strong><?php i::_e('Atenção') ?></strong><br>
                    <?php i::_e('Ao associar este modelo oficial a ações do PAR, ele será exibido apenas nas ações definidas.') ?>
                </mc-alert>
            </div>

            <custom-http-multiselect
                :entity="entity"
                prop="parActions"
                label="<?php i::esc_attr_e('Ações do PAR') ?>"
                endpoint="parAcoes"
                classes="col-12"
                @loading="parActionsLoading = $event"
            ></custom-http-multiselect>
        </template>
    </mc-card>

</div>

<mc-container>
    <main>
        <!-- Card 1: até o campo descrição longa -->
        <mc-card>
            <template #content>
                <div class="header-opp grid-12 v-bottom">
                    <entity-cover :entity="entity" classes=" header-opp__cover col-12"></entity-cover>
                    <div class="header-opp__profile col-3 sm:col-12">
                        <entity-profile :entity="entity"></entity-profile>
                    </div>
                    <div class="header-opp__field grid-12 col-9 sm:col-12">
                        <entity-field :entity="entity" prop="name"
                            classes="header-opp__field--name col-12"></entity-field>
                        <entity-field :entity="entity" prop="tipoDeEdital"
                            classes="header-opp__field--name col-12"></entity-field>
                    </div>
                    <entity-field :entity="entity" classes="header-opp__field--name col-12" prop="shortDescription"
                        :max-length="400"></entity-field>
                    <entity-field :entity="entity" classes="header-opp__field--name col-12"
                        prop="longDescription"></entity-field>
                </div>
            </template>
        </mc-card>

        <!-- Card Dados do PAR: instrumento do PAR isolado, com o status das ações do modelo -->
        <mc-card v-if="showParField">
            <template #title>
                <h3><?= i::__('Dados do PAR') ?></h3>
            </template>
            <template #content>
                <div class="grid-12">
                    <mc-alert v-if="hasParActions" type="warning" class="col-12">
                        <?php i::_e('Edital criado a partir de um modelo que está associado às seguintes ações do PAR:') ?>
                        <ul class="opportunity-basic-info__par-actions-list">
                            <li v-for="acao in parAcaoAllowedNames" :key="acao">{{ acao }}</li>
                        </ul>
                    </mc-alert>
                    <!-- Pede o preenchimento dos campos abaixo; não fala das ações herdadas do
                         modelo (parActions), que o usuário não resolve por aqui. -->
                    <mc-alert v-if="!hasPar" type="danger" class="col-12">
                        <?php i::_e('Os dados do PAR ainda não foram preenchidos. Selecione o exercício, a meta, a ação e a atividade abaixo.') ?>
                    </mc-alert>

                    <mc-federative-entity-par
                        class="header-opp__field header-opp__field--par-readonly grid-12 col-12"
                        :readonly="parReadonly"
                        :exercicios="parExercicios"
                        :allowed-acao-names="parAcaoSelectableNames"
                        :server-errors="entity.__validationErrors"
                        load-par-exercicios v-model="parModel"></mc-federative-entity-par>
                </div>
            </template>
        </mc-card>

        <!-- Card 2: Segmento artístico-cultural até Território -->
        <mc-card>
            <template #content>
                <div class="grid-12">
                    <div class="col-12 sm:col-12">
                        <custom-mc-multiselect :entity="entity" prop="segmento" outros-prop="segmentoOutros"
                            :not-applicable-label='<?= json_encode(i::__('Edital não se direciona a segmentos específicos')) ?>'>
                            <template #info>
                                <span class="required">*<?php i::_e('obrigatório') ?></span>
                            </template>
                        </custom-mc-multiselect>
                    </div>

                    <div class="col-12 sm:col-12">
                        <custom-mc-multiselect :entity="entity" prop="pauta" outros-prop="pautaOutros"
                            :show-all-options="false"
                            :not-applicable-label='<?= json_encode(i::__('Edital não se direciona a pautas específicas')) ?>'>
                            <template #info>
                                <span class="required">*<?php i::_e('obrigatório') ?></span>
                            </template>
                        </custom-mc-multiselect>
                    </div>

                    <div class="col-12 sm:col-12">
                        <custom-mc-multiselect :entity="entity" prop="etapa" outros-prop="etapaOutros"
                            :show-all-options="false"
                            :not-applicable-label='<?= json_encode(i::__('Edital não se direciona a etapa específica')) ?>'>
                            <template #info>
                                <span class="required">*<?php i::_e('obrigatório') ?></span>
                            </template>
                        </custom-mc-multiselect>
                    </div>

                    <div class="col-12 sm:col-12">
                        <custom-mc-multiselect :entity="entity" prop="territorio" :show-all-options="false"
                            :not-applicable-label='<?= json_encode(i::__('Edital não se direciona a territórios específicos')) ?>'>
                            <template #info>
                                <span class="required">*<?php i::_e('obrigatório') ?></span>
                            </template>
                        </custom-mc-multiselect>
                    </div>
                </div>
            </template>
        </mc-card>

        <!-- Card 3: a partir de Adicionar arquivos -->
        <mc-card>
            <template #content>
                <div class="grid-12">
                    <div class="col-12 divider"></div>

                    <opportunity-recursos-outras-fontes :entity="entity"
                        class="col-12"></opportunity-recursos-outras-fontes>

                    <div class="col-12 divider"></div>

                    <div class="opportunity-basic-info__formas-inscricao col-12">
                        <opportunity-formas-inscricao-edital :entity="entity"
                            class="col-12"></opportunity-formas-inscricao-edital>
                    </div>

                    <div class="col-12 divider"></div>

                    <div class="opportunity-basic-info__outras-modalidades col-12">
                        <opportunity-outras-modalidades-acoes-afirmativas :entity="entity"
                            class="col-12"></opportunity-outras-modalidades-acoes-afirmativas>
                    </div>

                    <div class="col-12 divider"></div>

                    <entity-files-list :entity="entity" classes="content-fileList col-12" group="downloads"
                        title="<?php i::esc_attr_e('Adicionar arquivos'); ?>" editable></entity-files-list>
                    <entity-links :entity="entity" classes="col-12" title="<?php i::esc_attr_e('Adicionar links'); ?>"
                        editable></entity-links>
                    <entity-gallery-video :entity="entity" classes="col-12" editable></entity-gallery-video>
                    <entity-gallery :entity="entity" classes="col-12" editable></entity-gallery>
                </div>
            </template>
        </mc-card>
    </main>
    <aside>
        <mc-card>
            <div class="grid-12">
                <link-opportunity :entity="entity" editable class="col-12"></link-opportunity>
                <entity-file :entity="entity" titleModal="<?php i::_e('Adicionar regulamento') ?>" groupName="rules"
                    classes="col-12" title="<?php i::esc_attr_e('Adicionar regulamento'); ?>" :required="!canManageOfficialModelParActions"
                    editable></entity-file>
                <entity-admins :entity="entity" classes="col-12" editable></entity-admins>
                <!-- <entity-related-agents :entity="entity" classes="col-12" title="<?php i::esc_attr_e('Agentes Relacionados'); ?>" editable></entity-related-agents> -->
                <entity-social-media :entity="entity" classes="col-12" editable></entity-social-media>
                <entity-seals :entity="entity" :editable="entity.currentUserPermissions?.createSealRelation"
                    classes="col-12" title="<?php i::esc_attr_e('Verificações'); ?>"></entity-seals>
                <entity-terms :entity="entity" classes="col-12" taxonomy="tag" title="<?php i::_e('Tags') ?>"
                    editable></entity-terms>
                <entity-owner :entity="entity" classes="col-12" title="Publicado por" editable></entity-owner>
            </div>
        </mc-card>
    </aside>
</mc-container>
<confirm-before-exit :entity="entity"></confirm-before-exit>

<!-- Aviso: oportunidade sem dados do PAR. Abre automaticamente (via ref) quando o usuário pode editar. -->
<mc-modal ref="parMissingModal" classes="opportunity-basic-info__par-missing-modal"
    title="<?php i::esc_attr_e('Dados do PAR ausentes') ?>">
    <template #default>
        <mc-alert type="warning">
            Esta oportunidade está sem os dados do PAR.<br>
            Preencha o <strong style="color: var(--mc-danger-500)">Exercício</strong>,
            a <strong style="color: var(--mc-danger-500)">Meta</strong>,
            a <strong style="color: var(--mc-danger-500)">Ação</strong> e
            a <strong style="color: var(--mc-danger-500)">Atividade</strong>
            no card "Dados do PAR" para regularizá-la.
        </mc-alert>
    </template>
    <template #actions="modal">
        <button class="button button--primary" @click="modal.close()"><?php i::_e('Entendi') ?></button>
    </template>
</mc-modal>
