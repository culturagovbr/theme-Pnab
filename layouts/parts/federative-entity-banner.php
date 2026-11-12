<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use AldirBlanc\Entities\FederativeEntity;
use AldirBlanc\Services\FederativeEntityService;
use AldirBlanc\Services\ParSituationService;

if (!isset($_SESSION['selectedFederativeEntity'])) {
    return;
}

$selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);

if (!$selectedEntity || !isset($selectedEntity['name'])) {
    return;
}

// A sessão guarda só id, name e document, e nunca é revalidada — o estado do PAR vem da entidade.
$selectedEntity['hasParData'] = FederativeEntityService::hasParData((int) ($selectedEntity['id'] ?? 0));
$entity = $app->repo(FederativeEntity::class)->find((int) ($selectedEntity['id'] ?? 0));
$selectedEntity['parReason'] = $entity ? (new ParSituationService())->storedReasonFor($entity) : null;

$this->jsObject['selectedFederativeEntity'] = $selectedEntity;
$this->jsObject['config']['cultBrSyncDegraded'] = !empty($_SESSION['gestor_cult_sync_degraded']);

$this->import('
    federative-entity-banner
');
?>

<federative-entity-banner></federative-entity-banner>