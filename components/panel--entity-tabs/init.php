<?php

use AldirBlanc\Services\FederativeEntityService;
use AldirBlanc\Services\UserAccessService;

$this->jsObject['config']['panelEntityTabsPnab'] = [
    'isGestorCultBr' => UserAccessService::isGestorCultBr(),
    'parActionOptions' => UserAccessService::isGestorCultBr()
        ? FederativeEntityService::getParActionNamesForSessionSelectedEntity()
        : [],
];
