<?php

/**
 * Sobrescrita PNAB: mesmo padrão de agent-types.php — inclui o core e ajusta metadados.
 * Garante `etapa` (e a lista usada no workplan via Theme.php) a partir desta definição
 * quando o tema ativo é o Pnab.
 */
$opportunity_types = include APPLICATION_PATH . '/conf/opportunity-types.php';

$opportunity_types['metadata']['etapa'] = [
    'label' => \MapasCulturais\i::__('Etapa do fazer cultural'),
    'type' => 'multiselect',
    'options' => [
        \MapasCulturais\i::__('Acesso, mediação e fruição'),
        \MapasCulturais\i::__('Comercialização e Distribuição'),
        \MapasCulturais\i::__('Criação'),
        \MapasCulturais\i::__('Difusão e Circulação'),
        \MapasCulturais\i::__('Formação'),
        \MapasCulturais\i::__('Memória e Preservação'),
        \MapasCulturais\i::__('Monitoramento e avaliação'),
        \MapasCulturais\i::__('Organização e gestão'),
        \MapasCulturais\i::__('Pesquisa e reflexão'),
        \MapasCulturais\i::__('Produção'),
        \MapasCulturais\i::__('Outra (especificar)'),
    ],
];

return $opportunity_types;
