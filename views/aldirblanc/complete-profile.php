<?php
/**
 * Complete seu cadastro (aldirblanc/completeProfile).
 * Informações de Apresentação (sem capa/perfil) + Dados Pessoais + Outros documentos + Dados sensíveis.
 */

use MapasCulturais\i;

$this->layout = 'entity';

$this->import('
    complete-profile-actions
    complete-profile-visibility
    elderly-person
    entity-field
    entity-header
    entity-location
    entity-status
    entity-terms
    mc-card
');
?>

<mc-entity #default="{ entity }">
<div class="main-app complete-profile-page">
    <div class="complete-profile-page__container">
        <h1 class="complete-profile-page__title">
            <?php i::_e('Complete seu cadastro') ?>
        </h1>
        <p class="complete-profile-page__description">
            <?php i::_e('Complete as informações abaixo para continuar o uso do sistema.') ?>
        </p>

        <div class="complete-profile-page__card">
            <entity-status :entity="entity"></entity-status>
            <main>
                <complete-profile-visibility :entity="entity" #default="{ isFieldVisible, isAddressVisible, showCardApresentacao, showCardPessoais, showCardSensiveis }">
                <mc-card v-if="showCardApresentacao">
                    <template #title>
                        <h3 class="bold"><?php i::_e("Informações de Apresentação"); ?></h3>
                        <p><?php i::_e("Os dados inseridos abaixo serão exibidos para todos os usuários") ?></p>
                    </template>
                    <template #content>
                        <div class="grid-12">
                            <entity-field v-if="isFieldVisible('name')" :entity="entity" classes="col-12" prop="name" label="<?php i::_e('Nome do Agente') ?>"></entity-field>
                        
                            <entity-terms v-if="isFieldVisible('terms.area')" :entity="entity" taxonomy="area" editable classes="col-12" title="<?php i::_e('Áreas de atuação'); ?>"></entity-terms>
                        
                            <entity-field v-if="isFieldVisible('shortDescription')" :entity="entity" classes="col-12" prop="shortDescription" :max-length="400" label="<?php i::_e('Mini bio') ?>">
                                <template #info>
                                    <?php $this->info('cadastro -> cadastrando-usuario -> mini-bio') ?>
                                </template>
                            </entity-field>
                        </div>
                    </template>
                </mc-card>
                <mc-card v-if="showCardPessoais">
                    <template #title>
                        <h3 class="bold"><?php i::_e("Dados Pessoais"); ?> <?php $this->info('cadastro -> configuracoes-entidades -> dados-pessoais') ?></h3>
                        <p><?php i::_e("Não se preocupe, esses dados não serão exibidos publicamente."); ?></p>
                    </template>
                    <template #content>
                        <div class="grid-12">
                            <entity-field v-if="isFieldVisible('nomeSocial')" :entity="entity" classes="col-12" prop="nomeSocial" label="<?= i::__('Nome artístico ou Nome Social') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('nomeCompleto')" :entity="entity" classes="col-12" prop="nomeCompleto" label="<?= i::__('Nome completo') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="global.auth.is('admin') && isFieldVisible('type')" :entity="entity" prop="type" @change="entity.save(true).then(() => global.reload())" classes="col-12"></entity-field>
                            <entity-field v-if="isFieldVisible('cpf')" :entity="entity" classes="col-12" prop="cpf">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('emailPrivado')" :entity="entity" classes="col-12" prop="emailPrivado" label="<?= i::__('E-mail') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('telefonePublico')" :entity="entity" classes="col-12" prop="telefonePublico" label="<?= i::__('Telefone') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('acessouFomentoCultural')" :entity="entity" classes="col-12" prop="acessouFomentoCultural">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('anosExperienciaAreaCultural')" :entity="entity" classes="col-12" prop="anosExperienciaAreaCultural">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('eMestreCulturasTradicionais')" :entity="entity" classes="col-12" prop="eMestreCulturasTradicionais">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <div class="col-12 divider" v-if="isAddressVisible"></div>
                            <entity-location v-if="isAddressVisible" :entity="entity" classes="col-12" editable :required="true"></entity-location>
                        </div>
                    </template>
                </mc-card>
                <mc-card v-if="showCardSensiveis">
                    <template #title>
                        <h3 class="bold"><?php i::_e("Dados pessoais sensíveis"); ?> <?php $this->info('cadastro -> configuracoes-entidades -> dados-pessoais-sensiveis') ?></h3>
                        <p class="data-subtitle"><?php i::_e("Os dados inseridos abaixo serão registrados apenas no sistemas e não serão exibidos publicamente"); ?></p>
                    </template>
                    <template #content>
                        <div class="grid-12">
                            <entity-field v-if="isFieldVisible('dataDeNascimento')" :entity="entity" classes="col-6 sm:col-12" prop="dataDeNascimento" label="<?= i::__('Data de Nascimento') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('genero')" :entity="entity" classes="col-6 sm:col-12" prop="genero" label="<?= i::__('Gênero') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('orientacaoSexual')" :entity="entity" classes="col-6 sm:col-12" prop="orientacaoSexual" label="<?= i::__('Orientação Sexual') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('raca')" :entity="entity" classes="col-6 sm:col-12" prop="raca" label="<?= i::__('Cor/raça/etnia') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('renda')" :entity="entity" classes="col-6 sm:col-12" prop="renda" label="<?= i::__('Renda média individual (R$)') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('escolaridade')" :entity="entity" classes="col-6 sm:col-12" prop="escolaridade" label="<?= i::__('Escolaridade') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('pessoaDeficiente')" :entity="entity" classes="col-12" prop="pessoaDeficiente" class="pcd col-12" label="<?= i::__('É pessoa com deficiência?') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                            <entity-field v-if="isFieldVisible('comunidadesTradicional')" :entity="entity" classes="col-12" prop="comunidadesTradicional" label="<?= i::__('Pertence a povos e comunidades tradicionais?') ?>">
                                <template #info><span class="required">*<?php i::_e('obrigatório') ?></span></template>
                            </entity-field>
                        </div>
                    </template>
                </mc-card>
                </complete-profile-visibility>
            </main>
        </div>
    </div>
    <complete-profile-actions :entity="entity"></complete-profile-actions>
</div>
</mc-entity>
