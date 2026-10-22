<?php

/**
 * Aba "Envio de Resultado ao CultBr": injetada por hook; o wrapper decide se aparece.
 * `entity` vem do escopo Vue da view de edição de oportunidade.
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

$this->import('
    mc-tabs
    mc-tab
    mc-container
    opportunity-resultado-cultbr-card
    opportunity-resultado-beneficiarios
    opportunity-resultado-cultbr-tab
');
?>

<opportunity-resultado-cultbr-tab :entity="entity"></opportunity-resultado-cultbr-tab>
