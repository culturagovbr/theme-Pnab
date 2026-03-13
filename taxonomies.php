<?php

use MapasCulturais\i;

$taxonomies = include APPLICATION_PATH . '/conf/taxonomies.php';

// Garante estrutura esperada
if (!isset($taxonomies[2]['restricted_terms']) || !is_array($taxonomies[2]['restricted_terms'])) {
    $taxonomies[2]['restricted_terms'] = [];
}

$label = i::__('Espaços e Equipamentos Culturais');

// Só adiciona e ordena se ainda não existir
if (!in_array($label, $taxonomies[2]['restricted_terms'], true)) {
    $taxonomies[2]['restricted_terms'][] = $label;
    sort($taxonomies[2]['restricted_terms']);
}

return $taxonomies;