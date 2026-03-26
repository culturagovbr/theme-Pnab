<?php
/**
 * @var \Pnab\Theme $this
 */

use MapasCulturais\i;

$this->import('
    home-search
');

$parallax_url = $this->asset('img/home/home-header/parallax.png', false);
$brand_logo_url = $this->asset('img/home/home-header/home-header-logo.png', false);
?>

<div :class="['home-header', {'home-header--withBanner' : banner}] ">
    <div class="home-header__content">

        <div class="home-header__brand">
            <?php if ($brand_logo_url) : ?>
                <img
                    class="home-header__brand-logo"
                    src="<?= htmlspecialchars($brand_logo_url, ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($this->text('brandLogoAlt', i::__('Cult.BR — identidade visual')), ENT_QUOTES, 'UTF-8') ?>"
                    decoding="async"
                />
            <?php endif; ?>
            <p class="home-header__brand-tagline">
                <span class="home-header__brand-tagline-line"><?= $this->text('brandTaglineLine1', i::__('Fomento à cultura')) ?></span><br aria-hidden="true" />
                <span class="home-header__brand-tagline-line"><?= $this->text('brandTaglineLine2', i::__('em cada canto do país')) ?></span>
            </p>
        </div>

        <div class="home-header__main">
            <label class="home-header__title">
                <?= $this->text('title', i::__('Boas vindas ao Mapa Cultural')) ?>
            </label>
            <p class="home-header__description">
                <?= $this->text('description', i::__('O Mapas Culturais é uma ferramenta de gestão cultural, que garante a estruturação de Sistemas de Informações e Indicadores. A plataforma oferece soluções para o mapeamento colaborativo de agentes culturais, realização de todas as etapas de editais e fomentos, organização de uma agenda cultural e divulgação espaços culturais dos territórios.')) ?>
            </p>
        </div>

        <div v-if="banner || secondBanner" class="home-header__banners">
            <div v-if="banner" class="home-header__banner">
                <a v-if="bannerLink" :href="bannerLink" :download="downloadableLink ? '' : undefined"  :target="!downloadableLink ? '_blank' : null">
                    <img :src="banner" />
                </a>
                <img v-if="!bannerLink" :src="banner" />
            </div>

            <div v-if="secondBanner" class="home-header__banner">
                <a v-if="secondBannerLink" :href="secondBannerLink" :download="secondDownloadableLink ? '' : undefined"  :target="!secondDownloadableLink ? '_blank' : null">
                    <img :src="secondBanner" />
                </a>
                <img v-if="!secondBannerLink" :src="secondBanner" />
            </div>

            <div v-if="thirdBanner" class="home-header__banner">
                <a v-if="thirdBannerLink" :href="thirdBannerLink" :download="thirdDownloadableLink ? '' : undefined"  :target="!thirdDownloadableLink ? '_blank' : null">
                    <img :src="thirdBanner" />
                </a>
                <img v-if="!thirdBannerLink" :src="thirdBanner" />
            </div>
        </div>

    </div>
    <div class="home-header__background">
        <div class="img">
            <img :src="background" />
        </div>
    </div>
    <?php if ($parallax_url) : ?>
        <div class="home-header__parallax" aria-hidden="true">
            <img src="<?= htmlspecialchars($parallax_url, ENT_QUOTES, 'UTF-8') ?>" alt="" width="1742" height="2497" decoding="async" />
        </div>
    <?php endif; ?>
    <!-- <home-search></home-search> -->
</div>
