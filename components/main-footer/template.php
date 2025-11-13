<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

use MapasCulturais\i;

$this->import('theme-logo');
$config = $app->config['social-media'];

$image_url_footer = $app->view->asset('img/logo-footer.png', false);

$entities = [
    'opportunities' => [
        'searchLabel' => 'Oportunidades',
        'panelLabel'  => 'Minhas oportunidades',
        'icon'        => 'opportunity'
    ],
    'agents' => [
        'searchLabel' => 'Agentes',
        'panelLabel'  => 'Meus agentes',
        'icon'        => 'agent'
    ],
];
?>

<?php $this->applyTemplateHook("main-footer", "before") ?>
<div v-if="globalState.visibleFooter" class="main-footer">
    <?php $this->applyTemplateHook("main-footer", "begin") ?>
    <div class="main-footer__content">
        <?php $this->applyTemplateHook("main-footer-logo", "before") ?>
        <div class="main-footer__support">
            <?php $this->part('footer-support-message') ?>
        </div>

        <div class="main-footer__content--logo-group">
            <div class="main-footer__logo-item"><img src="<?= $image_url_footer ?>" alt="Logo PNAB" /></div>
        </div>
        <?php $this->applyTemplateHook("main-footer-logo", "after") ?>

        <?php $this->applyTemplateHook("main-footer-links", "before") ?>
        <div class="main-footer__links-wrapper">
            <div class="main-footer__content--links">

                <ul class="main-footer__content--links-group">
                    <li><a><?php i::_e("Descubra"); ?></a></li>
                    <?php foreach ($entities as $key => $entity): ?>
                        <li v-if="global.enabledEntities.<?= $key ?>">
                            <a href="<?= $app->createUrl('search', $key) ?>">
                                <?php i::_e($entity['searchLabel']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <ul class="main-footer__content--links-group">
                    <li>
                        <a href="<?= $app->createUrl('panel', 'index') ?>"><?php i::_e('Painel de controle'); ?></a>
                    </li>
                    <?php
                        // TODO: AJUSTAR A CHAVE PROJECTS PARA A DE CIRCUITO)
                        $order = ['events', 'opportunities', 'agents', 'spaces', 'projects'];
                        foreach ($order as $key):
                            if (!isset($entities[$key])) continue;
                            $entity = $entities[$key];
                        ?>
                            <li v-if="global.enabledEntities.<?= $key ?>">
                                <a href="<?= $app->createUrl('panel', $key) ?>"><?php i::_e($entity['panelLabel']) ?></a>
                            </li>
                    <?php endforeach; ?>
                    <?php if (!($app->user->is('guest'))) : ?>
                        <li>
                            <a href="<?= $app->createUrl('auth', 'logout') ?>"><?php i::_e('Sair') ?></a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="main-footer__content--links-group">
                    <li><a><?php i::_e('Ajuda e privacidade'); ?></a></li>
                    <li><a href="<?= $app->createUrl('faq') ?>"><?php i::_e('Dúvidas frequentes'); ?></a></li>
                    <li>
                        <a href="https://github.com/redeMapas/mapas" target="_blank"><?php i::_e('Conheça o repositório'); ?></a>
                    </li>
                    <li>
                        <a href="https://manual.rededasartes.Pnab.gov.br/ " target="_blank"><?php i::_e('Acesse os manuais'); ?></a>
                    </li>

                    <?php if (!empty($app->config['module.LGPD'])): ?>
                        <?php foreach ($app->config['module.LGPD'] as $slug => $cfg): ?>
                            <li>
                                <a href="<?= $app->createUrl('lgpd', 'view', [$slug]) ?>"><?= $cfg['title'] ?></a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="main-footer__content--logo-share">
                        <?php foreach ($config as $conf): ?>
                            <a target="_blank" href="<?= $conf['link'] ?>">
                                <mc-icon style="font-size: 25px;" name="<?= $conf['title'] ?>"></mc-icon>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </ul>
            </div>
        </div>
        <?php $this->applyTemplateHook("main-footer-links", "after") ?>
    </div>

    <?php $this->applyTemplateHook("main-footer-reg", "before") ?>
    <?php $this->applyTemplateHook("main-footer-reg", "after") ?>
    <?php $this->applyTemplateHook("main-footer", "end") ?>

    <div class="main-footer__beta-alert">
        <p>
            <strong><?php i::_e('Versão Beta') ?></strong>
            <?php i::_e('Você está em uma versão de teste da plataforma. Se encontrar qualquer divergência ou tiver dúvidas, entre em contato com o suporte.') ?>
            <br/>
            <?php i::_e('Desenvolvido por Laboratório do Futuro da Universidade Federal do Ceará.') ?>
        </p>
    </div>
</div>
<?php $this->applyTemplateHook("main-footer", "after") ?>
