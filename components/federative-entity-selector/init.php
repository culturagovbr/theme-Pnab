<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use AldirBlanc\Entities\FederativeEntityAgentRelation;

$federativeEntities = [];

if ($app->user && $app->user->profile) {
    $relations = $app->em->getRepository(FederativeEntityAgentRelation::class)->findBy([
        'agent' => $app->user->profile
    ]);

    foreach ($relations as $relation) {
        if ($relation->owner) {
            $federativeEntities[] = [
                'id' => $relation->owner->id,
                'name' => $relation->owner->name,
                'document' => $relation->owner->document
            ];
        }
    }
}

$this->jsObject['aldirBlancConfig'] = [
    'federativeEntities' => $federativeEntities
];
