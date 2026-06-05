<?php

use MapasCulturais\i;

$this->layout = 'federative-entity';

$this->import('
    agent-data-2
    country-address-view
    entity-actions
    entity-admins
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-header
    entity-links
    entity-list
    entity-location
    entity-seals
    entity-social-media
    entity-terms
    mc-breadcrumb
    mc-container
    mc-share-links
    mc-tab
    mc-tabs
');

$this->breadcrumb = [
    ['label' => i::__('Inicio'), 'url' => $app->createUrl('panel', 'index')],
    ['label' => i::__('Entes Federados'), 'url' => $app->createUrl('panel', 'federativeEntities')],
    ['label' => $entity->name, 'url' => $app->createUrl('panel', 'federativeEntitySingle', [$entity->id])],
];
?>

<div class="main-app federative-entity-single">
    <mc-breadcrumb></mc-breadcrumb>
    <entity-header :entity="entity"></entity-header>
    <mc-tabs class="tabs" sync-hash>
        <mc-tab icon="exclamation" label="<?= i::_e('Informações') ?>" slug="info">
            <div class="tabs__info">
                <mc-container>
                    <main>
                        <div class="grid-12">
                            <agent-data-2 :entity="entity"></agent-data-2>
                            <country-address-view v-if="entity.publicLocation" :entity="entity" class="col-12"></country-address-view>
                            <div v-if="entity.longDescription" class="col-12">
                                <h2><?php i::_e('Descrição Detalhada'); ?></h2>
                                <p class="description" v-html="entity.longDescription"></p>
                            </div>
                            <entity-files-list v-if="entity.files?.downloads!= null" :entity="entity" classes="col-12" group="downloads" title="<?php i::esc_attr_e('Arquivos para download'); ?>"></entity-files-list>
                            <entity-links :entity="entity" classes="col-12" title="<?php i::_e('Links'); ?>"></entity-links>
                            <entity-gallery-video :entity="entity" classes="col-12"></entity-gallery-video>
                            <entity-gallery :entity="entity" classes="col-12"></entity-gallery>
                        </div>
                    </main>
                    <aside>
                        <div class="grid-12">
                            <?php $this->applyTemplateHook('single2-entity-info-taxonomie-area','before') ?>
                            <entity-terms :entity="entity" hide-required classes="col-12" taxonomy="area" title="<?php i::esc_attr_e('Áreas de atuação'); ?>"></entity-terms>
                            <?php $this->applyTemplateHook('single2-entity-info-taxonomie-area','after') ?>

                            <?php $this->applyTemplateHook('single2-entity-info-social-media','before') ?>
                            <entity-social-media :entity="entity" classes="col-12"></entity-social-media>
                            <?php $this->applyTemplateHook('single2-entity-info-social-media','after') ?>

                            <?php $this->applyTemplateHook('single2-entity-info-entity-seals','before') ?>
                            <entity-seals :entity="entity" :editable="entity.currentUserPermissions?.createSealRelation" classes="col-12" title="<?php i::esc_attr_e('Verificações'); ?>"></entity-seals>
                            <?php $this->applyTemplateHook('single2-entity-info-entity-seals','before') ?>

                            <?php $this->applyTemplateHook('single2-entity-info-entity-terms-tag','before') ?>
                            <entity-terms :entity="entity" hide-required classes="col-12" taxonomy="tag" title="<?php i::esc_attr_e('Tags') ?>"></entity-terms>
                            <?php $this->applyTemplateHook('single2-entity-info-entity-terms-tag','after') ?>

                            <?php $this->applyTemplateHook('single2-entity-info-mc-share-links','before') ?>
                            <mc-share-links classes="col-12" title="<?php i::esc_attr_e('Compartilhar'); ?>" text="<?php i::esc_attr_e('Veja este link:'); ?>"></mc-share-links>
                            <?php $this->applyTemplateHook('single2-entity-info-mc-share-links','before') ?>

                            <?php $this->applyTemplateHook('single2-entity-info-entity-admins','before') ?>
                            <entity-admins :entity="entity" classes="col-12"></entity-admins>
                            <?php $this->applyTemplateHook('single2-entity-info-entity-admins','after') ?>
                        </div>
                    </aside>
                </mc-container>
            </div>
        </mc-tab>
    </mc-tabs>
    <entity-actions :entity="entity"></entity-actions>
</div>
