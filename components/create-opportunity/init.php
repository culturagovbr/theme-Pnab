<?php

use MapasCulturais\Entities\Opportunity;
use MapasCulturais\App;

$this->useOpportunityAPI();

$app = App::i();

$canAccess = $app->user->is('GestorCultBr') ||
    $app->user->is('saasSuperAdmin') ||
    $app->user->is('superAdmin') ||
    $app->user->is('saasAdmin');

$this->jsObject['config']['canAccess'] = $canAccess;

$this->jsObject['config']['createOpportunity'] = [
    'date' => Opportunity::CONTINUOUS_FLOW_DATE,
];