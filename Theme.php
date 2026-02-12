<?php

namespace Pnab;

use AldirBlanc\Services\UserAccessService;
use MapasCulturais\i;
use MapasCulturais\App;
use Pnab\Enum\OtherValues;

/**
 * @method void import(string $components) Importa lista de componentes Vue. * 
 */
// Alteração necessária para rodar o theme-Pnab como submodule do culturagovbr/mapadacultura
// class Theme extends \BaseTheme\Theme
class Theme extends \MapasCulturais\Themes\BaseV2\Theme
{
    private const METADATA_RANGE_SUM_KEYS = [
        'vacancies' => 'limit',
        'totalResource' => 'value',
    ];

    static function getThemeFolder()
    {
        return __DIR__;
    }

    function _init()
    {
        parent::_init();
        $app = App::i();

        $canAccess = UserAccessService::canAccess();
        $theme = $this;

        /**
         * Controla a renderização do link "Oportunidades" no header baseado no acesso do usuário
         */
        $app->hook('template(<<*>>.mc-header-menu):begin', function () use ($canAccess) {
            if ($canAccess) {
                /** @var \MapasCulturais\Theme $this */
                $this->part('header-menu-opportunity-link');
            }
        });

        /**
         * Verifica se o usuário tem permissão para acessar a rota de minhas oportunidades
         */
        $app->hook('GET(panel.opportunities):before', function () use ($app, $canAccess) {
            if (!$canAccess) {
                $app->pass();
            }
        });

        /**
         * Implementa a action para Oportunidades do Ente Federado
         * Renderiza view customizada que filtra por federativeEntityId
         */
        $app->hook('GET(panel.federativeEntityOpportunities)', function () use ($app, $canAccess) {
            $this->requireAuthentication();
            if (!$canAccess) {
                $app->pass();
            }

            $this->render('federative-entity-opportunities');
        });

        /**
         * Implementa a action para Minha Equipe do Ente Federado
         * Renderiza view customizada que lista os gestores/agentes
         */
        $app->hook('GET(panel.federativeEntityAgents)', function () use ($app, $canAccess) {
            $this->requireAuthentication();
            if (!$canAccess) {
                $app->pass();
            }

            $this->render('federative-entity-agents');
        });

        /**
         * Verifica se o usuário tem permissão para criar uma oportunidade
         */
        $app->hook('POST(opportunity.index):before', function () use ($canAccess) {
            if (!$canAccess) {
                $this->errorJson(\MapasCulturais\i::__('Criação não permitida'), 403);
            }
        });

        $app->hook('PATCH(opportunity.single):before', function () use ($theme) {
            $entity = $this->requestedEntity;
            $postData = $this->postData;

            foreach (self::METADATA_RANGE_SUM_KEYS as $metadataKey => $keyTarget) {
                $totalByMetadata = $theme->validateTotalByMetadata($entity, $postData, $metadataKey, $keyTarget);
                if ($totalByMetadata) {
                    $this->errorJson($totalByMetadata, 400);
                }
            }

            $theme->trimOtherValue('etapa', 'etapaOutros', $postData);
            $theme->trimOtherValue('pauta', 'pautaOutros', $postData);
        });

        /**
         * Hook na API para listar oportunidades do ente federado
         * Usa API.find(opportunity).params para processar os parâmetros antes do MapasCulturais
         */
        $app->hook('API.find(opportunity).params', function (&$api_params) use ($app) {
            // Se não for gestor CultBR, para aqui
            if (!UserAccessService::isGestorCultBr()) {
                return;
            }

            // Verifica se é a aba "Com permissão"
            $isGrantedTab = isset($api_params['@permissions']) && 
                           $api_params['@permissions'] === '@control' &&
                           isset($api_params['user']) && 
                           $api_params['user'] === '!EQ(@me)';
            
            if ($isGrantedTab) {
                // Remove federativeEntityId se presente, mas mantém outros filtros
                unset($api_params['federativeEntityId']);
                return;
            }

            // Verifica se há federativeEntityId nos parâmetros da requisição
            $federativeEntityIdParam = $api_params['federativeEntityId'] ?? null;
            
            // Se não tiver nos parâmetros, tenta buscar da sessão
            if (!$federativeEntityIdParam && isset($_SESSION['selectedFederativeEntity'])) {
                $selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);
                if ($selectedEntity && isset($selectedEntity['id'])) {
                    $federativeEntityIdParam = (string)$selectedEntity['id'];
                }
            }

            // Se ainda não tiver federativeEntityId, para aqui
            if (!$federativeEntityIdParam) {
                return;
            }

            // Remove filtros de user/owner para mostrar todas as oportunidades do ente federado
            unset($api_params['user'], $api_params['owner']);

            // Extrai o ID do federativeEntityId (remove EQ() se presente)
            $federativeEntityId = preg_match('/^EQ\((\d+)\)$/', $federativeEntityIdParam, $m) 
                ? (int)$m[1] 
                : (int)$federativeEntityIdParam;

            // Processa o status: remove duplicação de EQ() e extrai operadores
            if (isset($api_params['status'])) {
                $status = trim($api_params['status']);
                
                // Remove múltiplas camadas de EQ() - ex: EQ(EQ(0)) -> EQ(0), EQ(EQ(EQ(0))) -> EQ(0)
                while (preg_match('/^EQ\((EQ\([^)]+\))\)$/', $status, $m)) {
                    $status = $m[1];
                }
                
                // Remove EQ() de operadores - ex: EQ(GTE(1)) -> GTE(1)
                if (preg_match('/^EQ\((GTE|LTE|GT|LT|IN|BETWEEN)\(([^)]+)\)\)$/', $status, $m)) {
                    $status = $m[1] . '(' . $m[2] . ')';
                }
                // Se vier apenas como número sem formatação, adiciona EQ()
                elseif (preg_match('/^-?\d+$/', $status)) {
                    $status = 'EQ(' . $status . ')';
                }
                // Garante que sempre tenha formato válido (EQ, GTE, etc)
                elseif (!preg_match('/^(EQ|GTE|LTE|GT|LT|IN|BETWEEN)\(/', $status)) {
                    // Se não tiver formato válido, tenta extrair número e criar EQ()
                    if (preg_match('/(-?\d+)/', $status, $numMatch)) {
                        $status = 'EQ(' . $numMatch[1] . ')';
                    }
                }
                
                $api_params['status'] = $status;
            } else {
                $api_params['status'] = 'GTE(1)';
            }

            // Busca IDs das oportunidades com metadado federativeEntityId
            $conn = $app->em->getConnection();
            $params = [
                'meta_key' => 'federativeEntityId',
                'federativeEntityId' => (string)$federativeEntityId
            ];
            
            // Consulta que busca IDs das oportunidades com metadado federativeEntityId
            $sql = "SELECT DISTINCT o.id 
                    FROM opportunity o
                    INNER JOIN opportunity_meta m ON m.key = :meta_key 
                        AND m.value = :federativeEntityId
                        AND m.object_id = CASE 
                            WHEN o.parent_id IS NOT NULL THEN o.parent_id 
                            ELSE o.id 
                        END";
            
            $opportunityIds = [];
            try {
                $results = $conn->executeQuery($sql, $params)->fetchAll();
                $opportunityIds = array_map(fn($r) => (int)$r['id'], $results);
            } catch (\Exception $e) {
                // Se houver erro, retorna array vazio
                $opportunityIds = [];
            }
            
            // Aplica filtro APENAS se houver oportunidades encontradas
            // Se não houver nenhuma oportunidade com o metadado, retorna filtro vazio (EQ(-1))
            if (empty($opportunityIds)) {
                $api_params['id'] = 'EQ(-1)';
            } else {
                $api_params['id'] = 'IN(' . implode(',', $opportunityIds) . ')';
            }
            
            unset($api_params['federativeEntityId']);
        });

        /**
         * Hook para filtrar modelos de oportunidades por federativeEntityId na action findOpportunitiesModels
         * Intercepta o resultado após a execução e filtra apenas os modelos do ente federado selecionado
         * A action findOpportunitiesModels retorna um array de objetos com estrutura: {id, descricao, numeroFases, ...}
         */
        $app->hook('GET(opportunity.findOpportunitiesModels):after', function (&$result) use ($app) {
            // Se não for gestor CultBR, para aqui
            if (!UserAccessService::isGestorCultBr()) {
                return;
            }

            // Verifica se há federativeEntityId na sessão
            $federativeEntityId = null;
            if (isset($_SESSION['selectedFederativeEntity'])) {
                $selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);
                if ($selectedEntity && isset($selectedEntity['id'])) {
                    $federativeEntityId = (int)$selectedEntity['id'];
                }
            }

            // Se não tiver federativeEntityId, para aqui (mantém comportamento padrão)
            if (!$federativeEntityId) {
                return;
            }

            // Se o resultado não for um array, para aqui
            if (!is_array($result)) {
                return;
            }

            // Busca IDs dos modelos que devem ser exibidos (com metadado federativeEntityId)
            // Inclui modelos cuja oportunidade principal tem o metadado
            $conn = $app->em->getConnection();
            $params = [
                'meta_key' => 'federativeEntityId',
                'federativeEntityId' => (string)$federativeEntityId
            ];
            
            // Consulta otimizada que busca modelos relacionados às oportunidades do ente federado
            // Busca modelos onde o metadado está na própria oportunidade OU na oportunidade principal (para modelos com parent)
            $sql = "SELECT DISTINCT o.id 
                    FROM opportunity o
                    INNER JOIN opportunity_meta m_model ON m_model.object_id = o.id 
                        AND m_model.key = 'isModel' 
                        AND m_model.value = '1'
                    INNER JOIN opportunity_meta m_fed ON m_fed.key = :meta_key 
                        AND m_fed.value = :federativeEntityId
                        AND m_fed.object_id = CASE 
                            WHEN o.parent_id IS NOT NULL THEN o.parent_id 
                            ELSE o.id 
                        END";
            
            $allowedModelIds = [];
            try {
                $results = $conn->executeQuery($sql, $params)->fetchAll();
                $allowedModelIds = array_map(fn($r) => (int)$r['id'], $results);
            } catch (\Exception $e) {
                // Se houver erro, retorna array vazio
                $allowedModelIds = [];
            }
            
            // Filtra o resultado para manter apenas os modelos permitidos
            if (!empty($allowedModelIds)) {
                $result = array_filter($result, function($model) use ($allowedModelIds) {
                    // Verifica se o modelo tem ID e se está na lista de permitidos
                    return isset($model['id']) && in_array((int)$model['id'], $allowedModelIds);
                });
                // Reindexa o array após filtrar para manter índices numéricos sequenciais
                $result = array_values($result);
            } else {
                // Se não houver modelos permitidos, retorna array vazio
                $result = [];
            }
        });

       /**
         * Hook na API para listar agentes associados ao ente federado quando há federativeEntityId
         */
        $app->hook('API.find(agent).params', function (&$api_params) use ($app, $canAccess) {
            if (!$canAccess) {
                return;
            }

            $federativeEntityIdParam = $api_params['federativeEntityId'] ?? null;
            if (!$federativeEntityIdParam) {
                return;
            }

            preg_match('/EQ\((\d+)\)/', $federativeEntityIdParam, $matches);
            $federativeEntityId = $matches[1] ?? null;
            if (!$federativeEntityId) {
                return;
            }

            $federativeEntityRef = $app->em->getReference('AldirBlanc\Entities\FederativeEntity', $federativeEntityId);
            $relations = $app->repo('AldirBlanc\Entities\FederativeEntityAgentRelation')->findBy([
                'owner' => $federativeEntityRef,
                'status' => 1
            ]);

            // Extrai os IDs dos agentes (status >= 1)
            $agentIds = [];
            foreach ($relations as $relation) {
                if ($relation->agent && $relation->agent->status >= 1) {
                    $agentIds[] = $relation->agent->id;
                }
            }

            // Se não houver agentes, retorna filtro vazio
            if (empty($agentIds)) {
                $api_params['id'] = 'EQ(-1)';
            } else {
                $api_params['id'] = 'IN(' . implode(',', $agentIds) . ')';
            }
            
            unset($api_params['federativeEntityId']);
        });

        /**
         * Define o metadado federativeEntityId ao salvar entidades
         * Garante que o ID da entidade federativa seja salvo junto com a entidade
         */
        $app->hook('entity(<<*>>).save:before', function () {
            if (UserAccessService::isGestorCultBr() && isset($_SESSION['selectedFederativeEntity'])) {
                $selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);
                if ($selectedEntity && isset($selectedEntity['id'])) {
                    $entityId = (int)$selectedEntity['id'];

                    // Verifica se a entidade suporta metadados e se o metadado está registrado
                    if (method_exists($this, 'getRegisteredMetadata')) {
                        $metadata_def = $this->getRegisteredMetadata('federativeEntityId', true);
                        if ($metadata_def) {
                            $this->setMetadata('federativeEntityId', $entityId);
                        }
                    }
                }
            }
        });

        /**
         * Limpa cache de permissões quando o Ente Federado selecionado muda
         * Isso garante que as permissões sejam recalculadas imediatamente
         */
        $app->hook('aldirblanc.selectFederativeEntity:after', function() use ($app) {
            $userAgent = $app->user->profile;
            if ($userAgent && method_exists($userAgent, 'clearPermissionCache')) {
                $userAgent->clearPermissionCache();
            }
        });

        /**
         * Bloqueia a renderização e a criação de um novo aplicativo
         */
        $app->hook('GET(panel.apps):before', fn() => $this->errorJson(\MapasCulturais\i::__('Acesso não permitido'), 403));
        $app->hook('POST(app.index):before', fn() => $this->errorJson(\MapasCulturais\i::__('Acesso não permitido'), 403));

        /**
         * Configura o menu do painel: renomeia "Minhas Oportunidades" e move para "Ente Federado"
         */
        $app->hook('panel.nav', function (&$nav) use ($app, $canAccess) {
            // Removendo o menu de "Meus aplicativos" [para todos os usuários]
            $nav['more']['condition'] = fn() => false;

            // Só manipula os menus para GestorCultBr, se não for, parar aqui
            if (!UserAccessService::isGestorCultBr()) {
                return;
            }

            // Remove o menu "Admin" para GestorCultBr
            $nav['admin']['condition'] = fn() => false;

            // Remove o menu "Minhas Oportunidades" do grupo original
            foreach ($nav['opportunities']['items'] as $key => $item) {
                if (isset($item['route']) && $item['route'] === 'panel/opportunities') {
                    $nav['opportunities']['items'][$key]['condition'] = fn() => false;
                }
            }

            // Remove o menu "Minhas Validações" do grupo "Editais e Oportunidades" (opportunities)
            if (isset($nav['opportunities']['items'])) {
                foreach ($nav['opportunities']['items'] as $key => $item) {
                    if (isset($item['route']) && $item['route'] === 'panel/validations') {
                        $nav['opportunities']['items'][$key]['condition'] = fn() => false;
                    }
                }
            }
        
            // Criando menus específicos para GestorCultBr
            $nav['federativeEntity'] = [
                'condition' => fn() => true,
                'label' => i::__('Ente Federado'),
                'items' => [
                    [
                        'route' => 'panel/opportunities',
                        'icon' => 'opportunity',
                        'label' => i::__('Oportunidades'),
                    ],
                    [
                        'route' => 'panel/federativeEntityAgents',
                        'icon' => 'agent',
                        'label' => i::__('Minha Equipe'),
                    ],
                    [
                        'route' => 'panel/validations',
                        'icon' => 'opportunity',
                        'label' => i::__('Minhas Validações'),
                    ]
                ],
            ];
        });

        $this->enqueueStyle('app-v2', 'main', 'css/theme-Pnab.css');

        // Mapeia o ícone do X (antigo Twitter) para o novo logo do X
        $app->hook('component(mc-icon).iconset', function (&$iconset) {
            $iconset['twitter'] = 'simple-icons:x';
        });

        /**
         * Redireciona para consolidação após login bem-sucedido
         * Limpa a seleção de entidade federativa quando o usuário faz login
         * Não redireciona admins (não há o que consolidar)
         */
        $app->hook('auth.successful', function () use ($app) {
            // Se for admin em qualquer nível, não precisa consolidar dados
            if (UserAccessService::isAdmin()) {
                return;
            }

            // Limpa flags de sincronização anteriores (incluindo erros)
            unset($_SESSION['gestor_cult_sync_started']);
            unset($_SESSION['gestor_cult_sync_completed']);
            unset($_SESSION['gestor_cult_sync_error']);
            unset($_SESSION['gestor_cult_sync_error_message']);
            
            // Limpa a seleção de entidade federativa
            unset($_SESSION['selectedFederativeEntity']);
            unset($_SESSION['federative_entity_redirect_uri']);
            
            // Redireciona para a tela de consolidação (que vai disparar o sync)
            $_SESSION['mapasculturais.auth.redirect_path'] = $app->createUrl('aldirblanc', 'consolidatingData');
        });

        /**
         * Limpa a seleção de entidade federativa e flags de sync quando o usuário faz logout
         */
        $app->hook('auth.logout:before', function () {
            unset($_SESSION['selectedFederativeEntity']);
            unset($_SESSION['federative_entity_redirect_uri']);
            unset($_SESSION['gestor_cult_sync_started']);
            unset($_SESSION['gestor_cult_sync_completed']);
            unset($_SESSION['gestor_cult_sync_error']);
            unset($_SESSION['gestor_cult_sync_error_message']);
        });

        /**
         * Hook que bloqueia acesso quando há erro de consolidação
         * Captura todas as requisições GET e POST, exceto auth, consolidatingData, startSync, checkSyncStatus, logoutOnError, selectFederativeEntity, changeFederativeEntity e federativeEntities
         * Não bloqueia admins (não há o que consolidar)
         */
        $blockAccessOnError = function () use ($app) {
            if ($app->user->is('guest')) {
                return;
            }

            // Se for admin em qualquer nível, não precisa consolidar dados
            if (UserAccessService::isAdmin()) {
                return;
            }

            $route = [$this->id, $this->action];

            // Ignora as rotas de consolidação, sync, seleção, alteração, verificação de status e busca de entes federados
            if ($route[0] === 'aldirblanc' && in_array($route[1], ['consolidatingData', 'startSync', 'selectFederativeEntity', 'changeFederativeEntity', 'checkSyncStatus', 'federativeEntities', 'logoutOnError'])) {
                return;
            }

            // Verifica se o sync foi iniciado mas ainda não foi concluído
            $syncStarted = isset($_SESSION['gestor_cult_sync_started']) && $_SESSION['gestor_cult_sync_started'] === true;
            $syncCompleted = isset($_SESSION['gestor_cult_sync_completed']) && $_SESSION['gestor_cult_sync_completed'] === true;
            $hasError = isset($_SESSION['gestor_cult_sync_error']) && 
                       $_SESSION['gestor_cult_sync_error'] !== null && 
                       $_SESSION['gestor_cult_sync_error'] !== '';

            // Se há erro de sync, bloqueia TODAS as requisições e redireciona para consolidação
            if ($syncCompleted && $hasError) {
                // Para requisições AJAX, retorna erro JSON
                if ($app->request->isAjax()) {
                    /** @var \MapasCulturais\Controller $this */
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode([
                        'error' => true,
                        'message' => 'Não foi possível consolidar seus dados. Você será desconectado.',
                        'redirectTo' => $app->createUrl('aldirblanc', 'consolidatingData')
                    ]);
                    exit;
                }
                
                // Para requisições normais, redireciona
                $_SESSION['federative_entity_redirect_uri'] = $_SERVER['REQUEST_URI'] ?? "";
                $url = $app->createUrl('aldirblanc', 'consolidatingData');
                $app->redirect($url);
                return;
            }

            // Se o sync foi iniciado mas não foi concluído, redireciona para consolidação
            if ($syncStarted && !$syncCompleted) {
                if (!$app->request->isAjax()) {
                    $_SESSION['federative_entity_redirect_uri'] = $_SERVER['REQUEST_URI'] ?? "";
                }
                $url = $app->createUrl('aldirblanc', 'consolidatingData');
                $app->redirect($url);
                return;
            }

            // Após o sync terminar sem erro, verifica se é gestor e precisa selecionar entidade
            if ($syncCompleted && !$hasError && UserAccessService::isGestorCultBr()) {
                // Verifica se existe entidade federativa selecionada na sessão
                if (!isset($_SESSION['selectedFederativeEntity'])) {
                    if (!$app->request->isAjax()) {
                        $_SESSION['federative_entity_redirect_uri'] = $_SERVER['REQUEST_URI'] ?? "";
                    }
                    // Redireciona para a tela de seleção
                    $url = $app->createUrl('aldirblanc', 'selectFederativeEntity');
                    $app->redirect($url);
                }
            }
        };

        // Hook para requisições GET
        $app->hook('GET(<<*>>):before,-GET(<<auth>>.<<*>>):before', $blockAccessOnError);
        
        // Hook para requisições POST
        $app->hook('POST(<<*>>):before,-POST(<<auth>>.<<*>>):before', $blockAccessOnError);

        // Adiciona banner com informações do ente federado selecionado
        $app->hook('template(<<*>>.main-header):after', function () use ($app) {
            /** @var \MapasCulturais\Theme $this */
            if (UserAccessService::isGestorCultBr() && isset($_SESSION['selectedFederativeEntity'])) {
                $this->part('federative-entity-banner');
            }
        });

    }


    function register()
    {
        parent::register();

        $app = App::i();

        /**
         * Registra o papel de Gestor CultBR
         */
        $def = new \MapasCulturais\Definitions\Role(
            'GestorCultBr',
            i::__('Gestor CultBR'),
            i::__('Gestor CultBR'),
            true,
            function (\MapasCulturais\UserInterface $user, $subsite_id) {
                return false;
            },
            [],
        );
        $app->registerRole($def);

        /**
         * Validação de oportunidade: Exige arquivo de regulamento para validar
         */
        $app->hook('opportunity.canValidate', function (&$errors) {
            $opportunity = $this;

            $regulations = $opportunity->getFiles('rules');
            if (empty($regulations)) {
                $errors[] = i::__('O campo "Adicionar regulamento" é obrigatório.');
            }

            // Validar Tipos de Proponente
            $proponentTypes = $opportunity->registrationProponentTypes;
            if (empty($proponentTypes)) {
                $errors[] = i::__('O campo "Tipos do proponente" é obrigatório.');
            }
        });

        /**
         * Validação de oportunidade: Torna o campo "Tipos do proponente" obrigatório
         */
        $app->hook('entity(Opportunity).validations', function(&$validations) {
            /** @var \MapasCulturais\Entities\Opportunity $this */
            if (!$this->isNew() && !$this->isLastPhase) {
                if (!is_array($this->registrationProponentTypes)) {
                    $this->registrationProponentTypes = [];
                }
                $validations['registrationProponentTypes'] = [
                    'required' => i::__('O campo "Tipos do proponente" é obrigatório.')
                ];
            }
        });

        /**
         * Validação adicional: Garante que arrays vazios sejam tratados como inválidos
         */
        $app->hook('entity(Opportunity).validationErrors', function(&$errors) use ($app) {
            /** @var \MapasCulturais\Entities\Opportunity $this */
            if (!$this->isNew() && !$this->isLastPhase) {
                // Validação de Tipos do proponente
                $proponentTypes = $this->registrationProponentTypes;
                if (!is_array($proponentTypes) || count($proponentTypes) === 0) {
                    $errors['registrationProponentTypes'] = [i::__('O campo "Tipos do proponente" é obrigatório.')];
                }
                
                // Validação de Regulamento
                $regulations = $this->getFiles('rules');
                if (empty($regulations)) {
                    $errors['rules'] = [i::__('O campo "Adicionar regulamento" é obrigatório.')];
                }
            }
            
            // Garante que TODOS os campos com erro sejam incluídos no postData
            if (!$this->isNew() && !empty($errors)) {
                $controller = $app->controller('opportunity');
                if ($controller && isset($controller->postData)) {
                    foreach ($errors as $field => $fieldErrors) {
                        if (!isset($controller->postData[$field])) {
                            // Adiciona o campo ao postData apenas se não estiver presente
                            $controller->postData[$field] = property_exists($this, $field) ? $this->$field : null;
                        }
                    }
                }
            }
        });

        /**
         * Garante que os campos customizados sejam incluídos no POST mesmo quando não estão presentes
         * Necessário para que a validação seja executada e o erro seja retornado
         * Usa a mesma condição das validações existentes: !$entity->isNew() && !$entity->isLastPhase
         * IMPORTANTE: Não sobrescreve campos existentes, apenas adiciona os que não estão presentes
         */
        $app->hook('PATCH(opportunity.single):data', function(&$data) {
            /** @var \MapasCulturais\Controllers\Opportunity $this */
            $entity = $this->requestedEntity;
            if ($entity && !$entity->isNew() && !$entity->isLastPhase) {
                if (!isset($data['registrationProponentTypes']) && !isset($this->postData['registrationProponentTypes'])) {
                    $data['registrationProponentTypes'] = is_array($entity->registrationProponentTypes) 
                        ? $entity->registrationProponentTypes 
                        : [];
                    $this->postData['registrationProponentTypes'] = $data['registrationProponentTypes'];
                }
                
                // Garante que o erro de arquivo seja retornado mesmo quando não está no POST
                if (!isset($this->postData['rules'])) {
                    $this->postData['rules'] = null;
                }
            }
        });

        /**
         * Torna a taxonomia "área de atuação" opcional para Opportunity
         */
        $app->hook('app.register:after', function () use ($app) {
            $taxonomies = $app->getRegisteredTaxonomies('MapasCulturais\Entities\Opportunity');
            
            if (isset($taxonomies['area'])) {
                $taxonomies['area']->required = false;
            }
        });

        /**
         * Modifica o objeto JavaScript para refletir que a taxonomia "área de atuação" é opcional para Opportunity
         */
        $app->hook('mapas.printJsObject:before', function () use ($app) {
            if (isset($this->jsObject['Taxonomies']['area'])) {
                $this->jsObject['Taxonomies']['area']['required'] = false;
            }
        });

        /**
         * Registra metadados de oportunidade: Segmento, Etapa, Pauta e Território
         * Tenta reutilizar as opções do OpportunityWorkplan quando disponível
         * Usa app.init:after para garantir que os metadados do core já foram registrados
         */
        $theme = $this;
        $app->hook('app.init:after', function() use ($app, $theme) {
            // Registra metadados select obrigatórios em edit
            $theme->registerSelectMetadata('segmento', i::__('Segmento artistico-cultural'), $theme->getSegmentoOptions(), 'edit');
            $theme->registerSelectMetadata('etapa', i::__('Etapa do fazer cultural'), $theme->getEtapaOptions(), 'edit');
            $theme->registerSelectMetadata('pauta', i::__('Pauta temática'), $theme->getPautaOptions(), 'edit');
            $theme->registerSelectMetadata('territorio', i::__('Território'), $theme->getTerritorioOptions(), 'edit');

            // Registra metadados select obrigatórios em required
            $theme->registerSelectMetadata('tipoDeEdital', i::__('Tipo de Edital'), $theme->getTipoDeEditalOptions(), 'required');
            
            // Registra campos "Outros" para especificar quando "Outra" for selecionada
            $theme->registerOutrosMetadata('etapaOutros', i::__('Especificar etapa do fazer cultural'), 'etapa', 'etapaOutros');
            $theme->registerOutrosMetadata('pautaOutros', i::__('Especificar pauta temática'), 'pauta', 'pautaOutros');
        });
    }

    /**
     * Registra um metadado do tipo select obrigatório
     * 
     * @param string $key Chave do metadado
     * @param string $label Label do campo (já traduzido)
     * @param array $options Opções do select
     * @param string $operationType Tipo de operação (edit ou create)
     */
    private function registerSelectMetadata(string $key, string $label, array $options, string $operationType): void
    {
        $metadataValues =  [
            'label' => $label,
            'type' => 'select',
            'options' => $options,
        ];

        if ($operationType === 'required') {
            $metadataValues['validations'] = [
                'required' => i::__('O campo ') . strtolower($label) . i::__(' é obrigatório.'),
            ];
        } else {
            $metadataValues['should_validate'] = function($entity) use ($label, $operationType) {
                return $this->redefineRuleValidate($operationType, $entity, $label);
            };
        }

        $this->registerOpportunityMetadata($key, $metadataValues);
    }

    /**
     * Redefine a regra de validação do metadado select obrigatório
     * 
     * @param string $operationType Tipo de operação (edit ou create)
     * @param \MapasCulturais\Entity $entity Entidade que contém os campos
     * @param string $label Label do campo (já traduzido)
     * @return string|false Retorna mensagem de erro se inválido, false se não precisa validar
     */
    private function redefineRuleValidate($operationType, $entity, $label) {
        if ($operationType === 'edit') {
            if (!empty($entity->id)) {
                return i::__('O campo ') . strtolower($label) . i::__(' é obrigatório.');
            }
            return false;
        }

        if ($operationType === 'create') {
            if (!isset($entity->id) || $entity->id === null || $entity->id === '') {
                return i::__('O campo ') . strtolower($label) . i::__(' é obrigatório.');
            }
            return false;
        }

        return false;
    }

    /**
     * Registra um metadado "Outros" para especificar quando "Outra" for selecionada
     * 
     * @param string $key Chave do metadado "Outros"
     * @param string $label Label do campo (já traduzido)
     * @param string $campoPrincipal Nome do campo principal (ex: 'etapa', 'pauta')
     * @param string $campoOutros Nome do campo "Outros" (ex: 'etapaOutros', 'pautaOutros')
     */
    private function registerOutrosMetadata(string $key, string $label, string $campoPrincipal, string $campoOutros): void
    {
        $theme = $this;
        $this->registerOpportunityMetadata($key, [
            'label' => $label,
            'type' => 'string',
            'should_validate' => function($entity, $value) use ($theme, $campoPrincipal, $campoOutros, $label) {
                return $theme->validateOutrosField(
                    $entity,
                    $value,
                    $campoPrincipal,
                    $campoOutros,
                    i::__('O campo ') . strtolower($label) . i::__(' é obrigatório quando "Outra (especificar)" é selecionada.')
                );
            },
        ]);
    }

    /**
     * Valida campo "Outros" quando o campo principal contém "outra"
     * 
     * @param object $entity Entidade que contém os campos
     * @param mixed $value Valor atual do campo "Outros"
     * @param string $campoPrincipal Nome do campo principal (ex: 'etapa', 'pauta')
     * @param string $campoOutros Nome do campo "Outros" (ex: 'etapaOutros', 'pautaOutros')
     * @param string $mensagemErro Mensagem de erro a retornar se a validação falhar
     * @return string|false Retorna mensagem de erro se inválido, false se não precisa validar
     */
    private function validateOutrosField($entity, $value, string $campoPrincipal, string $campoOutros, string $mensagemErro)
    {
        $valorPrincipal = $entity->{$campoPrincipal} ?? '';
        
        if (!$valorPrincipal || stripos($valorPrincipal, 'outra') === false) {
            return false;
        }
        
        $valorAtual = ($value !== null && $value !== '') ? $value : ($entity->{$campoOutros} ?? null);
        
        if ($valorAtual === null || $valorAtual === '' || trim((string)$valorAtual) === '') {
            return $mensagemErro;
        }
        
        return false;
    }

    /**
     * Obtém opções de metadados de uma entidade específica
     * 
     * @param string $className Nome completo da classe da entidade
     * @param string $metadataKey Chave do metadado a ser obtido
     * @return array Array de opções ou array vazio se não encontrado
     */
    private function getMetadataOptions(string $className, string $metadataKey): array
    {
        if (!class_exists($className)) {
            return [];
        }
        
        $app = App::i();
        $allMetadata = $app->getRegisteredMetadata($className);
        
        if (isset($allMetadata[$metadataKey]) && isset($allMetadata[$metadataKey]->options)) {
            return $allMetadata[$metadataKey]->options;
        }
        
        return [];
    }

    /**
     * Obtém as opções de Segmento do OpportunityWorkplan
     */
    private function getSegmentoOptions(): array
    {
        return $this->getMetadataOptions(
            'OpportunityWorkplan\Entities\Workplan',
            'culturalArtisticSegment'
        );
    }

    /**
     * Obtém as opções de Etapa do OpportunityWorkplan
     */
    public function getEtapaOptions(): array
    {
        return $this->getMetadataOptions(
            'OpportunityWorkplan\Entities\Goal',
            'culturalMakingStage'
        );
    }

    /**
     * Obtém as opções de Pauta do OpportunityWorkplan
     */
    public function getPautaOptions(): array
    {
        return $this->getMetadataOptions(
            'OpportunityWorkplan\Entities\Workplan',
            'thematicAgenda'
        );
    }

    /**
     * Obtém as opções de Território do ProjectMonitoring
     */
    private function getTerritorioOptions(): array
    {
        return $this->getMetadataOptions(
            'OpportunityWorkplan\Entities\Delivery',
            'priorityAudience'
        );
    }

    /*
     * Obtém as opções de Tipo de Edital
     */
    private function getTipoDeEditalOptions(): array
    {
        return array(
            i::__('Execução cultural'),
            i::__('Subsídio a espaços culturais'),
            i::__('Bolsa cultural'),
            i::__('Premiação cultural'),
            i::__('TCC Pontos de Cultura'),
            i::__('TCC Pontões de Cultura'),
            i::__('Bolsa Cultura Viva'),
            i::__('Premiação Cultura Viva'),
            i::__('Programa Nacional de Ações Continuadas'),
            i::__('Programa Nacional de Infraestrutura Cultural'),
            i::__('Programa Nacional de Formação para Gestores'),
            i::__('Outros')
        );
    }

    /**
     * Aplica trim no campo "Outros" quando o campo principal possui valor "Outra (especificar)"
     * 
     * @param string $tipo Nome do campo principal (ex: 'etapa', 'pauta')
     * @param string $outroTipo Nome do campo "Outros" (ex: 'etapaOutros', 'pautaOutros')
     * @param array &$postData Referência ao array de dados POST
     */
    private function trimOtherValue(string $tipo, string $outroTipo, array &$postData): void
    {
        $valorEsperado = $tipo === 'etapa' ? OtherValues::OUTRA_ETAPA : OtherValues::OUTRA_PAUTA;
        
        if (
            isset($postData[$tipo]) && isset($postData[$outroTipo]) &&
            $postData[$tipo] === $valorEsperado && 
            $postData[$outroTipo] !== null && $postData[$outroTipo] !== ''
        ) {
            $postData[$outroTipo] = trim($postData[$outroTipo]);
        }
    }

    private function validateTotalByMetadata($entity, array $postData, string $metadataKey, string $keyTarget)
    {
        if (!isset($postData[$metadataKey]) && !isset($postData['registrationRanges'])) {
            return false;
        }

        $metadataValue = $postData[$metadataKey] ?? ($entity->{$metadataKey} ?? null);
        if ($metadataValue === null || $metadataValue === '') {
            return false;
        }

        $registrationRanges = $postData['registrationRanges'] ?? ($entity->registrationRanges ?? []);
        if (!is_array($registrationRanges) || !$registrationRanges) {
            return false;
        }

        $convertVal = $metadataKey === 'vacancies' ? 'intval' : 'floatval';
        $totalMetadataInRanges = array_sum(array_map($convertVal, array_column($registrationRanges, $keyTarget)));

        if ($convertVal($metadataValue) > $totalMetadataInRanges) {
            return [
                $metadataKey => [i::__('Valor superior ao total das faixas.')]
            ];
        }

        return false;
    }
}
