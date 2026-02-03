<?php

use MapasCulturais\i;

$app = \MapasCulturais\App::i();

// Obtém o ID da entidade federativa selecionada na sessão
$federativeEntityId = null;
if (isset($_SESSION['selectedFederativeEntity'])) {
    $selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);
    if ($selectedEntity && isset($selectedEntity['id'])) {
        $federativeEntityId = (int)$selectedEntity['id'];
    }
}

$this->import('
    search-list
');
?>

<?php if ($federativeEntityId): ?>
    <div class="panel-page">
        <div class="panel-page__header">
            <h1 class="panel-page__title">
                <?= htmlspecialchars(i::__('Minha Equipe')) ?>
            </h1>
        </div>
        <div class="panel-page__content">
            <div class="search__tabs--list">
                <search-list 
                    :pseudo-query="{federativeEntityId: <?= $federativeEntityId ?>}" 
                    type="agent" 
                    select="name,type,shortDescription,files.avatar,seals,terms">
                </search-list>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="panel-page">
        <div class="alert alert-warning">
            <?= i::_e('Nenhuma entidade federativa selecionada. Por favor, selecione uma entidade federativa.') ?>
        </div>
    </div>
<?php endif; ?>
