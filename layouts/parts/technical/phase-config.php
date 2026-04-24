<?php

/**
 * Pnab: mesma configuração técnica do módulo, sem «Políticas Afirmativas».
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import("
    entity-field
    mc-icon
    technical-assessment-section
    tiebreaker-criteria-configuration
");
?>
<section class="col-12 evaluation-step__section">
    <div class="evaluation-step__section-header">
        <div class="evaluation-step__section-label">
            <h3><?= i::__('Configuração da avaliação') ?></h3>
            <?php $this->info('editais-oportunidades -> avaliacao-tecnica -> configuracao-avaliacao') ?>
        </div>
    </div>

    <div class="evaluation-step__section-content">
        <technical-assessment-section :entity="phase"></technical-assessment-section>
        <entity-field :entity="phase" prop="enableViability" :autosave="3000">
            <template #info>
                <?php $this->info('editais-oportunidades -> fases -> exequibilidade') ?>
            </template>
        </entity-field>
        <tiebreaker-criteria-configuration :phase="phase"></tiebreaker-criteria-configuration>
    </div>
</section>