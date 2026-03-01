<?php

$agent_types = include APPLICATION_PATH . '/conf/agent-types.php';

// Remove obrigatoriedade de campos específicos na criação de agente.
foreach (['raca', 'renda'] as $field_key) {
    if (isset($agent_types['metadata'][$field_key]['validations']['required'])) {
        unset($agent_types['metadata'][$field_key]['validations']['required']);
    }
}

// Detalhamento do tipo de agente coletivo (agente continua type=2 Coletivo).
$agent_types['metadata']['tipoAgenteColetivo'] = [
    'label' => \MapasCulturais\i::__('Tipo de agente coletivo'),
    'type' => 'select',
    'options' => [
        'pj_fins_lucrativos' => \MapasCulturais\i::__('Pessoa jurídica com fins lucrativos'),
        'pj_sem_fins_lucrativos' => \MapasCulturais\i::__('Pessoa jurídica sem fins lucrativos'),
        'coletivos_grupos_informais' => \MapasCulturais\i::__('Coletivos e grupos informais'),
    ],
    'validations' => [
        'required' => \MapasCulturais\i::__('O tipo de agente coletivo é obrigatório'),
    ],
];

return $agent_types;
