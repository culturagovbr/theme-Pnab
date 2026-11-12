<?php

use AldirBlanc\Entities\FederativeEntity;
use AldirBlanc\Services\FederativeEntityService;
use AldirBlanc\Services\ParSituationService;
use AldirBlanc\Services\UserAccessService;
use MapasCulturais\App;

$selectedEntityId = FederativeEntityService::getSelectedFederativeEntityIdFromSession();
$selectedEntity = $selectedEntityId ? App::i()->repo(FederativeEntity::class)->find($selectedEntityId) : null;

$this->jsObject['config']['panelEntityTabsPnab'] = [
    'isGestorCultBr' => UserAccessService::isGestorCultBr(),
    'parActionOptions' => UserAccessService::isGestorCultBr()
        ? FederativeEntityService::getParActionNamesForSessionSelectedEntity()
        : [],
    'parReason' => $selectedEntity ? (new ParSituationService())->storedReasonFor($selectedEntity) : null,
];
