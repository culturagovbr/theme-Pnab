<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

if (!isset($_SESSION['selectedFederativeEntity'])) {
    return;
}

$selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);

if (!$selectedEntity || !isset($selectedEntity['name'])) {
    return;
}

$this->jsObject['selectedFederativeEntity'] = $selectedEntity;

$this->import('
    federative-entity-banner
');
?>

<federative-entity-banner></federative-entity-banner>