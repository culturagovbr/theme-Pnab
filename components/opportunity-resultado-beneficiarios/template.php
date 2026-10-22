<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

use MapasCulturais\i;

$this->import('mc-card');
?>
<mc-card class="resultado-beneficiarios">
    <template #title>
        <h3 class="resultado-beneficiarios__titulo"><?= i::__('Lista de beneficiários') ?></h3>
    </template>
    <template #content>
        <p class="resultado-beneficiarios__nota">
            <?= i::__('Configure de onde vem cada coluna da planilha e abra a lista de beneficiários para revisar e completar os dados enviados ao CultBR.') ?>
        </p>
    </template>
</mc-card>
