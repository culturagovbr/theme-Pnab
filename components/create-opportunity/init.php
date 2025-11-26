<?php

use MapasCulturais\Entities\Opportunity;

$this->useOpportunityAPI();

$this->jsObject['config']['canAccess'] = \Pnab\Theme::canAccess();
$this->jsObject['config']['createOpportunity'] = [
    'date' => Opportunity::CONTINUOUS_FLOW_DATE,
];