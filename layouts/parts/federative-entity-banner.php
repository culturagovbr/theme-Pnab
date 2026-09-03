<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use AldirBlanc\Services\FederativeEntityService;

if (!isset($_SESSION['selectedFederativeEntity'])) {
    return;
}

$selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);

if (!$selectedEntity || !isset($selectedEntity['name'])) {
    return;
}

// A sessão guarda só id, name e document, e nunca é revalidada — o estado do PAR vem da entidade.
$selectedEntity['hasParData'] = FederativeEntityService::hasParData((int) ($selectedEntity['id'] ?? 0));

$this->jsObject['selectedFederativeEntity'] = $selectedEntity;

$this->import('
    federative-entity-banner
');
?>

<federative-entity-banner></federative-entity-banner>