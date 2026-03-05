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
        'title' => '',
        'route' => 'search/opportunities',
        'viewAll' => 'Acesse aqui',
        'link' => 'https://cultbr.cultura.gov.br/transparencia',
        'description' => 'Aqui você encontra atividades e chamadas com inscrições abertas, como editais, intercâmbios, residências artísticas e outras oportunidades disponíveis. Você também pode criar e divulgar novas oportunidades para outros agentes da Rede.',
    ],
    'signup' => [
        'image' => 'img/cards/cadastro_bg.png',
        'title' => '',
        'route' => 'autenticacao',
        'viewAll' => 'Fazer Cadastro',
        'description' => 'O <strong>Cult.br Editais</strong> é uma plataforma gratuita. Gestores culturais divulgam editais da <strong>Política Nacional Aldir Blanc</strong> e agentes culturais submetem suas propostas. Aqui você pode realizar a inscrição, seleção, monitoramento e prestação de informações sobre projetos. Acesse e faça parte da <strong>maior política pública de fomento cultural da história!</strong>',
    ],
];
?>

<div class="home-entities">
  <div class="home-entities__content">
    <label class="home-entities__content--title"><?= $this->text('title', i::__('Encontre as informações sobre a PNAB')) ?></label>
    <div class="home-entities__content--cards">
      <?php foreach ($entities as $key => $entity): ?>
        <?php $imageUrl = $app->view->asset($entity['image'], false); ?>
        <div v-if="<?= $key === 'signup' ? 'true' : "global.enabledEntities.$key" ?>" class="card">
          <div class="card__image" style="background-image: url('<?= $imageUrl ?>');">
            <h3><?= i::__($entity['title']) ?></h3>
          </div>
          <div class="card__body">
            <p><?= i::__($entity['description']) ?></p>
            <a href="<?= $entity['link'] ?? $app->createUrl($entity['route']) ?>" target="_blank">
              <?= i::__($entity['viewAll']) ?>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
