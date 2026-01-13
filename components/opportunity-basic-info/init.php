<?php
use Pnab\Enum\OtherValues;

$this->useOpportunityAPI();
$entity = $this->controller->requestedEntity;

$this->jsObject['config']['opportunityBasicInfo'] = [
    'date' => $entity::CONTINUOUS_FLOW_DATE,
];

$this->jsObject['config']['opportunityOtherOptions'] = [
    'etapa' => OtherValues::OUTRA_ETAPA,
    'pauta' => OtherValues::OUTRA_PAUTA,
];
