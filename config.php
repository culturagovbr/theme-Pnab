<?php

use \MapasCulturais\i;

return [
    /*
    Define o nome do asset da imagem da logo do site - Substituirá a logo padrão

    ex: `img/meu-mapa-logo.jpg` (pasta assets/img/meu-mapa-logo.jpg do tema)
    */
    'app.siteName' => 'Pnab',
    'app.siteDescription' => 'Aqui você encontra informações de editais e oportunidades do Ministério da Cultura.',
    'logo.image' => './img/logo-site.png',
    'logo.hideLabel' => env('LOGO_HIDELABEL', true),

    // entidades habilitadas
    'app.enabled.agents'        => true,
    'app.enabled.opportunities' =>  true,
    'app.enabled.spaces'        => false,
    'app.enabled.projects'      =>  false,
    'app.enabled.events'        =>  false,
    'app.enabled.subsites'       => false,
    'app.enabled.seals'         =>  false,
    'app.enabled.apps'          =>  false,

    'mailer.templates' => [
        'welcome' => [
            'title' => i::__("Bem-vindo(a) à PNAB"),
            'template' => 'welcome.html'
        ],
        'last_login' => [
            'title' => i::__("Acesse à PNAB"),
            'template' => 'last_login.html'
        ],
        'new' => [
            'title' => i::__("Novo registro"),
            'template' => 'new.html'
        ],
        'update_required' => [
            'title' => i::__("Acesse à PNAB"),
            'template' => 'update_required.html'
        ],
        'compliant' => [
            'title' => i::__("Denúncia - Pnab"),
            'template' => 'compliant.html'
        ],
        'suggestion' => [
            'title' => i::__("Mensagem - Pnab"),
            'template' => 'suggestion.html'
        ],
        'seal_toexpire' => [
            'title' => i::__("Selo Certificador Expirando"),
            'template' => 'seal_toexpire.html'
        ],
        'seal_expired' => [
            'title' => i::__("Selo Certificador Expirado"),
            'template' => 'seal_expired.html'
        ],
        'opportunity_claim' => [
            'title' => i::__("Solicitação de Recurso de Oportunidade"),
            'template' => 'opportunity_claim.html'
        ],
        'request_relation' => [
            'title' => i::__("Solicitação de requisição"),
            'template' => 'request_relation.html'
        ],
        'start_registration' => [
            'title' => i::__("Inscrição iniciada"),
            'template' => 'start_registration.html'
        ],
        'start_data_collection_phase' => [
            'title' => i::__("Sua inscrição avaçou de fase"),
            'template' => 'start_data_collection_phase.html'
        ],
        'export_spreadsheet' => [
            'title' => i::__("Planilha disponível"),
            'template' => 'export_spreadsheet.html'
        ],
        'export_spreadsheet_error' => [
            'title' => i::__("Houve um erro com o arquivo"),
            'template' => 'export_spreadsheet_error.html'
        ],
        'send_registration' => [
            'title' => i::__("Inscrição enviada"),
            'template' => 'send_registration.html'
        ],
        'claim_form' => [
            'title' => i::__("Solicitação de recurso"),
            'template' => 'claim_form.html'
        ],
        'claim_certificate' => [
            'title' => i::__("Certificado de solicitação de recurso"),
            'template' => 'claim_certificate.html'
        ],

    ],


    /*
    Define as configurações de ícones de redes sociais do componente main-footer.
    */
    'social-media' => [
        'facebook-icon' => [
            'title' => 'facebook',
            'link' => 'https://www.facebook.com/mincgovbr'
        ],
        'twitter-icon' => [
            'title' => 'twitter',
            'link' => 'https://x.com/CulturaGovBr'
        ],
        'instagram-icon' => [
            'title' => 'instagram',
            'link' => 'https://www.instagram.com/minc'
        ],
        'youtube-icon' => [
            'title' => 'youtube',
            'link' => 'https://www.youtube.com/@ministeriodacultura'
        ]
    ],

    'routes' => [
        'default_controller_id' => 'site',
        'default_action_name' => 'index',
        'shortcuts' => [
            // busca
            'agentes'           => ['search', 'agents'],
            'oportunidades'     => ['search', 'opportunities'],

            // entidades
            'usuario'           => ['user', 'single'],
            'agente'            => ['agent', 'single'],
            'oportunidade'      => ['opportunity', 'single'],

            'edicao-de-usuario'           => ['user', 'edit'],
            'edicao-de-agente'            => ['agent', 'edit'],
            'gestao-de-oportunidade'      => ['opportunity', 'edit'],

            'configuracao-de-formulario'  => ['opportunity', 'formBuilder'],
            'lista-de-inscricoes'  => ['opportunity', 'registrations'],
            'lista-de-avaliacoes'  => ['opportunity', 'allEvaluations'],

            'avaliacoes'  => ['opportunity', 'userEvaluations'],

            'suporte/lista-de-inscricoes'  => ['support', 'list'],
            'suporte/formulario'  => ['support', 'form'],
            'suporte/configuracao' => ['support', 'supportConfig'],

            'baixar-rascunhos' => ['opportunity', 'reportDrafts'],
            'baixar-inscritos' => ['opportunity', 'report'],
            'baixar-avaliacoes' => ['opportunity', 'reportEvaluations'],

            'avaliacao' => ['registration', 'evaluation'],


            'historico'         => ['entityRevision', 'history'],

            'sair'              => ['auth', 'logout'],
            'sobre'             => ['site', 'page', ['sobre']],
            'como-usar'         => ['site', 'page', ['como-usar']],

            // LGPD
            'termos-de-uso'             => ['lgpd', 'view', ['termsOfUsage']],
            'politica-de-privacidade'   => ['lgpd', 'view', ['privacyPolicy']],
            'uso-de-imagem'             => ['lgpd', 'view', ['termsUse']],
            'termos-e-condicoes'        => ['lgpd', 'accept'],

            // painel
            'meus-agentes'             => ['panel', 'agents'],
            'minhas-oportunidades'     => ['panel', 'opportunities'],
            'oportunidades-do-ente-federado' => ['panel', 'federativeEntityOpportunities'],
            'minhas-inscricoes'        => ['panel', 'registrations'],
            'minhas-avaliacoes'        => ['panel', 'evaluations'],
            'minhas-prestacoes-de-contas'        => ['panel', 'prestacoes-de-conta'],

            'aparencia'               => ['theme-customizer', 'index'],

            'conta-e-privacidade'        => ['panel', 'my-account'],

            'inscricao' => ['registration', 'edit'],
            'inscricao' => ['registration', 'single'],
            'inscricao' => ['registration', 'view'],

            'visualizacao-de-formulario' => ['opportunity', 'formPreview'],

            'gestao-de-usuarios' => ['panel', 'user-management'],

            'certificado' => ['relatedSeal', 'single'],

            'perguntas-frequentes' => ['faq', 'index'],

            'file/arquivo-privado' => ['file', 'privateFile'],

        ],
        'controllers' => [
            'painel'         => 'panel',
            'inscricoes'     => 'registration',
            'inscricoes'     => 'registration',
            'autenticacao'   => 'auth',
            'anexos'         => 'registrationfileconfiguration',
            'revisoes'       => 'entityRevision',
            'historico'      => 'entityRevision',
            'suporte'        => 'support',
        ],
        'actions' => [
            'acesso'         => 'single',
            'lista'         => 'list',
            'apaga'         => 'delete',
            'edita'         => 'edit',
            'agentes'       => 'agents',
            'oportunidades' => 'oportunities',
            'oportunidades-do-ente-federado' => 'federativeEntityOpportunities',
            'inscricoes'    => 'registrations',
            'agente'        => 'agent',
            'inscricao'     => 'registration',
            'prestacoes-de-contas' => 'accountability'
        ],

        'readableNames' => [
            //controllers

            'panel'         => i::__('Painel'),
            'auth'          => i::__('Autenticação'),
            'site'          => i::__('Site'),
            'agent'         => i::__('Agente'),
            'agents'        => i::__('Agentes'),
            'opportunity'   => i::__('Oportunidade'),
            'opportunities' => i::__('Oportunidades'),
            'registration'  => i::__('Inscrição'),
            'registrations' => i::__('Inscrições'),
            'file'          => i::__('Arquivo'),
            'files'         => i::__('Arquivos'),
            'entityRevision' => i::__('Histórico'),
            'revisions'     => i::__('Revisões'),
            'sealrelation'  => i::__('Certificado'),
            //actions
            'list'          => i::__('Listando'),
            'index'         => i::__('Índice'),
            'delete'        => i::__('Apagando'),
            'edit'          => i::__('Editando'),
            'create'        => i::__('Criando novo'),
            'search'        => i::__('Busca')
        ]
    ],

    # AUTENTICAÇÃO
    'auth.provider' => '\MultipleLocalAuth\Provider',
    'auth.config' => [
        'salt' => env('AUTH_SALT', 'SECURITY_SALT'),
        'wizard' => env('AUTH_WIZARD_ENABLED', false),
        'timeout' => '24 hours',
        'strategies' => [
            'govbr' => [
                'visible' => env('AUTH_GOV_BR_VISIBLE', false),
                'response_type' => env('AUTH_GOV_BR_RESPONSE_TYPE', 'code'),
                'scope' => env('AUTH_GOV_BR_SCOPE', null),
                'auth_endpoint' => env('AUTH_GOV_BR_ENDPOINT', null),
                'token_endpoint' => env('AUTH_GOV_BR_TOKEN_ENDPOINT', null),
                'nonce' => env('AUTH_GOV_BR_NONCE', null),
                'code_verifier' => env('AUTH_GOV_BR_CODE_VERIFIER', null),
                'code_challenge' => env('AUTH_GOV_BR_CHALLENGE', null),
                'code_challenge_method' => env('AUTH_GOV_BR_CHALLENGE_METHOD', null),
                'userinfo_endpoint' => env('AUTH_GOV_BR_USERINFO_ENDPOINT', null),
                'state_salt' => env('AUTH_GOV_BR_STATE_SALT', null),
                'applySealId' => env('AUTH_GOV_BR_APPLY_SEAL_ID', null),
                'menssagem_authenticated' => env('AUTH_GOV_BR_MENSSAGEM_AUTHENTICATED', 'Usuário já se autenticou pelo GovBr'),
                'dic_agent_fields_update' => json_decode(env('AUTH_GOV_BR_DICT_AGENT_FIELDS_UPDATE', '{}'), true),

                # Autenticação customizada
                'client_id' => env('PNAB_AUTH_GOV_BR_CLIENT_ID', null),
                'client_secret' => env('PNAB_AUTH_GOV_BR_SECRET', null),
                'redirect_uri' => env('PNAB_AUTH_GOV_BR_REDIRECT_URI', null),
            ]
        ]
    ],

    # METABASE
    'Metabase' => [
        'config' => [
            'links' => []
        ]
    ],

    # CAPTCHA
    'captcha' => [
        'provider' => env('PNAB_CAPTCHA_PROVIDER', env('CAPTCHA_PROVIDER', 'google')),
        'providers' => [
            'google' => [
                'url' => 'https://www.google.com/recaptcha/api.js?onload=vueRecaptchaApiLoaded&render=explicit',
                'verify' => 'https://www.google.com/recaptcha/api/siteverify',
                'key' => env('PNAB_CAPTCHA_SITEKEY', env('CAPTCHA_SITEKEY', null)),
                'secret' => env('PNAB_CAPTCHA_SECRET', env('CAPTCHA_SECRET', null))
            ],
            'cloudflare' => [
                'url' => 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
                'verify' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                'key' => env('PNAB_CAPTCHA_SITEKEY', env('CAPTCHA_SITEKEY', null)),
                'secret' => env('PNAB_CAPTCHA_SECRET', env('CAPTCHA_SECRET', null))
            ]
        ]
    ]
];