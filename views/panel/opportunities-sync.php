<?php
use MapasCulturais\i;
$this->import('mc-icon');
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
</div>
