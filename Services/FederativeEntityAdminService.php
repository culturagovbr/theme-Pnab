<?php

namespace Pnab\Services;

use AldirBlanc\Entities\FederativeEntity;
use AldirBlanc\Entities\FederativeEntityAgentRelation;
use MapasCulturais\App;
use MapasCulturais\Entities\Agent;
use MapasCulturais\Entities\AgentRelation;

class FederativeEntityAdminService
{
    public function __construct(private App $app)
    {
    }

    public function getViewData(): array
    {
        $entities = $this->app->repo(FederativeEntity::class)
            ->createQueryBuilder('entity')
            ->orderBy('LOWER(entity.name)', 'ASC')
            ->addOrderBy('entity.id', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'entities' => array_map([$this, 'getListEntityData'], $entities),
        ];
    }

    public function find(int $id): ?FederativeEntity
    {
        return $this->app->repo(FederativeEntity::class)->find($id);
    }

    public function getAgentFederativeEntitiesData(Agent $agent): array
    {
        $relations = $this->app->repo(FederativeEntityAgentRelation::class)->findBy([
            'agent' => $agent,
            'status' => AgentRelation::STATUS_ENABLED,
        ]);

        $entities = [];
        foreach ($relations as $relation) {
            $entity = $relation->owner;
            if ($entity instanceof FederativeEntity) {
                $entities[(int) $entity->id] = [
                    'id' => (int) $entity->id,
                    'name' => (string) $entity->name,
                    'document' => $this->formatCnpj($entity->document),
                    'singleUrl' => $this->app->createUrl('panel', 'federativeEntitySingle', [$entity->id]),
                ];
            }
        }

        usort($entities, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return array_values($entities);
    }

    public function getRequestedEntityData(FederativeEntity $entity): array
    {
        return [
            '@entityType' => 'agent',
            'id' => (int) $entity->id,
            'name' => (string) $entity->name,
            'shortDescription' => '',
            'longDescription' => '',
            'cnpj' => $this->formatCnpj($entity->document),
            'status' => 1,
            'type' => [
                'id' => 2,
                'name' => 'Ente Federado',
            ],
            'files' => [],
            'terms' => [],
            'seals' => [],
            'children' => $this->getAssociatedAgentIds($entity),
            'relatedAgents' => [],
            'agentRelations' => [],
            'currentUserPermissions' => [
                'viewPrivateData' => true,
            ],
            'publicLocation' => false,
            'singleUrl' => $this->app->createUrl('panel', 'federativeEntitySingle', [$entity->id]),
            'editUrl' => '#',
        ];
    }

    private function getAssociatedAgentIds(FederativeEntity $entity): array
    {
        $agentIds = [];
        foreach ($this->getEnabledRelations($entity) as $relation) {
            $agent = $relation->agent;
            if ($agent && $agent->status === Agent::STATUS_ENABLED) {
                $agentIds[] = (int) $agent->id;
            }
        }

        sort($agentIds);

        return array_map(
            fn($id) => ['id' => (int) $id],
            array_values(array_unique($agentIds))
        );
    }

    private function getListEntityData(FederativeEntity $entity): array
    {
        return [
            'id' => (int) $entity->id,
            'name' => (string) $entity->name,
            'document' => (string) $entity->document,
            'exercices' => $entity->exercices,
            'update_timestamp' => $this->formatDateTime($entity->updateTimestamp),
            'managers_count' => count($this->getEnabledRelations($entity)),
        ];
    }

    private function getEnabledRelations(FederativeEntity $entity): array
    {
        return $this->app->repo(FederativeEntityAgentRelation::class)->findBy([
            'owner' => $entity,
            'status' => AgentRelation::STATUS_ENABLED,
        ]);
    }

    private function formatDateTime(?\DateTimeInterface $date): ?string
    {
        return $date?->format('Y-m-d H:i:s');
    }

    private function formatCnpj(?string $document): string
    {
        $numbers = preg_replace('/[^0-9]/', '', (string) $document);
        if (strlen($numbers) !== 14) {
            return (string) $document;
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $numbers);
    }
}
