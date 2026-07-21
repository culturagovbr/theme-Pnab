<?php

/**
 * Aba "Logs CultBr" — injetada por hook em template(opportunity.edit.tabs):end (Theme::_init),
 * só para admin. `entity` vem do escopo Vue da view de edição de oportunidade.
 *
 * A <mc-tab> vive aqui (e não no componente) para o conteúdo só montar quando a aba é aberta.
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-tab
    opportunity-cultbr-logs
');
?>

<mc-tab label="<?= i::__('Logs CultBr') ?>" slug="logs-cultbr">
    <opportunity-cultbr-logs :entity="entity"></opportunity-cultbr-logs>
</mc-tab>
