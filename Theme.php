<?php

namespace Pnab;

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
        $theme = $this;
        
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

        $app->hook('PATCH(opportunity.single):before', function () use ($theme) {
            $theme->trimOtherValue('etapa', 'etapaOutros', $this->postData);
            $theme->trimOtherValue('pauta', 'pautaOutros', $this->postData);
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

            // Registra metadados select obrigatórios em create
            $theme->registerSelectMetadata('tipoDeEdital', i::__('Tipo de Edital'), $theme->getTipoDeEditalOptions(), 'create');
            
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
        $this->registerOpportunityMetadata($key, [
            'label' => $label,
            'type' => 'select',
            'options' => $options,
            'should_validate' => function($entity) use ($label, $operationType) {
                return $this->redefineRuleValidate($operationType, $entity, $label);
            },
        ]);
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
}
