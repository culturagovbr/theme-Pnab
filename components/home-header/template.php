<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
$banner_url = $app->view->asset('img/home/home-main-header/banner.png', false);
$logos_url = $app->view->asset('img/home/home-main-header/logos.png', false);
?>

<section class="hero-banner">
  <div class="hero-banner__content">
    <div class="hero-banner__text">
      <h2>
        A Cultura é o Nosso Futuro <br>
        Fomento Contínuo <br>
        Cultura Brasileira
      </h2>

      <p>
        Faça parte da PNAB!
      </p>

      <img src="<?= $logos_url ?>" alt="Logos em tons de branco" />


    </div>

    <div class="hero-banner__image">
      <img src="<?= $banner_url ?>" alt="Formas gráficas coloridas" />
    </div>
  </div>
</section>
