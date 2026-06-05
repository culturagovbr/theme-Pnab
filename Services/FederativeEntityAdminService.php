<?php

namespace Pnab\Services;

use AldirBlanc\Entities\FederativeEntity;
use MapasCulturais\App;

class FederativeEntityAdminService
{
    private const OBJECT_TYPE = 'AldirBlanc\\Entities\\FederativeEntity';

    public function __construct(private App $app)
    {
    }

    public function getViewData(): array
    {
        $conn = $this->app->em->getConnection();

        $sql = "
            SELECT
                fe.id,
                fe.name,
                fe.document,
                fe.exercices,
                fe.update_timestamp,
                COUNT(DISTINCT ar.agent_id) AS managers_count
            FROM federative_entity fe
            LEFT JOIN agent_relation ar ON ar.object_id = fe.id
                AND ar.object_type = :objectType
            GROUP BY fe.id, fe.name, fe.document, fe.exercices, fe.update_timestamp
            ORDER BY LOWER(fe.name) ASC, fe.id ASC
        ";

        $result = $conn->executeQuery($sql, [
            'objectType' => self::OBJECT_TYPE,
        ]);

        return [
            'entities' => $this->fetchAll($result),
        ];
    }

    public function find(int $id): ?FederativeEntity
    {
        return $this->app->repo(FederativeEntity::class)->find($id);
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

    private function fetchAll($result): array
    {
        if (method_exists($result, 'fetchAllAssociative')) {
            return $result->fetchAllAssociative();
        }

        return $result->fetchAll();
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
