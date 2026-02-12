<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-terms
    mc-link
    mc-avatar
    mc-popover 
    select-entity
    mc-icon
');
?>

<div class="link-opportunity">
    <entity-terms :entity="entity" :editable="true" title="<?php i::_e('Área de Interesse') ?>" taxonomy="area"></entity-terms>
    <label class="link-opportunity__title bold"><?php i::_e('Entidade Vinculada') ?><br></label>
    <div class="link-opportunity__ownerEntity">
        <div class="link-opportunity__header" :class="[entity.ownerEntity.__objectType+'__border', entity.ownerEntity.__objectType+'__color']">
            <mc-avatar :entity="entity.ownerEntity" size="xsmall"></mc-avatar>
            {{entity.ownerEntity.name}}
        </div>
    </div>
</div>