<?php

namespace Pnab;

use AldirBlanc\Services\UserAccessService;
use AldirBlanc\Services\InMincQuotasService;
use MapasCulturais\i;
use MapasCulturais\App;
use Pnab\Enum\OtherValues;
use Respect\Validation\Validator;
use AldirBlanc\Enum\OpportunityStatus;
use AldirBlanc\Jobs\OportunidadeCultJob;
use MapasCulturais\Entities\Opportunity;
use OpportunityWorkplan\Entities\Delivery as WorkplanDelivery;
use OpportunityWorkplan\Entities\Goal as WorkplanGoalEntity;
use OpportunityWorkplan\Entities\Workplan as WorkplanEntity;

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

    protected const AGENT_COLETIVO_TYPE_ID = 2;
    protected const AGENT_INDIVIDUAL_TYPE_ID = 1;

    /** Opções de "outras modalidades" que exigem sublista de subcategorias (fonte única para PHP e frontend) */
    public const OPTIONS_OTHER_MODALITIES_WITH_SUBLIST = ['bonus_agentes', 'bonus_tematicas', 'categoria_especifica', 'edital_especifico'];

    /** Tamanho máximo do campo nome da fonte em "Recursos de outras fontes". */
    private const RECURSOS_OUTRAS_FONTES_NOME_FONTE_MAX_LENGTH = 255;

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
        $isOpportunityGeneratedFromModel = fn ($entity) => $this->isOpportunityGeneratedFromModel($entity);
        $isCultBrCreateNotYetSynced = fn ($entity) => !$this->isOpportunityCultBrCreateSynced($entity);

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
         * Na edição de agente, o campo "Tipo de Agente Coletivo" não é exibido:
         * uma vez configurado (na criação), o usuário não pode mais alterá-lo.
         */
        // Hook removido: template(agent.edit.entity-info):end + part('agent-edit-tipo-coletivo')

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
         * Dispara o job de integração com o CultBR quando uma oportunidade é inserida
         */
        $app->hook('entity(Opportunity).insert:finish', function () use ($app, $theme) {
            if (!$theme->validateIntegrationJob($this)) {
                return;
            }

            $app->enqueueOrReplaceJob(
                OportunidadeCultJob::SLUG,
                [
                    'action' => 'create',
                    'opportunity' => $this
                ],
            );
        });

        /**
         * Job CultBR no update: Ativado → update (PUT); rascunho com isGeneratedFromModel → create (POST),
         * após validateIntegrationJob (clone «usar modelo» não passa por insert:finish).
         */
        $app->hook('entity(Opportunity).update:finish', function () use ($app, $theme, $isOpportunityGeneratedFromModel, $isCultBrCreateNotYetSynced) {
            if (!$theme->validateIntegrationJob($this)) {
                return;
            }

            // Quando a oportunidade for ativada, disparar o job de update
            if ((int) $this->status === OpportunityStatus::ENABLED->value) {
                $start_string = (new \DateTime())->modify(env('ALDIRBLANC_INTEGRATION_DELAY_JOB', 'now'))->format('Y-m-d H:i:s');

                $app->enqueueOrReplaceJob(
                    OportunidadeCultJob::SLUG,
                    [
                        'action' => 'update',
                        'opportunity' => $this
                    ],
                    $start_string
                );
                return;
            }

            // Rascunho «usar modelo»: um único create até sucesso no Cult; PATCH seguintes não re-enfileiram.
            if ((int) $this->status === OpportunityStatus::DRAFT->value
                && $isOpportunityGeneratedFromModel($this)
                && $isCultBrCreateNotYetSynced($this)
            ) {
                $app->enqueueOrReplaceJob(
                    OportunidadeCultJob::SLUG,
                    [
                        'action' => 'create',
                        'opportunity' => $this,
                    ],
                );
            }
        });

        /**
         * Garante que o metadado publishedTimestamp seja definido quando a oportunidade for publicada
         * (salvo na mesma transação do save/publish)
         */
        $app->hook('entity(Opportunity).save:before', function () {
            /** @var \MapasCulturais\Entities\Opportunity $this */
            if ((int) $this->status === Opportunity::STATUS_ENABLED && !$this->getMetadata('publishedTimestamp')) {
                $this->setMetadata('publishedTimestamp', (new \DateTime())->format('Y-m-d H:i:s'));
            }
        });

        $app->hook('PATCH(opportunity.single):before', function () use ($theme) {
            $entity = $this->requestedEntity;
            $postData = $this->postData;

            // Acumula erros de vagas e de valores para poder retornar ambos ao mesmo tempo
            $rangeErrors = [];
            foreach (self::METADATA_RANGE_SUM_KEYS as $metadataKey => $keyTarget) {
                $totalByMetadata = $theme->validateTotalByMetadata($entity, $postData, $metadataKey, $keyTarget);
                if (is_array($totalByMetadata) && !empty($totalByMetadata)) {
                    $rangeErrors = array_merge_recursive($rangeErrors, $totalByMetadata);
                }
            }

            if (!empty($rangeErrors)) {
                $this->errorJson($rangeErrors, 400);
            }

            $theme->trimOtherValue('etapa', 'etapaOutros', $postData);
            $theme->trimOtherValue('pauta', 'pautaOutros', $postData);
            $theme->trimSegmentoOutros($postData);

            // PATCH parcial (ex.: pós "usar modelo": só descrição + PAR) não envia cotas; validar só quando o body altera dados que afetam a regra IN-MinC.
            $touchesQuotasOrVacancies = false;
            foreach (['reservaVagasCotas', 'vacancies', 'registrationRanges'] as $key) {
                if (array_key_exists($key, $postData)) {
                    $touchesQuotasOrVacancies = true;
                    break;
                }
            }
            if ($touchesQuotasOrVacancies) {
                $quotasReservationErrors = InMincQuotasService::validateQuotasReservation($entity, $postData);
                if ($quotasReservationErrors) {
                    $this->errorJson($quotasReservationErrors, 400);
                }
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
                $opportunityIds = [];
            }

            // Aba "Meus modelos": incluir também modelos oficiais (isModel=1 com selo verificado),
            // que não possuem federativeEntityId e por isso não entram na lista do ente
            $isModelsTab = isset($api_params['isModel']) &&
                preg_match('/^EQ\(\s*1\s*\)$/i', trim((string)$api_params['isModel'])) &&
                isset($api_params['status']) &&
                preg_match('/^EQ\(\s*-1\s*\)$/i', trim((string)$api_params['status']));
            if ($isModelsTab) {
                $verifiedSealsIds = $app->config['app.verifiedSealsIds'] ?? [];
                if (is_numeric($verifiedSealsIds)) {
                    $verifiedSealsIds = [(int)$verifiedSealsIds];
                } elseif (!is_array($verifiedSealsIds)) {
                    $verifiedSealsIds = [];
                }
                if (!empty($verifiedSealsIds)) {
                    $placeholders = implode(',', array_fill(0, count($verifiedSealsIds), '?'));
                    $sqlOfficial = "SELECT DISTINCT o.id 
                        FROM opportunity o
                        INNER JOIN opportunity_meta m ON m.object_id = o.id AND m.key = 'isModel' AND m.value = '1'
                        INNER JOIN seal_relation sr ON sr.object_id = o.id AND sr.object_type = 'MapasCulturais\\Entities\\Opportunity'
                        WHERE sr.seal_id IN ($placeholders)";
                    try {
                        $officialResults = $conn->executeQuery($sqlOfficial, array_values($verifiedSealsIds))->fetchAll();
                        $officialIds = array_map(fn($r) => (int)$r['id'], $officialResults);
                        $opportunityIds = array_values(array_unique(array_merge($opportunityIds, $officialIds)));
                    } catch (\Exception $e) {
                        // mantém apenas os do ente em caso de erro
                    }
                }
            }
            
            // Aplica filtro: se não houver nenhum ID permitido, retorna filtro que não encontra nada
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
         * A action findOpportunitiesModels retorna um array de objetos com estrutura: {id, descricao, numeroFases, modelIsOfficial, ...}
         * Modelos oficiais (modelIsOfficial === true) são sempre exibidos para o GestorCultBr, pois não possuem federativeEntityId.
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

            // Busca IDs dos modelos do ente federado (com metadado federativeEntityId)
            // Inclui modelos cuja oportunidade principal tem o metadado
            $conn = $app->em->getConnection();
            $params = [
                'meta_key' => 'federativeEntityId',
                'federativeEntityId' => (string)$federativeEntityId
            ];
            
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
                $allowedModelIds = [];
            }
            
            // Filtra o resultado: mantém modelos do ente OU modelos oficiais (sem federativeEntityId)
            $result = array_filter($result, function ($model) use ($allowedModelIds) {
                if (!isset($model['id'])) {
                    return false;
                }
                $id = (int)$model['id'];
                $isFromEntity = in_array($id, $allowedModelIds);
                $isOfficial = !empty($model['modelIsOfficial']);
                return $isFromEntity || $isOfficial;
            });
            $result = array_values($result);
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
         * Impede alteração do metadado tipoAgenteColetivo após a criação do agente.
         * Se a entidade já existe, restaura o valor atual do banco antes de persistir.
         */
        $app->hook('entity(Agent).save:before', function () use ($app) {
            /** @var \MapasCulturais\Entities\Agent $this */
            if ($this->isNew()) {
                return;
            }
            $meta = $app->repo('AgentMeta')->findOneBy(['owner' => $this, 'key' => 'tipoAgenteColetivo']);
            if ($meta !== null && $meta->value !== null && $meta->value !== '') {
                $this->setMetadata('tipoAgenteColetivo', $meta->value);
            }

            if (($this->tipoAgenteColetivo ?? '') === 'coletivos_grupos_informais') {
                $qtdMeta = $app->repo('AgentMeta')->findOneBy(['owner' => $this, 'key' => 'qtdMembrosColetivo']);
                $current = $this->qtdMembrosColetivo;
                $invalid = $current === null || $current === '' || (is_numeric($current) && (int) $current < 2);
                if ($invalid && $qtdMeta !== null && $qtdMeta->value !== null && $qtdMeta->value !== '' && (int) $qtdMeta->value >= 2) {
                    $this->setMetadata('qtdMembrosColetivo', $qtdMeta->value);
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
        $app->hook('GET(panel.apps):before', function () {
            // Libera para SuperSaasAdmin, se não for, bloqueia
            if (!UserAccessService::isSaasSuperAdmin()) {
                $this->errorJson(\MapasCulturais\i::__('Acesso não permitido'), 403);
            }
        });
        $app->hook('POST(app.index):before', function () {
            // Libera para SuperSaasAdmin, se não for, bloqueia
            if (!UserAccessService::isSaasSuperAdmin()) {
                $this->errorJson(\MapasCulturais\i::__('Acesso não permitido'), 403);
            }
        });

        /**
         * Configura o menu do painel: renomeia "Minhas Oportunidades" e move para "Ente Federado"
         */
        $app->hook('panel.nav', function (&$nav) use ($app, $canAccess) {
            // "Meus aplicativos" visível apenas para saasSuperAdmin
            $nav['more']['condition'] = fn() => UserAccessService::isSaasSuperAdmin();

            // Usuário sem canAccess não vê "Minhas Oportunidades" (apenas GestorCultBr pode criar/acessar a página)
            if (!$canAccess && isset($nav['opportunities']['items'])) {
                foreach ($nav['opportunities']['items'] as $key => $item) {
                    if (isset($item['route']) && $item['route'] === 'panel/opportunities') {
                        $nav['opportunities']['items'][$key]['condition'] = fn() => false;
                    }
                }
            }

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

        /**
         * Validação de e-mail (formas de inscrição) no blur — usa o mesmo padrão do backend (Respect\Validation).
         */
        $app->hook('POST(site.validaEmailFormasInscricao)', function () {
            $this->requireAuthentication();
            $email = trim((string) ($this->data['email'] ?? ''));
            if ($email === '') {
                $this->json(['valid' => true]);
                return;
            }
            $valid = Validator::email()->validate($email);
            $this->json([
                'valid' => $valid,
                'message' => $valid ? '' : i::__('Informe um e-mail válido.'),
            ]);
        });

        // Mapeia o ícone do X (antigo Twitter) para o novo logo do X
        $app->hook('component(mc-icon).iconset', function (&$iconset) {
            $iconset['twitter'] = 'simple-icons:x';
        });

        /**
         * Redireciona para consolidação após login bem-sucedido
         * Limpa a seleção de entidade federativa quando o usuário faz login
         * Não redireciona admins (não há o que consolidar)
         * Se o agente tem isNotGestorCultBr (API já retornou vazio), redireciona para o painel (2º+ login)
         */
        $app->hook('auth.successful', function () use ($app) {
            // Se for admin em qualquer nível, não precisa consolidar dados
            if (UserAccessService::isAdmin()) {
                return;
            }

            $profile = $app->user->profile;
            if ($profile && $profile->getMetadata('isNotGestorCultBr')) {
                // 2º ou N-ésimo login: API já retornou vazio, não passar pela consolidação
                $_SESSION['mapasculturais.auth.redirect_path'] = $app->createUrl('panel', 'index');
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
         * Captura todas as requisições GET e POST, exceto auth, consolidatingData, startSync, checkSyncStatus, logoutOnError, selectFederativeEntity, changeFederativeEntity, federativeEntities, saveOpportunityPostGenerate, etc.
         * Não bloqueia admins (não há o que consolidar)
         * Não bloqueia admin em modo "login como usuário" (plugin AdminLoginAsUser): o $app->user vira o impersonado,
         * então isAdmin() falha e LGPD/termos ou auth.asUserId podiam ser afetados indevidamente.
         */
        $theme = $this;
        $blockAccessOnError = function () use ($app, $theme) {
            if ($app->user->is('guest')) {
                return;
            }

            $controllerId = $app->request->controllerId ?? '';
            $action = $app->request->action ?? '';

            // Voltar como administrador / alternar usuário (AdminLoginAsUser — ex.: mc-link route auth/asUserId)
            if ($controllerId === 'auth' && $action === 'asUserId') {
                return;
            }

            // Sessão ativa de impersonação: não aplicar consolidação/perfil/ente ao usuário "visto" pelo admin
            if (isset($_SESSION['auth.asUserId'])) {
                return;
            }

            // Se for admin em qualquer nível, não precisa consolidar dados
            if (UserAccessService::isAdmin()) {
                return;
            }

            if ($controllerId === 'lgpd') {
                return;
            }

            if ($controllerId === 'aldirblanc' && in_array($action, ['consolidatingData', 'startSync', 'selectFederativeEntity', 'completeProfile', 'changeFederativeEntity', 'checkSyncStatus', 'federativeEntities', 'parExercicios', 'logoutOnError', 'saveOpportunityPostGenerate'])) {
                return;
            }

            $path = trim($app->request->getPathInfo(), '/');
            if ($path === 'termos-e-condicoes' || str_starts_with($path, 'termos-e-condicoes/')) {
                return;
            }

            $profile = $app->user->profile;
            $route = [$this->id, $this->action];

            // Ignora as rotas de consolidação, sync, seleção, complementar perfil, alteração, verificação de status e busca de entes federados
            if ($route[0] === 'aldirblanc' && in_array($route[1], ['consolidatingData', 'startSync', 'selectFederativeEntity', 'completeProfile', 'changeFederativeEntity', 'checkSyncStatus', 'federativeEntities', 'parExercicios', 'logoutOnError', 'saveOpportunityPostGenerate'])) {
                return;
            }

            // Ordem: (1) consolidação, (2) perfil incompleto, (3) gestor sem ente

            // 1. Consolidação: só aplica a quem NÃO tem isNotGestorCultBr (2º+ login sem gestor pula)
            $syncStarted = isset($_SESSION['gestor_cult_sync_started']) && $_SESSION['gestor_cult_sync_started'] === true;
            $syncCompleted = isset($_SESSION['gestor_cult_sync_completed']) && $_SESSION['gestor_cult_sync_completed'] === true;
            $hasError = isset($_SESSION['gestor_cult_sync_error']) &&
                $_SESSION['gestor_cult_sync_error'] !== null &&
                $_SESSION['gestor_cult_sync_error'] !== '';

            if (!$profile || !$profile->getMetadata('isNotGestorCultBr')) {
                // Se há erro de sync, bloqueia e redireciona para consolidação
                if ($syncCompleted && $hasError) {
                    if ($app->request->isAjax()) {
                        header('Content-Type: application/json');
                        http_response_code(403);
                        echo json_encode([
                            'error' => true,
                            'message' => 'Não foi possível consolidar seus dados. Você será desconectado.',
                            'redirectTo' => $app->createUrl('aldirblanc', 'consolidatingData')
                        ]);
                        exit;
                    }
                    $_SESSION['federative_entity_redirect_uri'] = $_SERVER['REQUEST_URI'] ?? "";
                    $app->redirect($app->createUrl('aldirblanc', 'consolidatingData'));
                    return;
                }

                // Se o sync foi iniciado mas não foi concluído, redireciona para consolidação
                if ($syncStarted && !$syncCompleted) {
                    if (!$app->request->isAjax()) {
                        $_SESSION['federative_entity_redirect_uri'] = $_SERVER['REQUEST_URI'] ?? "";
                    }
                    $app->redirect($app->createUrl('aldirblanc', 'consolidatingData'));
                    return;
                }
            }

            // 2. Perfil incompleto (agente individual ou tipo não definido): redireciona para tela de complementar cadastro
            $profileTypeId = $profile && is_object($profile->type) ? ($profile->type->id ?? null) : ($profile->type ?? null);
            $isIndividual = $profile && (($profileTypeId === null) || ((int) $profileTypeId === self::AGENT_INDIVIDUAL_TYPE_ID));
            if ($isIndividual && method_exists($theme, 'hasRequiredAgentFieldsFilled')) {
                // Usa instância recarregada do banco para evitar perfil em memória/sessão sem __metadata carregado (getMetadata retorna null se __metadata não for iterável)
                $profileToCheck = $profile->refreshed();
                if (!$theme->hasRequiredAgentFieldsFilled($profileToCheck)) {
                    if (!$app->request->isAjax()) {
                        $app->redirect($app->createUrl('aldirblanc', 'completeProfile'));
                    } else {
                        header('Content-Type: application/json');
                        http_response_code(403);
                        echo json_encode([
                            'error' => true,
                            'message' => 'Complete seu cadastro para continuar.',
                            'redirectTo' => $app->createUrl('aldirblanc', 'completeProfile'),
                        ]);
                        exit;
                    }
                    return;
                }
            }

            // 3. Gestor sem ente: após consolidação ok (ou isNotGestorCultBr), exige seleção de ente
            if ($syncCompleted && !$hasError && UserAccessService::isGestorCultBr()) {
                if (!isset($_SESSION['selectedFederativeEntity'])) {
                    if (!$app->request->isAjax()) {
                        $_SESSION['federative_entity_redirect_uri'] = $_SERVER['REQUEST_URI'] ?? "";
                    }
                    $app->redirect($app->createUrl('aldirblanc', 'selectFederativeEntity'));
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
         * Validação de oportunidade:
         * - Torna o campo "Descrição curta" obrigatório em qualquer fase
         * - Torna o campo "Tipos do proponente" obrigatório nas fases de edição (não novas e não últimas fases)
         */
        $app->hook('entity(Opportunity).validations', function (&$validations) {
            /** @var \MapasCulturais\Entities\Opportunity $this */

            // Descrição curta obrigatória (inclui criação via modal)
            $validations['shortDescription'] = [
                'required' => i::__('O campo "Descrição curta" é obrigatório.')
            ];

            // Tipos do proponente obrigatórios apenas em edição, como já era feito antes
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

                // Validação: Utilização de recursos de outras fontes
                $recursos = self::ensureArray($this->recursosOutrasFontes);
                $houve = $recursos['houveUtilizacao'] ?? '';
                if ($houve !== 'sim' && $houve !== 'nao') {
                    $errors['recursosOutrasFontes'] = [i::__('O campo "Houve utilização de recursos de outras fontes?" é obrigatório.')];
                } elseif ($houve === 'sim') {
                    $recursosProprios = $recursos['recursosProprios'] ?? null;
                    $conveniosParcerias = $recursos['conveniosParcerias'] ?? null;
                    $emendasParlamentares = $recursos['emendasParlamentares'] ?? null;
                    $remanescentesCiclo1 = $recursos['remanescentesCiclo1'] ?? null;
                    $outrasFontes = $recursos['outrasFontes'] ?? null;
                    $algumaMarcada = $recursosProprios !== null || $conveniosParcerias !== null
                        || $emendasParlamentares !== null || $remanescentesCiclo1 !== null
                        || (is_array($outrasFontes) && count($outrasFontes) > 0);
                    if (!$algumaMarcada) {
                        $errors['recursosOutrasFontes'] = [i::__('Selecione pelo menos uma fonte de recurso para continuar.')];
                    } elseif (is_array($outrasFontes) && count($outrasFontes) > 0) {
                        $algumaComNome = false;
                        foreach ($outrasFontes as $entrada) {
                            if (!empty(trim((string) ($entrada['nomeFonte'] ?? '')))) {
                                $algumaComNome = true;
                                break;
                            }
                        }
                        if (!$algumaComNome) {
                            $errors['recursosOutrasFontes'] = [i::__('Preencha o nome de pelo menos uma fonte em "Recursos de outras fontes".')];
                        }
                    }
                }

                // Validação: Formas de inscrição previstas no edital
                $formasInscricao = self::ensureArray($this->formasInscricaoEdital);
                $previstas = $formasInscricao['previstasNoEdital'] ?? '';
                if ($previstas !== 'sim' && $previstas !== 'nao') {
                    $errors['formasInscricaoEdital'] = [i::__('O campo "Formas de inscrição previstas no edital" é obrigatório.')];
                } elseif ($previstas === 'sim') {
                    $formas = $formasInscricao['formas'] ?? null;
                    if (!is_array($formas) || count($formas) === 0) {
                        $errors['formasInscricaoEdital'] = [i::__('Selecione pelo menos uma forma de inscrição para continuar.')];
                    } else {
                        foreach ($formas as $item) {
                            $descricao = trim((string) ($item['descricao'] ?? ''));
                            if ($descricao === '') {
                                $errors['formasInscricaoEdital'] = [i::__('Preencha a descrição de cada forma de inscrição marcada.')];
                                break;
                            }
                            $tipo = $item['tipo'] ?? '';
                            if ($tipo === 'email' && !Validator::email()->validate($descricao)) {
                                $errors['formasInscricaoEdital_email'] = [i::__('Informe um e-mail válido.')];
                                break;
                            }
                        }
                    }
                }

                // Validação: Outras modalidades de ações afirmativas
                $outrasModalidades = self::ensureArray($this->outrasModalidadesAcoesAfirmativas);
                $opcoes = $outrasModalidades['opcoes'] ?? [];
                if (!is_array($opcoes) || count($opcoes) === 0) {
                    $errors['outrasModalidadesAcoesAfirmativas'] = [i::__('Selecione pelo menos uma opção.')];
                } else {
                    foreach (self::OPTIONS_OTHER_MODALITIES_WITH_SUBLIST as $op) {
                        if (in_array($op, $opcoes)) {
                            $sublist = $outrasModalidades[$op] ?? null;
                            if (!is_array($sublist) || count($sublist) === 0) {
                                $errors['outrasModalidadesAcoesAfirmativas'] = [i::__('Por favor, selecione pelo menos uma subcategoria.')];
                                break;
                            }
                        }
                    }
                    if (empty($errors['outrasModalidadesAcoesAfirmativas']) && in_array('outra_legislacao', $opcoes)) {
                        $descricao = trim((string) ($outrasModalidades['outra_legislacao_descricao'] ?? ''));
                        if ($descricao === '') {
                            $errors['outrasModalidadesAcoesAfirmativas'] = [i::__('Por favor, preencha a descrição.')];
                        }
                    }
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

                // Recursos de outras fontes: incluir no payload para validação e sanitizar quando enviado
                if (!array_key_exists('recursosOutrasFontes', $data)) {
                    $data['recursosOutrasFontes'] = $entity->recursosOutrasFontes ?? null;
                    $this->postData['recursosOutrasFontes'] = $data['recursosOutrasFontes'];
                } else {
                    $app = \MapasCulturais\App::i();
                    $theme = $app->view;
                    if (method_exists($theme, 'sanitizeRecursosOutrasFontes')) {
                        $theme->sanitizeRecursosOutrasFontes($data);
                    }
                }

                // Formas de inscrição previstas no edital: incluir no payload para validação
                if (!array_key_exists('formasInscricaoEdital', $data)) {
                    $data['formasInscricaoEdital'] = $entity->formasInscricaoEdital ?? null;
                    $this->postData['formasInscricaoEdital'] = $data['formasInscricaoEdital'];
                }

                // Outras modalidades de ações afirmativas: incluir no payload para validação
                if (!array_key_exists('outrasModalidadesAcoesAfirmativas', $data)) {
                    $data['outrasModalidadesAcoesAfirmativas'] = $entity->outrasModalidadesAcoesAfirmativas ?? null;
                    $this->postData['outrasModalidadesAcoesAfirmativas'] = $data['outrasModalidadesAcoesAfirmativas'];
                }
            }
        });

        /**
         * Garante que os campos obrigatórios do agente coletivo estejam no payload.
         * Assim a validação roda e retorna erro nos campos que faltaram.
         */
        $agentColetivoTypeId = self::AGENT_COLETIVO_TYPE_ID;
        $agentIndividualTypeId = self::AGENT_INDIVIDUAL_TYPE_ID;
        $app->hook('PATCH(agent.single):data', function (&$data) use ($app, $agentColetivoTypeId, $agentIndividualTypeId) {
            /** @var \MapasCulturais\Controllers\Agent $this */
            $theme = $app->view;
            $entity = $this->requestedEntity;

            // Tipo de Agente Coletivo não pode ser alterado após a criação: remove do payload.
            if ($entity && !$entity->isNew()) {
                unset($data['tipoAgenteColetivo']);
            }

            if (!method_exists($theme, 'getRequeredsAgentColetivoMetadata')) {
                return;
            }
            if (!$entity || $entity->isNew()) {
                return;
            }
            $typeId = is_object($entity->type) ? ($entity->type->id ?? null) : $entity->type;
            $typeId = $typeId === null ? null : (int) $typeId;

            if ($typeId === $agentColetivoTypeId && method_exists($theme, 'getRequeredsAgentColetivoMetadata')) {
                foreach ($theme->getRequeredsAgentColetivoMetadata() as $key) {
                    if (!array_key_exists($key, $data)) {
                        $data[$key] = $entity->$key ?? null;
                        $this->postData[$key] = $data[$key];
                    }
                }
                return;
            }

            if ($typeId === $agentIndividualTypeId && method_exists($theme, 'getRequeredsAgentIndividualMetadata')) {
                foreach ($theme->getRequeredsAgentIndividualMetadata() as $key) {
                    if (!array_key_exists($key, $data)) {
                        $data[$key] = $entity->$key ?? null;
                        $this->postData[$key] = $data[$key];
                    }
                }
            }

            // Coletivos informais: se qtdMembrosColetivo vier ausente no payload (front costuma omitir 0), forçar 0 para a validação rodar e bloquear o save.
            if (($entity->tipoAgenteColetivo ?? '') === 'coletivos_grupos_informais' && !array_key_exists('qtdMembrosColetivo', $data)) {
                $data['qtdMembrosColetivo'] = 0;
                $this->postData['qtdMembrosColetivo'] = 0;
            }
        });

        /**
         * Garante que campos com erro no postData estejam no payload para exibição na edição.
         */
        $app->hook('entity(Agent).validationErrors', function (array &$errors) use ($app) {
            /** @var \MapasCulturais\Entities\Agent $this */
            if (!empty($errors)) {
                $controller = $app->controller('agent');
                if ($controller && isset($controller->postData)) {
                    foreach ($errors as $field => $fieldErrors) {
                        if (!array_key_exists($field, $controller->postData)) {
                            $controller->postData[$field] = $this->$field ?? null;
                        }
                    }
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
            $this->jsObject['canAccessOpportunitiesPanel'] = UserAccessService::canAccess();
        });

        /**
         * Registra metadados de oportunidade: Segmento, Etapa, Pauta e Território
         * Tenta reutilizar as opções do OpportunityWorkplan quando disponível
         * Usa app.init:after para garantir que os metadados do core já foram registrados
         */
        $theme = $this;
        $app->hook('app.init:after', function() use ($app, $theme) {
            // Listas brutas (themes/Pnab/opportunity-types.php → metadata; antes de enriquecer multiselects do edital).
            $opportunitySegmentoOptionsForWorkplan = $app->getRegisteredMetadataByMetakey('segmento', Opportunity::class)?->options ?? [];
            $opportunityEtapaOptionsForWorkplan = $app->getRegisteredMetadataByMetakey('etapa', Opportunity::class)?->options ?? [];

            // Registra metadados multiselect obrigatórios em edit (segmento, pauta, etapa, território)
            $theme->registerMultiselectMetadata('segmento', i::__('Segmento artistico-cultural'), $theme->getSegmentoOptions(), 'edit');
            $theme->registerMultiselectMetadata(
                'etapa',
                i::__('Etapa do fazer cultural'),
                $theme->enrichMultiselectOptions(
                    $opportunityEtapaOptionsForWorkplan,
                    i::__('Outra (especificar)'),
                    null,
                    false
                ),
                'edit'
            );
            $theme->registerMultiselectMetadata('pauta', i::__('Pauta temática'), $theme->getPautaOptions(), 'edit');
            $theme->registerMultiselectMetadata('territorio', i::__('Território'), $theme->getTerritorioOptions(), 'edit');

            // Registra metadados select obrigatórios em required
            $theme->registerSelectMetadata('tipoDeEdital', i::__('Tipo de Edital'), $theme->getTipoDeEditalOptions(), 'required');
            
            // Registra campos "Outros" para especificar quando "Outra" for selecionada
            $theme->registerOutrosMetadata('etapaOutros', i::__('Especificar etapa do fazer cultural'), 'etapa', 'etapaOutros');
            $theme->registerOutrosMetadata('pautaOutros', i::__('Especificar pauta temática'), 'pauta', 'pautaOutros');
            $theme->registerSegmentoOutrosMetadata();

            // Plano de metas (PNAB): mesmas opções que opportunity-types em `segmento` e `etapa` (sem chaves sintéticas do multiselect do edital).
            $theme->registerWorkplanSegmentMetadataForPnab($app, $opportunitySegmentoOptionsForWorkplan);
            $theme->registerWorkplanEtapaMetadataForPnab($app, $opportunityEtapaOptionsForWorkplan);

            // Metadado: utilização de recursos de outras fontes (objeto; validação via hooks)
            $theme->registerOpportunityMetadata('recursosOutrasFontes', [
                'label' => i::__('Houve utilização de recursos de outras fontes?'),
                'type' => 'json',
            ]);

            // Metadado: reserva de vagas (cotas)
            $theme->registerOpportunityMetadata('reservaVagasCotas', [
                'label' => i::__('Reserva de vagas (cotas)'),
                'type' => 'json',
            ]);

            // Metadado: formas de inscrição previstas no edital
            $theme->registerOpportunityMetadata('formasInscricaoEdital', [
                'label' => i::__('Formas de inscrição previstas no edital'),
                'type' => 'json',
            ]);

            // Metadado: outras modalidades de ações afirmativas
            $theme->registerOpportunityMetadata('outrasModalidadesAcoesAfirmativas', [
                'label' => i::__('Outras modalidades de ações afirmativas'),
                'type' => 'json',
            ]);

            // Registra metadados de agente
            $theme->registerAgentMetadataByType(
                'acessouFomentoCultural', 
                i::__('Acessou Recursos Públicos de Fomento à Cultura nos Últimos 5 (Cinco) Anos?'), 
                'select', 
                null, 
                $theme->getAcessoFomentoCulturalOptions(), 
                []
            );

            $theme->registerAgentMetadataByType(
                'anosExperienciaAreaCultural',
                i::__('Anos de Atuação na Área Cultural?'),
                'number',
                null,
                [],
                []
            );

            $theme->registerAgentMetadataByType(
                'qtdMembrosColetivo',
                i::__('Quantas pessoas fazem parte do coletivo?'),
                'number',
                null,
                [],
                ['v::numericVal()->min(2)' => i::__('Informe pelo menos 2 membros.')]
            );

            $theme->registerAgentMetadataByType(
                'eMestreCulturasTradicionais',
                i::__('É mestre ou mestra das culturas tradicionais ou populares?'),
                'boolean',
                1,
                [],
                []
            );

            // Detalhamento do tipo de agente coletivo (apenas type=2 Coletivo).
            $theme->registerAgentMetadataByType(
                'tipoAgenteColetivo',
                i::__('Tipo de agente coletivo'),
                'select',
                self::AGENT_COLETIVO_TYPE_ID,
                [
                    'pj_fins_lucrativos' => i::__('Pessoa jurídica com fins lucrativos'),
                    'pj_sem_fins_lucrativos' => i::__('Pessoa jurídica sem fins lucrativos'),
                    'coletivos_grupos_informais' => i::__('Coletivos e grupos informais'),
                ],
                [
                    'required' => i::__('O tipo de agente coletivo é obrigatório'),
                ]
            );

            $agentClass = 'MapasCulturais\Entities\Agent';
            $tipoColetivoId = self::AGENT_COLETIVO_TYPE_ID;

            $definitions = $app->getRegisteredMetadata($agentClass, $tipoColetivoId);
            if (empty($definitions)) {
                return;
            }

            foreach ($definitions as $metaKey => $def) {
                if (!in_array($metaKey, $theme->getRequeredsAgentColetivoMetadata())) {
                    continue;
                }

                $def->config['should_validate'] = function ($entity, $value) use ($def) {
                    if ($entity->isNew()) {
                        return false;
                    }
                    $vazio = $value === null || $value === '' || (is_array($value) && empty($value));
                    if ($vazio) {
                        return i::__('O campo ') . strtolower($def->label) . i::__(' é obrigatório para agente coletivo.');
                    }
                    return false;
                };
            }

            // qtdMembrosColetivo: obrigatório para coletivos informais; não pode ser nulo; deve ser > 1 (coletivo = plural)
            if (isset($definitions['qtdMembrosColetivo'])) {
                $def = $definitions['qtdMembrosColetivo'];
                $def->config['should_validate'] = function ($entity, $value) use ($def) {
                    if ($entity->isNew() || ($entity->tipoAgenteColetivo ?? '') !== 'coletivos_grupos_informais') {
                        return false;
                    }
                    $vazio = $value === null || $value === '' || (is_array($value) && empty($value)) || $value === 0 || $value === '0';
                    if ($vazio) {
                        return i::__('O campo ') . strtolower($def->label) . i::__(' é obrigatório para coletivos e grupos informais.');
                    }
                    return false;
                };
            }

            // Labels de endereço para agente coletivo conforme dicionário (1ª coluna)
            $addressLabelsColetivo = [
                'En_CEP' => i::__('CEP da Sede'),
                'En_Municipio' => i::__('Cidade'),
            ];
            foreach ($addressLabelsColetivo as $metaKey => $newLabel) {
                $def = $definitions[$metaKey] ?? null;
                if ($def !== null) {
                    $config = array_merge([], $def->config);
                    $config['label'] = $newLabel;
                    $app->registerMetadata(new \MapasCulturais\Definitions\Metadata($metaKey, $config), $agentClass, $tipoColetivoId);
                }
            }

            $tipoIndividualId = self::AGENT_INDIVIDUAL_TYPE_ID;
            $definitionsIndividual = $app->getRegisteredMetadata($agentClass, $tipoIndividualId);
            if (!empty($definitionsIndividual)) {
                foreach ($definitionsIndividual as $metaKey => $def) {
                    if (!in_array($metaKey, $theme->getRequeredsAgentIndividualMetadata())) {
                        continue;
                    }

                    $def->config['should_validate'] = function ($entity, $value) use ($def) {
                        if ($entity->isNew()) {
                            return false;
                        }
                        $vazio = $value === null || $value === '' || (is_array($value) && empty($value));
                        if ($vazio) {
                            return i::__('O campo ') . strtolower($def->label) . i::__(' é obrigatório para agente individual.');
                        }
                        return false;
                    };
                }
            }
        });
    }

    /**
     * Registra um metadado do tipo multiselect obrigatório
     *
     * @param string $key Chave do metadado
     * @param string $label Label do campo (já traduzido)
     * @param array $options Opções do select
     * @param string $operationType Tipo de operação (edit ou create)
     */
    private function registerMultiselectMetadata(string $key, string $label, array $options, string $operationType): void
    {
        $metadataValues = [
            'label' => $label,
            'type' => 'multiselect',
            'options' => $options,
        ];

        $metadataValues['should_validate'] = function ($entity, $value) use ($label, $operationType) {
            return $this->redefineRuleValidateMultiselect($operationType, $entity, $label, $value);
        };

        $this->registerOpportunityMetadata($key, $metadataValues);
    }

    /**
     * Regra de validação para metadado multiselect obrigatório (array não vazio)
     *
     * @param string $operationType Tipo de operação (edit ou create)
     * @param \MapasCulturais\Entity $entity Entidade que contém os campos
     * @param string $label Label do campo (já traduzido)
     * @param mixed $value Valor atual (array para multiselect)
     * @return string|false Mensagem de erro se inválido, false se válido
     */
    private function redefineRuleValidateMultiselect(string $operationType, $entity, string $label, $value)
    {
        $isEmpty = $value === null || $value === '' || (is_array($value) && count($value) === 0);

        if (!$isEmpty) {
            return false;
        }

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
     * Registra o metadado "segmentoOutros" para especificar quando "Outros" for selecionado no segmento
     */
    private function registerSegmentoOutrosMetadata(): void
    {
        $theme = $this;
        $this->registerOpportunityMetadata('segmentoOutros', [
            'label' => i::__('Especificar segmento artístico-cultural'),
            'type' => 'string',
            'should_validate' => function ($entity, $value) use ($theme) {
                $segmento = $entity->segmento ?? [];
                if (!is_array($segmento)) {
                    return false;
                }
                $opcoes = $theme->getSegmentoOptions();
                $outrosKey = array_search(i::__('Outros (especificar)'), $opcoes, true);
                if ($outrosKey === false || !in_array($outrosKey, $segmento, true)) {
                    return false;
                }
                $valorAtual = ($value !== null && $value !== '') ? $value : ($entity->segmentoOutros ?? null);
                if ($valorAtual === null || $valorAtual === '' || trim((string) $valorAtual) === '') {
                    return i::__('O campo ') . strtolower(i::__('Especificar segmento artístico-cultural')) . i::__(' é obrigatório quando "Outros (especificar)" é selecionado.');
                }
                return false;
            },
        ]);
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

    private function registerAgentMetadataByType(string $key, string $label, string $typeMetadata, ?int $agentTypeId, array $options = [], array $validations = []): void
    {
        $app = App::i();

        $config = [
            'label' => $label,
            'type' => $typeMetadata,
        ];

        if ($typeMetadata === 'select') {
            $config['options'] = $options;
        }

        if ($validations !== []) {
            $config['validations'] = $validations;
        }

        $def = new \MapasCulturais\Definitions\Metadata($key, $config);
        $app->registerMetadata($def, 'MapasCulturais\Entities\Agent', $agentTypeId);
    }

    /**
     * Valida campo "Outros" quando o campo principal contém "outra"
     * Suporta campo principal como string (select) ou array (multiselect)
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

        $contemOutra = false;
        if (is_array($valorPrincipal)) {
            foreach ($valorPrincipal as $v) {
                if ($v && stripos((string) $v, 'outra') !== false) {
                    $contemOutra = true;
                    break;
                }
            }
        } else {
            $contemOutra = $valorPrincipal && stripos((string) $valorPrincipal, 'outra') !== false;
        }

        if (!$contemOutra) {
            return false;
        }

        $valorAtual = ($value !== null && $value !== '') ? $value : ($entity->{$campoOutros} ?? null);

        if ($valorAtual === null || $valorAtual === '' || trim((string) $valorAtual) === '') {
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
     * Prepara opções de multiselect: coloca uma opção "Outros/Outra" por último (opcional)
     * e adiciona no início as opções especiais "Edital não se direciona" e, opcionalmente, "Todas as opções".
     *
     * @param array<string, string> $baseOptions Opções base (key => label)
     * @param string|null $moveToEndLabel Label da opção a colocar por último (ex: 'Outros', 'Outra (especificar)')
     * @param string|null $endLabelOverride Label final para essa opção (ex: 'Outros (especificar)'); se null, mantém o label original
     * @param bool $includeTodasOpcoes Incluir a opção "Todas as opções" (apenas Segmento; Pauta, Etapa e Território usam false)
     * @return array<string, string>
     */
    private function enrichMultiselectOptions(array $baseOptions, ?string $moveToEndLabel = null, ?string $endLabelOverride = null, bool $includeTodasOpcoes = true): array
    {
        $rest = [];
        $endEntry = null;
        foreach ($baseOptions as $k => $v) {
            if ($moveToEndLabel !== null && (string) $v === $moveToEndLabel) {
                $endEntry = [$k => $endLabelOverride ?? $v];
            } else {
                $rest[$k] = $v;
            }
        }
        $ordered = $endEntry !== null ? $rest + $endEntry : $baseOptions;
        $especiais = [
            '__edital_nao_se_direciona__' => i::__('Edital não se direciona a segmentos específicos'),
        ];
        if ($includeTodasOpcoes) {
            $especiais['__todas_opcoes__'] = i::__('Todas as opções');
        }
        return $especiais + $ordered;
    }

    /**
     * No Pnab, plano de metas usa a mesma lista de segmentos que a oportunidade (conf/opportunity-types),
     * sem as chaves sintéticas do multiselect da edição de edital.
     */
    private function registerWorkplanSegmentMetadataForPnab(App $app, array $segmentOptionsFromOpportunity): void
    {
        if ($segmentOptionsFromOpportunity === []) {
            return;
        }

        $workplanSegmentDef = $app->getRegisteredMetadataByMetakey('culturalArtisticSegment', WorkplanEntity::class);
        $deliverySegmentDef = $app->getRegisteredMetadataByMetakey('segmentDelivery', WorkplanDelivery::class);

        $app->registerMetadata(new \MapasCulturais\Definitions\Metadata('culturalArtisticSegment', [
            'label' => $workplanSegmentDef?->label ?? i::__('Segmento artistico-cultural'),
            'type' => 'select',
            'options' => $segmentOptionsFromOpportunity,
        ]), WorkplanEntity::class);

        $app->registerMetadata(new \MapasCulturais\Definitions\Metadata('segmentDelivery', [
            'label' => $deliverySegmentDef?->label ?? i::__('Segmento artístico cultural da entrega'),
            'type' => 'select',
            'options' => $segmentOptionsFromOpportunity,
        ]), WorkplanDelivery::class);
    }

    /**
     * PNAB: `culturalMakingStage` na meta usa as opções de `etapa` em conf/opportunity-types.php (mesmas chaves persistidas).
     */
    private function registerWorkplanEtapaMetadataForPnab(App $app, array $etapaOptionsFromOpportunity): void
    {
        if ($etapaOptionsFromOpportunity === []) {
            return;
        }

        $goalEtapaDef = $app->getRegisteredMetadataByMetakey('culturalMakingStage', WorkplanGoalEntity::class);

        $app->registerMetadata(new \MapasCulturais\Definitions\Metadata('culturalMakingStage', [
            'label' => $goalEtapaDef?->label ?? i::__('Etapa do fazer cultural'),
            'type' => 'select',
            'options' => $etapaOptionsFromOpportunity,
        ]), WorkplanGoalEntity::class);
    }

    /**
     * Opções do multiselect `segmento` na oportunidade (tema PNAB).
     * Lista base vem do metadata `segmento` já registrado (conf/opportunity-types.php).
     * Ordem: 1) "Edital não se direciona…", 2) "Todas as opções", 3) segmentos com "Outros (especificar)" por último.
     */
    private function getSegmentoOptions(): array
    {
        $app = App::i();
        $definition = $app->getRegisteredMetadataByMetakey('segmento', Opportunity::class);
        $baseOptions = $definition?->options ?? [];

        return $this->enrichMultiselectOptions(
            $baseOptions,
            i::__('Outros'),
            i::__('Outros (especificar)')
        );
    }

    /**
     * Opções do multiselect `etapa` na oportunidade (PNAB), após app.init:after = metadata já registrado (enriquecido).
     * Usado em validações/trim; o registro do campo usa a mesma lista que o workplan ($opportunityEtapaOptionsForWorkplan + enrich).
     */
    public function getEtapaOptions(): array
    {
        $app = App::i();
        $definition = $app->getRegisteredMetadataByMetakey('etapa', Opportunity::class);
        if ($definition === null || !is_array($definition->options)) {
            return [];
        }
        if (array_key_exists('__edital_nao_se_direciona__', $definition->options)) {
            return $definition->options;
        }

        return $this->enrichMultiselectOptions(
            $definition->options,
            i::__('Outra (especificar)'),
            null,
            false
        );
    }

    /**
     * Obtém as opções de Pauta do OpportunityWorkplan
     * Com opção "Não se direciona" no início e "Outra (especificar)" por último. Sem "Todas as opções".
     */
    public function getPautaOptions(): array
    {
        $opcoes = $this->getMetadataOptions(
            'OpportunityWorkplan\Entities\Workplan',
            'thematicAgenda'
        );
        return $this->enrichMultiselectOptions($opcoes, i::__('Outra (especificar)'), null, false);
    }

    /**
     * Obtém as opções de Território. Apenas "Edital não se direciona" no início; sem "Todas as opções" e sem "Outros (especificar)".
     */
    private function getTerritorioOptions(): array
    {
        $opcoes = $this->getMetadataOptions(
            'OpportunityWorkplan\Entities\Delivery',
            'priorityAudience'
        );
        $outra = i::__('Outra (especificar)');
        $outros = i::__('Outros (especificar)');
        $filtered = [];
        foreach ($opcoes as $k => $v) {
            if ((string) $v === $outra || (string) $v === $outros) {
                continue;
            }
            $filtered[$k] = $v;
        }
        return $this->enrichMultiselectOptions($filtered, null, null, false);
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
     * Obtém as opções de acesso ao fomento cultural nos últimos 5 anos
     */
    private function getAcessoFomentoCulturalOptions(): array
    {
        return array(
            i::__('Sim'),
            i::__('Não'),
            i::__('Não sei informar'),
        );
    }

    /**
     * Obtém os metadados obrigatórios para agente coletivo
     * @return array Array de metadados obrigatórios
     */
    public function getRequeredsAgentColetivoMetadata(): array
    {
        return [
            'nomeSocial',
            'nomeCompleto',
            'cnpj',
            'dataDeNascimento',
            'emailPrivado',
            'telefonePublico',
            'emailPublico',
            'acessouFomentoCultural',
            'anosExperienciaAreaCultural',
            'En_CEP',
            'En_Nome_Logradouro',
            'En_Num',
            'En_Bairro',
            'En_Municipio',
            'En_Estado',
        ];
    }

    /**
     * Obtém os metadados obrigatórios para agente individual.
     *
     * @return array Array de metadados obrigatórios
     */
    public function getRequeredsAgentIndividualMetadata(): array
    {
        return [
            'nomeCompleto',
            'cpf',
            'emailPrivado',
            'telefonePublico',
            'acessouFomentoCultural',
            'anosExperienciaAreaCultural',
            'eMestreCulturasTradicionais',
            'En_CEP',
            'En_Nome_Logradouro',
            'En_Num',
            'En_Bairro',
            'En_Municipio',
            'En_Estado',
            'dataDeNascimento',
            'genero',
            'orientacaoSexual',
            'raca',
            'renda',
            'escolaridade',
            'pessoaDeficiente',
            'comunidadesTradicional',
        ];
    }

    /**
     * Verifica se o agente individual possui todos os campos obrigatórios preenchidos (perfil completo).
     * Considera apenas agente do tipo individual (tipo 1). name, shortDescription e área são desconsiderados.
     *
     * @param \MapasCulturais\Entities\Agent $agent
     * @return bool
     */
    public function hasRequiredAgentFieldsFilled(\MapasCulturais\Entities\Agent $agent): bool
    {
        $typeId = is_object($agent->type) ? ($agent->type->id ?? null) : $agent->type;
        // Considera apenas agente individual (tipo 1). Tipo null é tratado como individual para exigir preenchimento.
        if ($typeId !== null && (int) $typeId !== self::AGENT_INDIVIDUAL_TYPE_ID) {
            return true;
        }

        foreach ($this->getRequeredsAgentIndividualMetadata() as $key) {
            // Acessa via getMetadata para garantir que __metadata seja carregado (evita null quando relação lazy não foi inicializada)
            $value = $agent->getMetadata($key);
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Aplica trim no campo "Outros" quando o campo principal possui valor "Outra (especificar)"
     * Suporta campo principal como string (select antigo) ou array (multiselect).
     *
     * @param string $tipo Nome do campo principal (ex: 'etapa', 'pauta')
     * @param string $outroTipo Nome do campo "Outros" (ex: 'etapaOutros', 'pautaOutros')
     * @param array &$postData Referência ao array de dados POST
     */
    private function trimOtherValue(string $tipo, string $outroTipo, array &$postData): void
    {
        $valorEsperado = $tipo === 'etapa' ? OtherValues::OUTRA_ETAPA : OtherValues::OUTRA_PAUTA;
        if (!isset($postData[$tipo]) || !isset($postData[$outroTipo]) || $postData[$outroTipo] === null || $postData[$outroTipo] === '') {
            return;
        }
        $contemOutra = false;
        $valorPrincipal = $postData[$tipo];
        if (is_array($valorPrincipal)) {
            $opcoes = $tipo === 'etapa' ? $this->getEtapaOptions() : $this->getPautaOptions();
            $outraKey = array_search($valorEsperado, $opcoes, true);
            $contemOutra = $outraKey !== false && in_array($outraKey, $valorPrincipal, true);
        } else {
            $contemOutra = $valorPrincipal === $valorEsperado;
        }
        if ($contemOutra) {
            $postData[$outroTipo] = trim((string) $postData[$outroTipo]);
        }
    }

    /**
     * Aplica trim no campo segmentoOutros quando "Outros" está em segmento
     *
     * @param array &$postData Referência ao array de dados POST
     */
    private function trimSegmentoOutros(array &$postData): void
    {
        if (!isset($postData['segmento']) || !isset($postData['segmentoOutros']) || $postData['segmentoOutros'] === null || $postData['segmentoOutros'] === '') {
            return;
        }
        $segmento = $postData['segmento'];
        if (!is_array($segmento)) {
            return;
        }
        $opcoes = $this->getSegmentoOptions();
        $outrosKey = array_search(i::__('Outros (especificar)'), $opcoes, true);
        if ($outrosKey !== false && in_array($outrosKey, $segmento, true)) {
            $postData['segmentoOutros'] = trim((string) $postData['segmentoOutros']);
        }
    }

    /**
     * Sanitiza o metadado recursosOutrasFontes antes de persistir: trim, strip_tags e
     * limite de tamanho para nomeFonte em cada item de "outras fontes".
     *
     * @param array &$data Dados do PATCH (alterados in-place)
     */
    public function sanitizeRecursosOutrasFontes(array &$data): void
    {
        if (!isset($data['recursosOutrasFontes']) || !is_array($data['recursosOutrasFontes'])) {
            return;
        }
        $raw = $data['recursosOutrasFontes'];
        $recursos = self::ensureArray($raw);
        if (isset($recursos['outrasFontes']) && is_array($recursos['outrasFontes'])) {
            foreach ($recursos['outrasFontes'] as $i => $entrada) {
                $entrada = is_array($entrada) ? $entrada : [];
                $nome = isset($entrada['nomeFonte']) ? (string) $entrada['nomeFonte'] : '';
                $nome = trim(strip_tags($nome));
                if (mb_strlen($nome) > self::RECURSOS_OUTRAS_FONTES_NOME_FONTE_MAX_LENGTH) {
                    $nome = mb_substr($nome, 0, self::RECURSOS_OUTRAS_FONTES_NOME_FONTE_MAX_LENGTH);
                }
                $recursos['outrasFontes'][$i]['nomeFonte'] = $nome;
                if (array_key_exists('_id', $entrada)) {
                    $recursos['outrasFontes'][$i]['_id'] = $entrada['_id'];
                }
                if (array_key_exists('valor', $entrada)) {
                    $recursos['outrasFontes'][$i]['valor'] = $entrada['valor'];
                }
            }
        }
        $data['recursosOutrasFontes'] = $recursos;
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
        $totalDefinido = $convertVal($metadataValue);

        // Exige que o somatório das categorias seja exatamente igual ao Total de vagas / Valor total (nem menor, nem maior)
        if ($metadataKey === 'vacancies') {
            if ($totalMetadataInRanges !== $totalDefinido) {
                return [
                    'registrationRangesVacancies' => [
                        i::__('O total de vagas das categorias deve ser igual ao Total de vagas definido;')
                    ]
                ];
            }
        } elseif ($metadataKey === 'totalResource') {
            // Para valor monetário, tolerância de 0.01 para evitar erros de ponto flutuante
            if (abs($totalMetadataInRanges - $totalDefinido) > 0.009) {
                return [
                    'registrationRangesTotalResource' => [
                        i::__('O total em valores das categorias deve ser igual ao Valor total definido;')
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * Garante valor como array (objeto JSON vindo do banco vira array associativo).
     * Reutilizado em todas as validações de metadado JSON.
     * Público pois é chamado de dentro de hooks onde $this é a entidade (ex.: opportunity).
     */
    public static function ensureArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value)) {
            $decoded = json_decode(json_encode($value), true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Metadado «gerada a partir de modelo» (evita repetir filter_var em hooks e validação).
     *
     * @param Opportunity $entity
     */
    private function isOpportunityGeneratedFromModel($entity): bool
    {
        return (bool) filter_var(
            $entity->getMetadata(\AldirBlanc\Controller::OPPORTUNITY_META_IS_GENERATED_FROM_MODEL),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * True após OportunidadeCultJob create concluir com sucesso (metadado cultBrCreateSynced).
     *
     * @param Opportunity $entity
     */
    private function isOpportunityCultBrCreateSynced($entity): bool
    {
        return (bool) filter_var(
            $entity->getMetadata(\AldirBlanc\Controller::OPPORTUNITY_META_CULT_BR_CREATE_SYNCED),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Valida se o job de integração com o CultBR deve ser disparado
     */
    private function validateIntegrationJob($entity)
    {
        $federativeEntityId = $entity->getMetadata('federativeEntityId');
        $subsiteId = (int) $entity->subsite?->id;
        $parent = $entity->parent;
        $status = $entity->status;
        $themePnabSubsiteId = (int) env('ALDIRBLANC_SUBSITE_ID', 0);
        $isGeneratedFromModel = $this->isOpportunityGeneratedFromModel($entity);

        // Se federativeEntityId não estiver definido, não disparar o job
        if ($federativeEntityId === null
            || $federativeEntityId === ''
            || (is_string($federativeEntityId) && trim($federativeEntityId) === '')
        ) {
            return false;
        }

        // Se subsiteId não estiver definido, não disparar o job
        if ($subsiteId < 1) {
            return false;
        }

        // Se ALDIRBLANC_SUBSITE_ID não estiver definido, não disparar o job
        if ($themePnabSubsiteId === 0) {
            return false;
        }

        // Subsite da entidade ≠ ALDIRBLANC_SUBSITE_ID: bloqueia, exceto «usar modelo» (já garantido themePnabSubsiteId > 0 acima).
        if (!$isGeneratedFromModel && $subsiteId !== $themePnabSubsiteId) {
            return false;
        }

        if ((int) $status === (int) Opportunity::STATUS_PHASE) {
            return false;
        }

        // Se a oportunidade for uma oportunidade complementar, não disparar o job
        if ((bool) $parent) {
            return false;
        }

        return true;
    }
}
