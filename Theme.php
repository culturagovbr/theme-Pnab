<?php

namespace Pnab;

use AldirBlanc\Entities\User;
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

        $canAccess = \AldirBlanc\Entities\User::canAccess();

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
         */
        $app->hook('API(opportunity.find):before', function () use ($app) {
            if (!User::isGestorCultBr()) {
                return;
            }

            $federativeEntityId = $_GET['federativeEntityId'] ?? null;
            if (!$federativeEntityId) {
                return;
            }

            // Remove filtros de user/owner para permitir ver oportunidades de todos os usuários
            unset($_GET['user'], $_GET['owner']);

            // Sempre força filtros de status publicado e permissões de visualização
            $_GET['status'] = 'GTE(1)';
            $_GET['@permissions'] = 'view';
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
            if (User::isGestorCultBr() && isset($_SESSION['selectedFederativeEntity'])) {
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
         * Modifica o comportamento de canUser para Opportunities
         * Permite que agentes associados ao mesmo Ente Federado possam editar oportunidades
         * Se agente X e Y tiverem associados ao Ente Federado Z, ambos conseguem editar oportunidades de Z
         * IMPORTANTE: Também verifica se o Ente Federado selecionado na sessão corresponde ao da opportunity
         */
        $checkFederativeEntityPermission = function($user, &$result) use ($app) {
            /** @var \MapasCulturais\Entities\Opportunity $this */
            
            // Se já tem permissão, não precisa modificar
            if ($result) {
                return;
            }

            // Verifica se é gestor CultBR
            if (!User::isGestorCultBr()) {
                return;
            }

            // Limpa cache de permissões da entidade para forçar recálculo baseado na sessão atual
            // Isso garante que mudanças no Ente Federado selecionado sejam refletidas imediatamente
            if (method_exists($this, 'clearPermissionCache')) {
                $this->clearPermissionCache();
            }

            // Verifica se há Ente Federado selecionado na sessão
            if (!isset($_SESSION['selectedFederativeEntity'])) {
                return;
            }

            $selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);
            $selectedFederativeEntityId = $selectedEntity['id'] ?? null;
            
            if (!$selectedFederativeEntityId) {
                return;
            }

            // Obtém o agente do usuário logado
            $userAgent = $user->profile;
            if (!$userAgent) {
                return;
            }

            // Obtém o federativeEntityId da opportunity
            $opportunityFederativeEntityId = null;
            if (method_exists($this, 'getMetadata')) {
                $opportunityFederativeEntityId = $this->getMetadata('federativeEntityId');
            }

            // Se a opportunity não tem federativeEntityId, não pode dar permissão
            if (!$opportunityFederativeEntityId) {
                return;
            }

            // Converte para inteiro
            $opportunityFederativeEntityId = (int)$opportunityFederativeEntityId;

            // Busca se o agente do usuário está associado ao mesmo Ente Federado da opportunity
            try {
                $federativeEntityRef = $app->em->getReference('AldirBlanc\Entities\FederativeEntity', $opportunityFederativeEntityId);
                $relation = $app->repo('AldirBlanc\Entities\FederativeEntityAgentRelation')->findOneBy([
                    'owner' => $federativeEntityRef,
                    'agent' => $userAgent,
                    'status' => 1
                ]);

                // Se encontrou a relação e o agente está ativo, concede permissão
                if ($relation && $relation->agent && $relation->agent->status >= 1) {
                    $result = true;
                }
            } catch (\Exception $e) {
                // Se houver erro ao buscar a relação, não concede permissão
                return;
            }
        };

        // Hook para permissão de modificação
        $app->hook('entity(Opportunity).canUser(modify)', $checkFederativeEntityPermission);

        // Hook para permissão de controle
        $app->hook('entity(Opportunity).canUser(@control)', $checkFederativeEntityPermission);

        /**
         * Bloqueia a renderização e a criação de um novo aplicativo
         */
        $app->hook('GET(panel.apps):before', fn() => $this->errorJson(\MapasCulturais\i::__('Acesso não permitido'), 403));
        $app->hook('POST(app.index):before', fn() => $this->errorJson(\MapasCulturais\i::__('Acesso não permitido'), 403));

        /**
         * Verifica se o usuário tem permissão para acessar o menu de oportunidades no painel
         * removendo o link de minhas oportunidades
         */
        $app->hook('panel.nav', function (&$nav) use ($app, $canAccess) {
            if ($app->user->is('GestorCultBr')) {
                $nav['admin']['condition'] = function () {
                    return false;
                };
            }

            // Removendo o menu de "Meus aplicativos"
            $nav['more']['condition'] = fn() => false;

            // Adicionando o menu "Oportunidades do Ente Federado"
            $nav['federativeEntity'] = [
                'condition' => fn() => User::isGestorCultBr(),
                'label' => i::__('Ente Federado'),
                'items' => [
                    [
                        'route' => 'panel/federativeEntityOpportunities',
                        'icon' => 'opportunity',
                        'label' => i::__('Oportunidades'),
                    ],
                    [
                        'route' => 'panel/federativeEntityAgents',
                        'icon' => 'agent',
                        'label' => i::__('Minha Equipe'),
                    ]
                ],
            ];

            if (!$canAccess) {
                $filteredNav = array_filter($nav['opportunities']['items'], function ($item) {
                    return $item['route'] !== 'panel/opportunities';
                });

                $nav['opportunities']['items'] = $filteredNav;
            }
        });

        $this->enqueueStyle('app-v2', 'main', 'css/theme-Pnab.css');

        // Mapeia o ícone do X (antigo Twitter) para o novo logo do X
        $app->hook('component(mc-icon).iconset', function (&$iconset) {
            $iconset['twitter'] = 'simple-icons:x';
        });

        /**
         * Redireciona para /panel após login bem-sucedido
         * Limpa a seleção de entidade federativa quando o usuário faz login
         */
        $app->hook('auth.successful', function () use ($app) {
            // Define o redirect path para /panel na sessão
            $_SESSION['mapasculturais.auth.redirect_path'] = $app->createUrl('panel', 'index');
            
            // Limpa a seleção de entidade federativa
            unset($_SESSION['selectedFederativeEntity']);
            unset($_SESSION['federative_entity_redirect_uri']);
        });

        /**
         * Limpa a seleção de entidade federativa quando o usuário faz logout
         */
        $app->hook('auth.logout:before', function () {
            unset($_SESSION['selectedFederativeEntity']);
            unset($_SESSION['federative_entity_redirect_uri']);
        });

        /**
         * Hook que força o usuário a selecionar uma entidade federativa antes de continuar
         * Captura todas as requisições GET, exceto auth, selectFederativeEntity e changeFederativeEntity
         */
        $app->hook('GET(<<*>>):before,-GET(<<auth>>.<<*>>):before', function () use ($app) {
            if ($app->user->is('guest')) {
                return;
            }

            if (!User::isGestorCultBr()) {
                return;
            }

            $route = [$this->id, $this->action];

            // Ignora as rotas de seleção e alteração
            if ($route[0] === 'aldirblanc' && in_array($route[1], ['selectFederativeEntity', 'changeFederativeEntity'])) {
                return;
            }

            // Verifica se existe entidade federativa selecionada na sessão
            if (!isset($_SESSION['selectedFederativeEntity'])) {
                if (!$app->request->isAjax()) {
                    $_SESSION['federative_entity_redirect_uri'] = $_SERVER['REQUEST_URI'] ?? "";
                }
                $url = $app->createUrl('aldirblanc', 'selectFederativeEntity');
                $app->redirect($url);
            }
        });

        // Adiciona banner com informações do ente federado selecionado
        $app->hook('template(<<*>>.main-header):after', function () use ($app) {
            /** @var \MapasCulturais\Theme $this */
            if (User::isGestorCultBr() && isset($_SESSION['selectedFederativeEntity'])) {
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
            false,
            function (\MapasCulturais\UserInterface $user, $subsite_id) {
                return false;
            },
            [],
        );
        $app->registerRole($def);
    }
}
