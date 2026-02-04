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
                <?php $this->applyTemplateHook('search-tabs', 'before'); ?>
                <mc-tab icon="check-circle" label="<?php i::esc_attr_e('Publicados') ?>" slug="published" class="tab-published" style="--mc-tab-active-color: var(--mc-success-500);">
                    <div class="tabs-component__panels">
                        <div class="search__tabs--list">
                            <search-list :pseudo-query="{...pseudoQuery, status: 'GTE(1)', federativeEntityId: <?= $federativeEntityId ?>}" type="opportunity" select="name,type,files.avatar">
                                <template #filter>
                                    <search-filter-opportunity :pseudo-query="{...pseudoQuery, status: 'GTE(1)', federativeEntityId: <?= $federativeEntityId ?>}"></search-filter-opportunity>
                                </template>
                            </search-list>
                        </div>
                    </div>
                </mc-tab>
                <mc-tab icon="edit" label="<?php i::esc_attr_e('Em rascunho') ?>" slug="draft" class="tab-draft" style="--mc-tab-active-color: var(--mc-warning-500);">
                    <div class="tabs-component__panels">
                        <div class="search__tabs--list">
                            <search-list :pseudo-query="{...pseudoQuery, status: 'EQ(0)', federativeEntityId: <?= $federativeEntityId ?>}" type="opportunity" select="name,type,files.avatar">
                                <template #filter>
                                    <search-filter-opportunity :pseudo-query="{...pseudoQuery, status: 'EQ(0)', federativeEntityId: <?= $federativeEntityId ?>}"></search-filter-opportunity>
                                </template>
                            </search-list>
                        </div>
                    </div>
                </mc-tab>
                <mc-tab icon="folder" label="<?php i::esc_attr_e('Arquivados') ?>" slug="archived" class="archived" style="--mc-tab-active-color: var(--mc-warning);">
                    <div class="tabs-component__panels">
                        <div class="search__tabs--list">
                            <search-list :pseudo-query="{...pseudoQuery, status: 'EQ(-2)', federativeEntityId: <?= $federativeEntityId ?>}" type="opportunity" select="name,type,files.avatar">
                                <template #filter>
                                    <search-filter-opportunity :pseudo-query="{...pseudoQuery, status: 'EQ(-2)', federativeEntityId: <?= $federativeEntityId ?>}"></search-filter-opportunity>
                                </template>
                            </search-list>
                        </div>
                    </div>
                </mc-tab>
                <mc-tab icon="trash" label="<?php i::esc_attr_e('Lixeira') ?>" slug="trash" class="trash" style="--mc-tab-active-color: var(--mc-error);">
                    <div class="tabs-component__panels">
                        <div class="search__tabs--list">
                            <search-list :pseudo-query="{...pseudoQuery, status: 'EQ(-1)', federativeEntityId: <?= $federativeEntityId ?>}" type="opportunity" select="name,type,files.avatar">
                                <template #filter>
                                    <search-filter-opportunity :pseudo-query="{...pseudoQuery, status: 'EQ(-1)', federativeEntityId: <?= $federativeEntityId ?>}"></search-filter-opportunity>
                                </template>
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