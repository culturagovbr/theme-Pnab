<?php

/**
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\Entities\Opportunity;
use AldirBlanc\Services\UserAccessService;

$this->useOpportunityAPI();

$this->jsObject['config']['canAccess'] = UserAccessService::canAccess();
$this->jsObject['config']['createOpportunity'] = [
    'date' => Opportunity::CONTINUOUS_FLOW_DATE,
];
