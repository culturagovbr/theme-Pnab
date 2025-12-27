<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('mc-link');

$entities = [
    'opportunities' => [
        'image' => 'img/cards/oportunidades_bg.png',
        'title' => 'Portal Cult.br',
        'route' => 'search/opportunities',
        'viewAll' => 'Acesse aqui',
        'link' => 'https://cultbr.cultura.gov.br/transparencia',
        'description' => 'Aqui você encontra atividades e chamadas com inscrições abertas, como editais, intercâmbios, residências artísticas e outras oportunidades disponíveis. Você também pode criar e divulgar novas oportunidades para outros agentes da Rede.',
    ],
];
?>

<div class="home-entities">
  <div class="home-entities__content">
    <label class="home-entities__content--title"><?= $this->text('title', i::__('Encontre as informações sobre a PNAB')) ?></label>
    <div class="home-entities__content--cards">
      <?php foreach ($entities as $key => $entity): ?>
        <?php $imageUrl = $app->view->asset($entity['image'], false); ?>
        <div v-if="global.enabledEntities.<?= $key ?>" class="card">
          <div class="card__image" style="background-image: url('<?= $imageUrl ?>');">
            <h3><?= i::__($entity['title']) ?></h3>
          </div>
          <div class="card__body">
            <p><?= i::__($entity['description']) ?></p>
            <a href="<?= $entity['link'] ?>" target="_blank">
              <?= i::__($entity['viewAll']) ?>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
