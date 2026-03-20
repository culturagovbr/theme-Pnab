<?php

use Pnab\Enum\OtherValues;
use Pnab\Theme;

$this->useOpportunityAPI();
$entity = $this->controller->requestedEntity;

$this->jsObject['config']['opportunityBasicInfo'] = [
    'date' => $entity::CONTINUOUS_FLOW_DATE,
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
