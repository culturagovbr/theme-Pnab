<?php

/**
 * Exercícios PAR do ente federativo selecionado na sessão (gestor CultBR).
 *
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use AldirBlanc\Services\FederativeEntityService;

$this->jsObject['config']['mcFederativeEntityPar'] = $this->jsObject['config']['mcFederativeEntityPar'] ?? [];
$this->jsObject['config']['mcFederativeEntityPar']['exercicios'] =
    FederativeEntityService::getParExerciciosForSessionSelectedEntity();
