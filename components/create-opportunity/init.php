<?php

use MapasCulturais\Entities\Opportunity;

$this->useOpportunityAPI();

$this->jsObject['config']['canAccess'] = \AldirBlanc\Services\UserAccessService::canAccess();
$this->jsObject['config']['createOpportunity'] = [
    'date' => Opportunity::CONTINUOUS_FLOW_DATE,
];