<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>

<?php
$banner_left_url = $app->view->asset('img/home/home-register/banner-left.png', false);
?>

<?php
$banner_right_url = $app->view->asset('img/home/home-register/banner-right.png', false);
?>

<div class="home-register">
   
    <div class="home-register__background">
        <div class="home-register__background--mask"></div>
    </div>
    <img src="<?= $banner_left_url ?>" alt="Adorno esquerdo" class="decoration banner-left" />
    <div class="home-register__content">
        <label class="home-register__content--title"><?= $this->text('title', i::__('Faça parte do <br/> PNAB')) ?></label>
        <p class="home-register__content--description"><?= $this->text('description', i::__('Cadastre seu portfólio, divulgue suas ações, acompanhe a agenda do seu território, conecte-se com outros agentes e fortaleça a Rede das Artes do Brasil.')); ?>
        </p>
        <a href="<?= $app->createUrl('autenticacao', 'register') ?>" class="button button--primary button--large button--icon">
            <?= i::__('Faça seu cadastro')?>
        </a>
    </div>
    <img src="<?= $banner_right_url ?>" alt="Adorno direito" class="decoration banner-right" />
</div>