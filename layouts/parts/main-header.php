<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-header-menu
    mc-header-menu-user
    mc-icon
    mc-messages 
    theme-logo 
');
?>
<?php $this->applyTemplateHook('main-header', 'before') ?>
<header class="main-header" id="main-header">
    <?php $this->applyTemplateHook('main-header', 'begin') ?>

    <div class="main-header__content">

        <?php $this->applyTemplateHook('mc-header-menu', 'before') ?>
        <mc-header-menu>

            <!-- Logo -->
            <template #logo>
                <theme-logo href="<?= $app->createUrl('panel', 'index') ?>"></theme-logo>
            </template>
            <!-- Menu principal -->
            <template #default>
                <?php $this->applyTemplateHook('mc-header-menu', 'begin') ?>
                <!-- TODO: DESIGN PENDENTE -> Redirecionar para a nova tela de agenda -->
                <?php $this->applyTemplateHook('mc-header-menu-events', 'before') ?>
                <li v-if="global.enabledEntities.events">
                    <?php $this->applyTemplateHook('mc-header-menu-events', 'begin') ?>
                    <a href="<?= $app->createUrl('search', 'events') ?>" class="mc-header-menu--item event">
                        <p class="label"> <?php i::_e('Agenda') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-events', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-events', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'before') ?>
                <li v-if="global.enabledEntities.opportunities">
                    <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'begin') ?>
                    <a href="<?= $app->createUrl('search', 'opportunities') ?>" class="mc-header-menu--item opportunity">
                        <p class="label"> <?php i::_e('Oportunidades') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu-agent', 'before') ?>
                <li v-if="global.enabledEntities.agents">
                    <?php $this->applyTemplateHook('mc-header-menu-agent', 'begin') ?>
                    <a href="<?= $app->createUrl('search', 'agents') ?>" class="mc-header-menu--item agent">
                        <p class="label"> <?php i::_e('Agentes') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-agent', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-agent', 'after') ?>
                <?php $this->applyTemplateHook('mc-header-menu-spaces', 'before') ?>
                <li v-if="global.enabledEntities.spaces">
                    <?php $this->applyTemplateHook('mc-header-menu-spaces', 'begin') ?>
                    <a href="<?= $app->createUrl('search', 'spaces') ?>" class="mc-header-menu--item space">
                        <p class="label"> <?php i::_e('Espaços') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-spaces', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-projects', 'after') ?>
                <?php $this->applyTemplateHook('mc-header-menu-projects', 'before') ?>
                <li v-if="global.enabledEntities.projects">
                    <?php $this->applyTemplateHook('mc-header-menu-projects', 'begin') ?>
                    <a href="<?= $app->createUrl('circuitos') ?>" class="mc-header-menu--item project">
                        <p class="label"> <?php i::_e('Circuitos') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-projects', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-projects', 'after') ?>
                
                <?php $this->applyTemplateHook('mc-header-menu', 'end') ?>
            </template>

        </mc-header-menu>
        <?php $this->applyTemplateHook('mc-header-menu', 'after') ?>

        <div class="main-header__buttons">
            <?php $this->applyTemplateHook('mc-header-menu-user', 'before') ?>
            <?php if ($app->user->is('guest')): ?>
                <!-- Botão login -->
                <a href="<?= $app->createUrl('auth') ?>?redirectTo=<?=$_SERVER['REQUEST_URI']?>" class="logIn">
                    <?php i::_e('Entrar') ?>
                </a>
            <?php else: ?>
                <!-- Menu do usuário -->
                <mc-header-menu-user></mc-header-menu-user>
            <?php endif; ?>
            <?php $this->applyTemplateHook('mc-header-menu-user', 'after') ?>
        </div>

    </div>

    <?php $this->applyTemplateHook('main-header', 'end') ?>
</header>
<?php $this->applyTemplateHook('main-header', 'after') ?>

<mc-messages></mc-messages>