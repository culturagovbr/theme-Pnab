<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>
<li v-if="global.enabledEntities.opportunities">
    <a href="https://cultbr.cultura.gov.br/transparencia" class="mc-header-menu--item opportunity">
        <img src="<?= $app->view->asset('img/home/header/prancheta-2.jpg', false) ?>" alt="Ícone" class="menu-icon" width="24" height="24" />  
        <p class="label"> <?php i::_e('Portal CultBR') ?> </p>
    </a>
</li>
<li v-if="global.enabledEntities.agents">
    <a href="https://cultbr.cultura.gov.br/entrar" class="mc-header-menu--item agent">
        <img src="<?= $app->view->asset('img/home/header/prancheta-2.jpg', false) ?>" alt="Ícone" class="menu-icon" width="24" height="24" />  
        <p class="label"> <?php i::_e('Rede CultBR') ?> </p>
    </a>
</li>
