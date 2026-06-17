<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    mc-avatar
');
?>

<?php $this->applyTemplateHook('entity-list','before'); ?>
<div v-if="items.length > 0" class="entity-list">
    <?php $this->applyTemplateHook('entity-list','begin'); ?>
    <label class="col-12 entity-list__title"> {{title}} </label>
    <ul class="entity-list__list">
        <li v-for="entity in items" :key="entity.id" class="col-12 entity-list__list-item">
            <a class="entity-list__list-item-link" :href="entity.singleUrl">
                <div class="entity-list__list-item-img">
                    <mc-avatar :entity="entity" size="xsmall"></mc-avatar>
                </div>
                <div class="entity-list__list-item"> {{showContent(entity.name)}} </div>
            </a>
        </li>
    </ul>
    <?php $this->applyTemplateHook('entity-list','end'); ?>
</div>
<?php $this->applyTemplateHook('entity-list','after'); ?>
