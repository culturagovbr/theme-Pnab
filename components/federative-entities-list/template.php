<?php
use MapasCulturais\i;

$this->import('
    mc-tab
    mc-tabs
    panel--entity-card
');
?>

<mc-tabs class="entity-tabs models" sync-hash>
    <mc-tab label="<?= i::esc_attr__('Entes') ?>" slug="entities">
        <template v-if="cardEntities.length">
            <panel--entity-card v-for="entity in cardEntities" :key="entity.id" :entity="entity">
                <template #subtitle="{ entity }">
                    {{ entity.document }}
                </template>

                <template #default="{ entity }">
                    <dl>
                        <dt><?= i::__('Gestores associados') ?></dt>
                        <dd>{{ entity.managersCount }}</dd>
                    </dl>
                    <dl>
                        <dt><?= i::__('Atualizado em') ?></dt>
                        <dd>{{ entity.updatedAt }}</dd>
                    </dl>
                </template>

                <template #entity-actions-left></template>
                <template #entity-actions-center></template>
                <template #entity-actions-right></template>
            </panel--entity-card>
        </template>

        <div v-else class="panel__row">
            <?= i::__('Nenhum ente federado encontrado.') ?>
        </div>
    </mc-tab>
</mc-tabs>
