<?php

use MapasCulturais\i;

$app = \MapasCulturais\App::i();

// Obtém o ID da entidade federativa selecionada na sessão
$federativeEntityId = null;
if (isset($_SESSION['selectedFederativeEntity'])) {
    $selectedEntity = json_decode($_SESSION['selectedFederativeEntity'], true);
    if ($selectedEntity && isset($selectedEntity['id'])) {
        $federativeEntityId = (int)$selectedEntity['id'];
    }
}

$this->import('
    create-opportunity 
    search 
    search-filter-opportunity
    search-list
    mc-tabs
    mc-tab
');
?>

<?php if ($federativeEntityId): ?>
    <search page-title="<?= htmlspecialchars(i::__('Oportunidades do Ente Federado')) ?>" entity-type="opportunity" :initial-pseudo-query="{type:[],'term:area':[], federativeEntityId: <?= $federativeEntityId ?>}">
        <template v-if="global.auth.isLoggedIn" #create-button>
            <create-opportunity :editable="true" #default="{modal}">
                <button @click="modal.open()" class="button button--primary button--icon">
                    <mc-icon name="add"></mc-icon>
                    <span><?= i::__('Criar Oportunidade') ?></span>
                </button>
            </create-opportunity>
        </template>

        <template #default="{pseudoQuery, entity}">
            <mc-tabs class="search__tabs" sync-hash>
                <template #before-tablist>
                    <label class="search__tabs--before">
                        <?= i::_e('Visualizar como:') ?>
                    </label>
                </template>
                <?php $this->applyTemplateHook('search-tabs', 'before'); ?>
                <mc-tab icon="list" label="<?php i::esc_attr_e('Lista') ?>" slug="list">
                    <div class="tabs-component__panels">
                        <div class="search__tabs--list">
                            <search-list :pseudo-query="pseudoQuery" type="opportunity" select="name,type,files.avatar">
                                <template #filter>
                                    <search-filter-opportunity :pseudo-query="pseudoQuery"></search-filter-opportunity>
                                </template>
                            </search-list>
                        </div>
                    </div>
                </mc-tab>
                <mc-tab icon="agent" label="<?php i::esc_attr_e('Gestores') ?>" slug="gestores">
                    <div class="tabs-component__panels">
                        <div class="search__tabs--list">
                            <search-list 
                                :pseudo-query="{federativeEntityId: <?= $federativeEntityId ?>}" 
                                type="agent" 
                                select="name,type,shortDescription,files.avatar,seals,terms">
                            </search-list>
                        </div>
                    </div>
                </mc-tab>
                <?php $this->applyTemplateHook('search-tabs', 'after'); ?>
            </mc-tabs>
        </template>
    </search>
<?php else: ?>
    <div class="panel-page">
        <div class="alert alert-warning">
            <?= i::_e('Nenhuma entidade federativa selecionada. Por favor, selecione uma entidade federativa.') ?>
        </div>
    </div>
<?php endif; ?>