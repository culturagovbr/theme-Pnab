<?php
use MapasCulturais\i;
$this->import('
    federative-entities-list
    mc-icon
');

$entities = $entities ?? [];

$esc = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$formatCnpj = function (?string $document): string {
    $numbers = preg_replace('/[^0-9]/', '', (string) $document);
    if (strlen($numbers) !== 14) {
        return (string) $document;
    }

    return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $numbers);
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
