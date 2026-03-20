<?php
/**
 * Tema Pnab: config do componente agent-data-1 (campos exibidos na single do agente individual).
 * Base: modules/Entities/components/agent-data-1/init.php
 */

$sensitive_fields = [
    'comunidadesTradicional',
    'comunidadesTradicionalOutros',
    'dataDeNascimento',
    'escolaridade',
    'genero',
    'orientacaoSexual',
    'pessoaDeficiente',
    'raca',
];
$fields = [
    'nomeCompleto',
    'nomeSocial',
    'cpf',
    'telefonePublico',
    'emailPrivado',
];

$app->applyHook('component(agent-data).fields', [&$fields, &$sensitive_fields]);

$this->jsObject['config']['agent-data-1']['fields'] = $fields;
$this->jsObject['config']['agent-data-1']['sensitiveFields'] = $sensitive_fields;
