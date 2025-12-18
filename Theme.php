<?php

namespace Pnab;

use MapasCulturais\i;
use MapasCulturais\App;

/**
 * @method void import(string $components) Importa lista de componentes Vue. * 
 */
// Alteração necessária para rodar o theme-Pnab como submodule do culturagovbr/mapadacultura
// class Theme extends \BaseTheme\Theme
class Theme extends \MapasCulturais\Themes\BaseV2\Theme 
{
    public const ROLES_ALLOWED = [
        'GestorCultBr',
        'saasSuperAdmin',
        'saasAdmin',
        'superAdmin',
        'admin'
    ];

    static function getThemeFolder()
    {
        return __DIR__;
    }

    /**
     * Verifica se o usuário atual da aplicação tem acesso baseado nas roles permitidas
     * 
     * @return bool
     */
    static function canAccess(): bool
    {
        $user = App::i()->user;

        return (bool) array_filter(self::ROLES_ALLOWED, function ($role) use ($user) {
            return $user->is($role);
        });
    }

    function _init()
    {
        parent::_init();
        $app = App::i();
        
        $canAccess = self::canAccess();
        
        /**
         * Verifica se o usuário tem permissão para acessar a rota de minhas oportunidades
         */
        $app->hook('GET(panel.opportunities):before', function () use ($app, $canAccess) {
            if (!$canAccess) {
                $app->pass();
            }
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
         * Verifica se o usuário tem permissão para acessar o menu de oportunidades no painel
         * removendo o link de minhas oportunidades
         */
        $app->hook('panel.nav', function (&$nav) use ($app, $canAccess) {
            if ($app->user->is('GestorCultBr')) {
                $nav['admin']['condition'] = function () { return false; };
            }

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
