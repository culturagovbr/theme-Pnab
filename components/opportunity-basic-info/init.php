<?php

use Pnab\Enum\OtherValues;
use Pnab\Theme;
use AldirBlanc\Services\UserAccessService;
use AldirBlanc\Services\FederativeEntityService;

$this->useOpportunityAPI();
$entity = $this->controller->requestedEntity;

// Exercícios do PAR do ente da própria oportunidade (independe do ente da sessão):
// necessário para o admin, que não tem ente selecionado, resolver os rótulos no readonly.
$federativeEntityId = (int) ($entity->federativeEntityId ?? 0);

$this->jsObject['config']['opportunityBasicInfo'] = [
    'date' => $entity::CONTINUOUS_FLOW_DATE,
    'canManageOfficialModelParActions' => UserAccessService::canAssociatePARAction(),
    // Admin (ou permissão maior) vê o instrumento PAR sempre readonly; os demais
    // (GestorCultBr) podem editar quando a oportunidade está sem os dados do PAR.
    'userIsAdmin' => UserAccessService::isAdmin(),
    'parExercicios' => $federativeEntityId > 0
        ? FederativeEntityService::getParExerciciosForFederativeEntityId($federativeEntityId)
        : [],
];

$this->jsObject['config']['opportunityOtherOptions'] = [
    'etapa' => OtherValues::OUTRA_ETAPA,
    'pauta' => OtherValues::OUTRA_PAUTA,
];

// Opções com sublista: replica Theme::OPTIONS_OTHER_MODALITIES_WITH_SUBLIST + labelKey p/ i18n
$this->jsObject['config']['opportunityOutrasModalidades'] = [
    'opcoesComSublista' => array_map(function ($key) {
        $labelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
        return ['key' => $key, 'labelKey' => $labelKey];
    }, Theme::OPTIONS_OTHER_MODALITIES_WITH_SUBLIST),
];
