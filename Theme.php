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
         * Verifica se o usuário tem permissão para criar uma oportunidade
         */
        $app->hook('POST(opportunity.index):before', function () use ($canAccess) {
            if (!$canAccess) {
                $this->errorJson(\MapasCulturais\i::__('Criação não permitida'), 403);
            }
        });

        /**
         * Hook na API para remover filtros automáticos de user/owner quando há federativeEntityId
         * Permite ver oportunidades publicadas de todos os usuários do mesmo ente federado
         * Sempre aplica status e @permissions para garantir apenas oportunidades publicadas
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

            // Corrige filtros duplicados (caso o componente search adicione operadores extras)
            if (isset($_GET['federativeEntityId']) && preg_match('/^EQ\(EQ\((.+)\)\)$/', $_GET['federativeEntityId'], $matches)) {
                $_GET['federativeEntityId'] = 'EQ(' . $matches[1] . ')';
            }
            if (isset($_GET['status']) && preg_match('/^EQ\(GTE\((.+)\)\)$/', $_GET['status'], $matches)) {
                $_GET['status'] = 'GTE(' . $matches[1] . ')';
            }

            // Também aplica no urlData se existir
            if (isset($this->urlData)) {
                unset($this->urlData['user'], $this->urlData['owner']);
                $this->urlData['status'] = 'GTE(1)';
                $this->urlData['@permissions'] = 'view';

                if (isset($this->urlData['federativeEntityId']) && preg_match('/^EQ\(EQ\((.+)\)\)$/', $this->urlData['federativeEntityId'], $matches)) {
                    $this->urlData['federativeEntityId'] = 'EQ(' . $matches[1] . ')';
                }
                if (isset($this->urlData['status']) && preg_match('/^EQ\(GTE\((.+)\)\)$/', $this->urlData['status'], $matches)) {
                    $this->urlData['status'] = 'GTE(' . $matches[1] . ')';
                }
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
