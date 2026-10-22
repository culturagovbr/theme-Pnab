<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

use MapasCulturais\i;
?>
<mc-tab v-if="visivel" label="<?= i::__('Envio de Resultado ao CultBr') ?>" slug="resultado-cultbr">
    <mc-container>
        <main class="resultado-cultbr-tab__conteudo">
            <opportunity-resultado-cultbr-card :entity="entity" :preloaded="estadoInicial"></opportunity-resultado-cultbr-card>
        </main>
    </mc-container>
</mc-tab>
