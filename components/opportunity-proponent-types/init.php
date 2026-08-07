<?php

use AldirBlanc\Services\UserAccessService;

$this->jsObject['config']['opportunityProponentTypes'] = $app->config['registration.proponentTypesToAgentsMap'];
$this->jsObject['config']['opportunityProponentTypesCanConfigureAgentRelation'] = UserAccessService::isAdmin();