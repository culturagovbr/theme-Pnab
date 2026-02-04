<?php

$agent_types = include APPLICATION_PATH . '/conf/agent-types.php';

// Remove obrigatoriedade de campos específicos na criação de agente.
foreach (['raca', 'renda'] as $field_key) {
    if (isset($agent_types['metadata'][$field_key]['validations']['required'])) {
        unset($agent_types['metadata'][$field_key]['validations']['required']);
    }
}

return $agent_types;
