<?php

namespace Pnab;

use AldirBlanc\Services\UserAccessService;
use MapasCulturais\i;
use MapasCulturais\App;

/**
 * @method void import(string $components) Importa lista de componentes Vue. * 
 */
// Alteração necessária para rodar o theme-Pnab como submodule do culturagovbr/mapadacultura
// class Theme extends \BaseTheme\Theme
class Theme extends \MapasCulturais\Themes\BaseV2\Theme
{
    static function getThemeFolder()
    {
        return __DIR__;
    }

    function _init()
    {
        parent::_init();
        $app = App::i();

        $canAccess = UserAccessService::canAccess();

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

            // Remove o menu "Minhas Validações" do grupo original
            if (isset($nav['registrations']['items'])) {
                foreach ($nav['registrations']['items'] as $key => $item) {
                    if (isset($item['route']) && $item['route'] === 'panel/evaluations') {
                        $nav['registrations']['items'][$key]['condition'] = fn() => false;
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
                        'route' => 'panel/evaluations',
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
    }
}
