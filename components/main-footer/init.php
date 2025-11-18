<?php

use MapasCulturais\App;

$app = App::i();

$canAccess = $app->user->is('GestorCultBr') ||
    $app->user->is('saasSuperAdmin') ||
    $app->user->is('superAdmin') ||
    $app->user->is('saasAdmin');

$this->jsObject['config']['canAccess'] = $canAccess;