<?php

$agent_types = include APPLICATION_PATH . '/conf/agent-types.php';

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

// Adequando escolaridade ao padrão de dados
if (isset($agent_types['metadata']['escolaridade']['options'])) {
    $agent_types['metadata']['escolaridade']['options'] = [
        MapasCulturais\i::__('Não possui educação formal'),
        MapasCulturais\i::__('Ensino fundamental incompleto ou equivalente'),
        MapasCulturais\i::__('Ensino fundamental completo ou equivalente'),
        MapasCulturais\i::__('Ensino médio incompleto ou equivalente'),
        MapasCulturais\i::__('Ensino médio completo ou equivalente'),
        MapasCulturais\i::__('Curso técnico incompleto'),
        MapasCulturais\i::__('Curso técnico completo'),
        MapasCulturais\i::__('Ensino superior incompleto ou equivalente'),
        MapasCulturais\i::__('Ensino superior completo'),
        MapasCulturais\i::__('Pós-graduação incompleto'),
        MapasCulturais\i::__('Pós-graduação completo')
    ];
}

// Adequando renda ao padrão de dados
if (isset($agent_types['metadata']['renda']['options'])) {
    $agent_types['metadata']['renda']['options'] = [
        MapasCulturais\i::__('Nenhuma renda'),
        MapasCulturais\i::__('De 1,00 a 500,00'),
        MapasCulturais\i::__('De 501,00 a 1.000,00'),
        MapasCulturais\i::__('De 1.001,00 a 2.000,00'),
        MapasCulturais\i::__('De 2.001,00 a 3.000,00'),
        MapasCulturais\i::__('De 3.001,00 a 5.000,00'),
        MapasCulturais\i::__('De 5.001,00 a 10.000,00'),
        MapasCulturais\i::__('De 10.001,00 a 20.000,00'),
        MapasCulturais\i::__('De 20.001,00 a 100.000,00'),
        MapasCulturais\i::__('Acima de 100.000,00'),
    ];
}

// Adequando raca ao padrão de dados
if (isset($agent_types['metadata']['raca']['options'])) {
    $agent_types['metadata']['raca']['options'] = [
        'Branca' => \MapasCulturais\i::__('Branca'),
        'Preta' => \MapasCulturais\i::__('Preta'),
        'Amarela' => \MapasCulturais\i::__('Amarela'),
        'Parda' => \MapasCulturais\i::__('Parda'),
        'Indígena' => \MapasCulturais\i::__('Indígena')
    ];

    $agent_types['metadata']['raca']['validations'] = [
        'required' => \MapasCulturais\i::__('O campo Raça/cor é obrigatório.')
    ];
}

// Adequando Genero ao padrão de dados
if (isset($agent_types['metadata']['genero']['options'])) {
    $agent_types['metadata']['genero']['options'] = [
        'Prefiro não declarar' => \MapasCulturais\i::__('Prefiro não declarar'),
        'Homem cisgênero' => \MapasCulturais\i::__('Homem cisgênero'),
        'Mulher cisgênero' => \MapasCulturais\i::__('Mulher cisgênero'),
        'Homem trans' => \MapasCulturais\i::__('Homem trans'),
        'Mulher trans' => \MapasCulturais\i::__('Mulher trans'),
        'Travesti' => \MapasCulturais\i::__('Travesti'),
        'Não binário' => \MapasCulturais\i::__('Não binário'),
        'Outro' => \MapasCulturais\i::__('Outro'),
    ];
}

// Adequando orientacaoSexual ao padrão de dados
if (isset($agent_types['metadata']['orientacaoSexual']['options'])) {
    $agent_types['metadata']['orientacaoSexual']['options'] = [
        'Lésbica' => \MapasCulturais\i::__('Lésbica'),
        'Gay' => \MapasCulturais\i::__('Gay'),
        'Heterossexual' => \MapasCulturais\i::__('Heterossexual'),
        'Bissexual' => \MapasCulturais\i::__('Bissexual'),
        'Outra' => \MapasCulturais\i::__('Outra'),
        'Prefere não responder' => \MapasCulturais\i::__('Prefere não responder'),
    ];
}

// Adequando pessoaDeficiente ao padrão de dados
if (isset($agent_types['metadata']['pessoaDeficiente']['options'])) {
    $agent_types['metadata']['pessoaDeficiente']['options'] = [
        MapasCulturais\i::__('Não'),
        MapasCulturais\i::__('Sim, Auditiva'),
        MapasCulturais\i::__('Sim, Física-motora'),
        MapasCulturais\i::__('Sim, Intelectual'),
        MapasCulturais\i::__('Sim, Múltipla'),
        MapasCulturais\i::__('Sim, Transtorno do Espectro Autista'),
        MapasCulturais\i::__('Sim, Visual'),
        MapasCulturais\i::__('Sim, Outra')
    ];
}

// Adequando comunidadesTradicional ao padrão de dados
if (isset($agent_types['metadata']['comunidadesTradicional']['options'])) {
    $agent_types['metadata']['comunidadesTradicional']['options'] = [
        MapasCulturais\i::__('Não pertence a povos ou comunidades tradicionais.'),
        MapasCulturais\i::__('Andirobeiros'),
        MapasCulturais\i::__('Apanhadores de flores sempre vivas'),
        MapasCulturais\i::__('Benzedeiros'),
        MapasCulturais\i::__('Catadores de mangaba'),
        MapasCulturais\i::__('Cipozeiros'),
        MapasCulturais\i::__('Comunidades de fundos e fechos de pasto'),
        MapasCulturais\i::__('Comunidades quilombolas'),
        MapasCulturais\i::__('Extrativistas costeiros e marinhos'),
        MapasCulturais\i::__('Extrativistas'),
        MapasCulturais\i::__('Faxinalenses'),
        MapasCulturais\i::__('Geraizeiros'),
        MapasCulturais\i::__('Ilhéus'),
        MapasCulturais\i::__('Juventude de povos e comunidades tradicionais'),
        MapasCulturais\i::__('Morroquianos'),
        MapasCulturais\i::__('Pantaneiros'),
        MapasCulturais\i::__('Pescadores artesanais'),
        MapasCulturais\i::__('Povo pomerano'),
        MapasCulturais\i::__('Povos ciganos'),
        MapasCulturais\i::__('Povos e comunidades de terreiro/povos e comunidades de matriz africana'),
        MapasCulturais\i::__('Povos indígenas'),
        MapasCulturais\i::__('Quebradeiras de coco babaçu'),
        MapasCulturais\i::__('Raizeiros'),
        MapasCulturais\i::__('Retireiros do Araguaia'),
        MapasCulturais\i::__('Ribeirinhos'),
        MapasCulturais\i::__('Vazanteiros'),
        MapasCulturais\i::__('Veredeiros'),
    ];
}

return $agent_types;
