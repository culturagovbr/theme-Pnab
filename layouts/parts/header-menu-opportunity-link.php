<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('mc-icon');
?>
<li v-if="global.enabledEntities.opportunities">
    <a href="https://cultbr.cultura.gov.br/transparencia" class="mc-header-menu--item project">
        <span class="icon"> <mc-icon name="project"></mc-icon> </span>
        <p class="label"> <?php i::_e('Portal CultBR') ?> </p>
    </a>
</li>
<li v-if="global.enabledEntities.agents">
    <a href="https://cultbr.cultura.gov.br/entrar" class="mc-header-menu--item agent">
        <span class="icon"> <mc-icon name="agent-2"></mc-icon> </span>
        <p class="label"> <?php i::_e('Rede CultBR') ?> </p>
    </a>
</li>
