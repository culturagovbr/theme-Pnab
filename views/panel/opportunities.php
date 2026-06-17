<?php
use MapasCulturais\i;
use AldirBlanc\Services\UserAccessService;

$isGestorCultBr = UserAccessService::isGestorCultBr();

$this->import('
    mc-icon
    panel--entity-card
    panel--entity-tabs
');

if (!$isGestorCultBr) {
    $this->import('
        create-opportunity
    ');
}
?>

<div class="panel-page">
    <header class="panel-page__header">
        <div class="panel-page__header-title">
            <div class="title">
                <div class="title__icon opportunity__background">
                    <mc-icon name="opportunity"></mc-icon>
                </div>
                <h1 class="title__title"> <?= i::_e('Minhas oportunidades') ?> </h1>
            </div>
        </div>
        <p class="panel-page__header-subtitle">
            <?= i::_e('Nesta seção você pode adicionar e gerenciar suas oportunidades') ?>
        </p>
        <?php if (!$isGestorCultBr): ?>
        <div class="panel-page__header-actions">
            <create-opportunity :editable="true" #default="{modal}">
                <button @click="modal.open()" class="button button--primary button--icon">
                    <mc-icon name="add"></mc-icon>
                    <span><?= i::__('Criar Oportunidade') ?></span>
                </button>
            </create-opportunity>
            <opportunity-importer></opportunity-importer>
        </div>
        <?php endif; ?>
    </header>

    <panel--entity-tabs type="opportunity"></panel--entity-tabs>
</div>
