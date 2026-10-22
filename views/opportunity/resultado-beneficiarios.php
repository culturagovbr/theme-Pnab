<?php
/**
 * @var \MapasCulturais\Themes\Pnab\Theme $this
 * @var \MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->layout = 'entity';
$this->addOpportunityBreadcramb(i::__('Beneficiários do resultado'), $entity);
$this->import('
    entity-header
    mc-breadcrumb
    mc-link
    opportunity-header
    opportunity-beneficiarios-grade
')
?>
<div class="main-app opportunity-beneficiarios">
    <mc-breadcrumb></mc-breadcrumb>
    <opportunity-header :opportunity="entity.parent || entity">
        <template #button>
            <mc-link class="button button--primary-outline" :entity="entity.parent || entity" route="edit" hash="resultado-cultbr" icon="arrow-left"><?= i::__('Voltar') ?></mc-link>
        </template>
    </opportunity-header>

    <div class="opportunity-beneficiarios__container">
        <opportunity-beneficiarios-grade :entity="entity"></opportunity-beneficiarios-grade>
    </div>
</div>
