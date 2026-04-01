<?php

$agent_types = include APPLICATION_PATH . '/conf/agent-types.php';

// Remove obrigatoriedade de campos específicos na criação de agente (core pode marcar como required).
foreach (['renda'] as $field_key) {
    if (isset($agent_types['metadata'][$field_key]['validations']['required'])) {
        unset($agent_types['metadata'][$field_key]['validations']['required']);
    }
}

// Detalhamento do tipo de agente coletivo (agente continua type=2 Coletivo).
$agent_types['metadata']['tipoAgenteColetivo'] = [
    'label' => \MapasCulturais\i::__('Tipo de Agente Coletivo'),
    'type' => 'select',
    'options' => [
        'pj_fins_lucrativos' => \MapasCulturais\i::__('Pessoa jurídica com fins lucrativos'),
        'pj_sem_fins_lucrativos' => \MapasCulturais\i::__('Pessoa jurídica sem fins lucrativos'),
        'coletivos_grupos_informais' => \MapasCulturais\i::__('Coletivos e grupos informais'),
    ],
];

// Remove o fallback que mostra user->email quando emailPrivado está vazio no banco (conf/agent-types.php).
// Assim $entity->emailPrivado fica vazio quando não há registro em agent_meta.
if (isset($agent_types['metadata']['emailPrivado']['unserialize'])) {
    unset($agent_types['metadata']['emailPrivado']['unserialize']);
}

// Opção presente no core; no Pnab não deve aparecer no select.
unset($agent_types['metadata']['orientacaoSexual']['options']['Assexual']);

// "Não informado" (chave vazia) no core; no Pnab o select não deve incluir essa opção.
unset($agent_types['metadata']['raca']['options']['']);

return $agent_types;
