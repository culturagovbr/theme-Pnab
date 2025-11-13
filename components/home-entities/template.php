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
        'title' => 'Oportunidades',
        'route' => 'search/opportunities',
        'viewAll' => 'Ver todas',
        'description' => 'Aqui você encontra atividades e chamadas com inscrições abertas, como editais, intercâmbios, residências artísticas e outras oportunidades disponíveis. Você também pode criar e divulgar novas oportunidades para outros agentes da Rede.',
    ],
    'agents' => [
        'image' => 'img/cards/agentes_bg.png',
        'title' => 'Agentes',
        'route' => 'search/agents',
        'viewAll' => 'Ver todos',
        'description' => 'Aqui você conhece participantes da Pnab e pode se inscrever para fazer parte. Conheça e se apresente!',
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
            <mc-link route="<?= $entity['route'] ?>">
              <?= i::__($entity['viewAll']) ?>
            </mc-link>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
