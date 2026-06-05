<?php

namespace Pnab\Services;

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

    private function fetchAll($result): array
    {
        if (method_exists($result, 'fetchAllAssociative')) {
            return $result->fetchAllAssociative();
        }

        return $result->fetchAll();
    }
}
