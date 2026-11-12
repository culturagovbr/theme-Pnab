<?php
use MapasCulturais\i;
$this->import('
    federative-entities-list
    mc-icon
');

$entities = $entities ?? [];

$esc = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$formatCnpj = function (?string $document): string {
    $characters = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $document));
    if (!preg_match('/^[A-Z0-9]{12}\d{2}$/', $characters)) {
        return (string) $document;
    }

    return preg_replace('/^(\w{2})(\w{3})(\w{3})(\w{4})(\d{2})$/', '$1.$2.$3/$4-$5', $characters);
};

$formatDate = function ($date): string {
    if (!$date) {
        return '';
    }

    try {
        return (new DateTime((string) $date))->format('d/m/Y H:i');
    } catch (Throwable) {
        return '';
    }
};

$viewEntities = array_map(function (array $entity) use ($app, $formatCnpj, $formatDate) {
    $id = (int) ($entity['id'] ?? 0);

    return [
        'id' => $id,
        'name' => (string) ($entity['name'] ?? ''),
        'document' => $formatCnpj($entity['document'] ?? ''),
        'managersCount' => (int) ($entity['managers_count'] ?? 0),
        'hasParData' => (bool) ($entity['has_par_data'] ?? false),
        'updatedAt' => $formatDate($entity['update_timestamp'] ?? null),
        'updatedAtOrder' => $entity['update_timestamp'] ? strtotime((string) $entity['update_timestamp']) : 0,
        'singleUrl' => $app->createUrl('panel', 'federativeEntitySingle', [$id]),
    ];
}, $entities);
?>

<div class="panel-page">
    <header class="panel-page__header">
        <div class="panel-page__header-title">
            <div class="title">
                <div class="title__icon agent__background">
                    <mc-icon name="agent"></mc-icon>
                </div>
                <h1 class="title__title"> <?= i::_e('Entes Federados') ?> </h1>
            </div>
        </div>
        <p class="panel-page__header-subtitle">
            <?= i::_e('Nesta seção você pode gerenciar os Entes Federados') ?>
        </p>
    </header>

    <federative-entities-list :entities='<?= json_encode($viewEntities, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>'></federative-entities-list>
</div>
