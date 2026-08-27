<?php
use MapasCulturais\i;
$this->import('
    mc-icon
    opportunities-sync-list
');
?>

<div class="panel-page">
    <header class="panel-page__header">
        <div class="panel-page__header-title">
            <div class="title">
                <div class="title__icon opportunity__background">
                    <mc-icon name="sync"></mc-icon>
                </div>
                <h1 class="title__title"> <?= i::_e('Sincronização') ?> </h1>
            </div>
        </div>
        <p class="panel-page__header-subtitle">
            <?= i::_e('Nesta seção você pode reenviar oportunidades publicadas ao CultBR') ?>
        </p>
    </header>

    <?php $encode = fn(array $filters) => json_encode($filters, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
    <opportunities-sync-list
        :syncable-filters='<?= $encode($syncableFilters) ?>'
        :listing-filters='<?= $encode($listingFilters) ?>'></opportunities-sync-list>
</div>
