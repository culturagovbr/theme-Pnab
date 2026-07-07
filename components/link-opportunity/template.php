<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-terms
    mc-link
    mc-popover 
    select-entity
    mc-icon
');
?>

<div class="link-opportunity">
    <entity-terms :entity="entity" :editable="true" title="<?php i::_e('Área de Interesse') ?>" taxonomy="area"></entity-terms>
</div>