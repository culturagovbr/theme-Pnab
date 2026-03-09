<?php

$agent_types = include APPLICATION_PATH . '/conf/agent-types.php';

// Remove obrigatoriedade de campos específicos na criação de agente.
foreach (['raca', 'renda'] as $field_key) {
    if (isset($agent_types['metadata'][$field_key]['validations']['required'])) {
        unset($agent_types['metadata'][$field_key]['validations']['required']);
    }
}

// Remove o fallback que mostra user->email quando emailPrivado está vazio no banco (conf/agent-types.php).
// Assim $entity->emailPrivado fica vazio quando não há registro em agent_meta.
if (isset($agent_types['metadata']['emailPrivado']['unserialize'])) {
    unset($agent_types['metadata']['emailPrivado']['unserialize']);
}

return $agent_types;
