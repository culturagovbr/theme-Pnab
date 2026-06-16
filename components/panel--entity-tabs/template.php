<?php
/**
 * @var \MapasCulturais\Themes\BaseV2\Theme $this
 * @var \MapasCulturais\App $app
 */

use MapasCulturais\i;

$this->import('
    mc-entities
    mc-tab
    mc-tabs
    panel--entity-card
    panel--entity-models-card
    registration-card
');

$tabs = $tabs ?? [
    'publish' => i::esc_attr__('Publicados'),
    'draft' => i::esc_attr__('Em rascunho'),
    'granted' => i::esc_attr__('Com permissão'),
    'mymodels' => i::esc_attr__('Meus modelos'),
    'archived' => i::esc_attr__('Arquivados'),
    'trash' => i::esc_attr__('Lixeira'),
];

$this->applyComponentHook('.tabs', [&$tabs]);

$sort_options = [
    'name ASC' => i::__('Ordem alfabética'),
    'createTimestamp DESC' => i::__('Mais recentes primeiro'),
    'createTimestamp ASC' => i::__('Mais antigas primeiro'),
    'updateTimestamp DESC' => i::__('Modificadas recentemente'),
    'updateTimestamp ASC' => i::__('Modificadas há mais tempo'),
];

$this->applyComponentHook('.sortOptions', [&$tabs]);

?>
<mc-tabs class="entity-tabs models" sync-hash>
    <?php $this->applyComponentHook('begin') ?>
    <template #header="{ tab }">
        <?php $this->applyComponentHook('tab', 'begin') ?>
        <mc-icon v-if="tab.slug === 'archived'" name="archive"></mc-icon>
        <mc-icon v-else-if="tab.slug === 'trash'" name="trash"></mc-icon>
        {{ tab.label }}
        <?php $this->applyComponentHook('tab', 'end') ?>
    </template>
    <?php foreach($tabs as $status => $label): ?>
    <?php $this->applyComponentHook($status, 'before') ?>
    <mc-tab v-if="showTab('<?=$status?>')" cache key="<?=$status?>" label="<?=$label?>" slug="<?=$status?>">
        <?php $this->applyComponentHook($status, 'begin') ?>
        <mc-entities :name="type + ':<?=$status?>'" :type="type" 
            :select="select"
            :query="queries['<?=$status?>']" 
            :limit="limit" 
            :order="queries['<?=$status?>']['@order']"
            watch-query>
            <template #header="{entities}">
                <form v-if="!('<?=$status?>' === 'mymodels' && type === 'opportunity' && parActionFilterEnabled)" class="entity-tabs__filters panel__row" @submit="$event.preventDefault();">
                    <slot name="filters">
                        <input type="search" class="entity-tabs__search-input"
                            aria-label="<?=i::__('Palavras-chave')?>"
                            placeholder="<?=i::__('Buscar por palavras-chave')?>"
                            v-model="queries['<?=$status?>']['@keyword']">
                        
                        <slot name="filters-additional" :entities="entities" :query="queries['<?=$status?>']"></slot>
                        <label> <?= i::__ ("Ordernar por:") ?>
                            <select class="entity-tabs__search-select primary__border--solid" v-model="queries['<?=$status?>']['@order']">
                                <?php foreach($sort_options as $value => $label): ?>
                                    <option value="<?= htmlentities($value) ?>"><?= htmlentities($label) ?></option>    
                                <?php endforeach ?>
                            </select>
                        </label>
                    </slot>
                </form>
            </template>

            <template #default="{entities}">
                <div
                    v-if="'<?=$status?>' === 'mymodels' && type === 'opportunity' && parActionFilterEnabled"
                    class="grid-12 search-list panel-entity-tabs__models-search">
                    <div class="col-3 search-list__filter">
                        <div class="search-list__filter--filter">
                            <div class="search-filter search-filter--list">
                                <div class="search-filter__filter show">
                                    <div class="search-filter__filter-content">
                                <label class="form__label"><?php i::_e('Filtros de modelos') ?></label>
                                <form class="form" @submit="$event.preventDefault()">
                                    <div class="field">
                                        <label><?php i::_e('Buscar por palavra-chave') ?></label>
                                        <input type="search"
                                            aria-label="<?=i::__('Palavras-chave')?>"
                                            placeholder="<?=i::__('Buscar por palavras-chave')?>"
                                            v-model="queries['<?=$status?>']['@keyword']">
                                    </div>
                                    <div v-if="parActionOptions.length > 0" class="field">
                                        <label><?php i::_e('Ações do PAR') ?></label>
                                        <select v-model="queries['<?=$status?>'].parAction">
                                            <option value=""><?= i::__('Selecione uma ação do PAR') ?></option>
                                            <option v-for="action in parActionOptions" :key="action" :value="action">{{ action }}</option>
                                        </select>
                                    </div>
                                    <div v-else class="alert alert-warning panel-entity-tabs__par-action-warning">
                                        <?= i::__('O Ente Federado não possui dados do PAR configurados. Entrar em contato com suporte.') ?>
                                    </div>
                                    <div class="field">
                                        <label><?php i::_e('Ordenar por') ?></label>
                                        <select v-model="queries['<?=$status?>']['@order']">
                                            <?php foreach($sort_options as $value => $label): ?>
                                                <option value="<?= htmlentities($value) ?>"><?= htmlentities($label) ?></option>    
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-9 search-list__cards">
                        <slot name='before-list' :entities="entities" :query="queries['<?=$status?>']"></slot>
                        <slot v-for="entity in entities" :key="entity.__objectId" :entity="entity" :moveEntity="moveEntity">
                            <registration-card v-if="entity.__objectType == 'registration'" :entity="entity" pictureCard hasBorders class="panel__row">
                                <template #entity-actions-left>
                                    <slot name="entity-actions-left" :entity="entity"></slot>
                                </template>
                            </registration-card>
                            <panel--entity-card v-if="(entity.__objectType != 'registration' && entity.__objectType != 'opportunity') || (entity.__objectType == 'opportunity' && entity.isModel != 1)" :key="entity.id" :entity="entity" 
                                @undeleted="moveEntity(entity, $event)" 
                                @deleted="moveEntity(entity, $event)" 
                                @archived="moveEntity(entity, $event)" 
                                @published="moveEntity(entity, $event)"
                                :on-delete-remove-from-lists="false"
                                >
                                <template #title="{ entity }">
                                    <slot name="card-title" :entity="entity"></slot>
                                </template>
                                <template #subtitle="{ entity }">
                                    <slot name="card-content" :entity="entity">
                                        <span v-if="entity.type">
                                            <?=i::__('Tipo: ')?> <strong>{{ entity.type.name }}</strong>
                                        </span>
                                    </slot>
                                </template>
                                <template #entity-actions-left>
                                    <slot name="entity-actions-left" :entity="entity"></slot>
                                </template>
                                <template #entity-actions-center>
                                    <slot name="entity-actions-center" :entity="entity"></slot>
                                </template>
                                <template #entity-actions-right>
                                    <slot name="entity-actions-right" :entity="entity"></slot>
                                </template>
                            </panel--entity-card>
                            <panel--entity-models-card v-if="entity.__objectType == 'opportunity' && entity.isModel == 1" :key="entity.id" :entity="entity" :models="opportunitiesModels"></panel--entity-models-card>
                        </slot>
                        <slot name='after-list' :entities="entities" :query="queries['<?=$status?>']"></slot>
                    </div>
                </div>
                <template v-else>
                    <slot name='before-list' :entities="entities" :query="queries['<?=$status?>']"></slot>
                    <slot v-for="entity in entities" :key="entity.__objectId" :entity="entity" :moveEntity="moveEntity">
                        <registration-card v-if="entity.__objectType == 'registration'" :entity="entity" pictureCard hasBorders class="panel__row">
                            <template #entity-actions-left>
                                <slot name="entity-actions-left" :entity="entity"></slot>
                            </template>
                        </registration-card>
                        <panel--entity-card v-if="(entity.__objectType != 'registration' && entity.__objectType != 'opportunity') || (entity.__objectType == 'opportunity' && entity.isModel != 1)" :key="entity.id" :entity="entity" 
                            @undeleted="moveEntity(entity, $event)" 
                            @deleted="moveEntity(entity, $event)" 
                            @archived="moveEntity(entity, $event)" 
                            @published="moveEntity(entity, $event)"
                            :on-delete-remove-from-lists="false"
                            >
                            <template #title="{ entity }">
                                <slot name="card-title" :entity="entity"></slot>
                            </template>
                            <template #subtitle="{ entity }">
                                <slot name="card-content" :entity="entity">
                                    <span v-if="entity.type">
                                        <?=i::__('Tipo: ')?> <strong>{{ entity.type.name }}</strong>
                                    </span>
                                </slot>
                            </template>
                            <template #entity-actions-left>
                                <slot name="entity-actions-left" :entity="entity"></slot>
                            </template>
                            <template #entity-actions-center>
                                <slot name="entity-actions-center" :entity="entity"></slot>
                            </template>
                            <template #entity-actions-right>
                                <slot name="entity-actions-right" :entity="entity"></slot>
                            </template>
                        </panel--entity-card>
                        <panel--entity-models-card v-if="entity.__objectType == 'opportunity' && entity.isModel == 1" :key="entity.id" :entity="entity" :models="opportunitiesModels"></panel--entity-models-card>
                    </slot>
                    <slot name='after-list' :entities="entities" :query="queries['<?=$status?>']"></slot>
                </template>
            </template>

            <template #empty="{entities}">
                <div
                    v-if="'<?=$status?>' === 'mymodels' && type === 'opportunity' && parActionFilterEnabled"
                    class="grid-12 search-list panel-entity-tabs__models-search">
                    <div class="col-3 search-list__filter">
                        <div class="search-list__filter--filter">
                            <div class="search-filter search-filter--list">
                                <div class="search-filter__filter show">
                                    <div class="search-filter__filter-content">
                                <label class="form__label"><?php i::_e('Filtros de modelos') ?></label>
                                <form class="form" @submit="$event.preventDefault()">
                                    <div class="field">
                                        <label><?php i::_e('Buscar por palavra-chave') ?></label>
                                        <input type="search"
                                            aria-label="<?=i::__('Palavras-chave')?>"
                                            placeholder="<?=i::__('Buscar por palavras-chave')?>"
                                            v-model="queries['<?=$status?>']['@keyword']">
                                    </div>
                                    <div v-if="parActionOptions.length > 0" class="field">
                                        <label><?php i::_e('Ações do PAR') ?></label>
                                        <select v-model="queries['<?=$status?>'].parAction">
                                            <option value=""><?= i::__('Selecione uma ação do PAR') ?></option>
                                            <option v-for="action in parActionOptions" :key="action" :value="action">{{ action }}</option>
                                        </select>
                                    </div>
                                    <div v-else class="alert alert-warning panel-entity-tabs__par-action-warning">
                                        <?= i::__('O Ente Federado não possui dados do PAR configurados. Entrar em contato com suporte.') ?>
                                    </div>
                                    <div class="field">
                                        <label><?php i::_e('Ordenar por') ?></label>
                                        <select v-model="queries['<?=$status?>']['@order']">
                                            <?php foreach($sort_options as $value => $label): ?>
                                                <option value="<?= htmlentities($value) ?>"><?= htmlentities($label) ?></option>    
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-9 search-list__cards">
                        <div class="panel__row noEntity panel-entity-tabs__no-models-for-action">
                            <ul>
                                <li><strong><?= i::__('Ação 1.1 - Fomento Cultural -') ?></strong> <?= i::__('Termo de Execução Cultural (Lei nº 14.903/2024)') ?>;</li>
                                <li><strong><?= i::__('Ação 1.1 - Fomento Cultural -') ?></strong> <?= i::__('Prêmio (Lei nº 14.903/2024)') ?>;</li>
                                <li><strong><?= i::__('Ação 1.1 - Fomento Cultural -') ?></strong> <?= i::__('Bolsa Cultural (Lei nº 14.903/2024)') ?>;</li>
                                <li><strong><?= i::__('Ação 1.1 - Fomento Cultural -') ?></strong> <?= i::__('Termo de Fomento (Lei nº 13.019/2014)') ?>;</li>
                                <li><strong><?= i::__('Ação 2.1 - Fomento a projetos de Pontos de Cultura -') ?></strong> <?= i::__('Termo de Compromisso Cultural (TCC) para projetos de Pontos de Cultura') ?>;</li>
                                <li><strong><?= i::__('Ação 2.2 - Fomento a projetos de Pontões de Cultura -') ?></strong> <?= i::__('Termo de Compromisso Cultural (TCC) para projetos de Pontões de Cultura') ?>;</li>
                                <li><strong><?= i::__('Ação 2.3 - Fomento a projetos de Pontões de Cultura -') ?></strong> <?= i::__('Premiação Cultura Viva') ?>;</li>
                                <li><strong><?= i::__('Ação 2.3 - Prêmio Cultura Viva de Pontos e Pontões de Cultura -') ?></strong> <?= i::__('Termo de Premiação Cultura Viva para Pontos e Pontões de Cultura') ?>;</li>
                                <li><strong><?= i::__('Ação 2.4 - Bolsas Cultura Viva -') ?></strong> <?= i::__('Concessão de Bolsas para Mestres e Mestras das Culturas Tradicionais e Populares - Termo de Concessão de Bolsa Cultura Viva para Mestras e Mestres das Culturas Tradicionais e Populares') ?>;</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div v-else class="panel__row noEntity">
                    <p><?= i::__('Nenhuma entidade encontrada') ?></p>
                </div>
            </template>
        </mc-entities>
        <?php $this->applyComponentHook($status, 'end') ?>
    </mc-tab>
    <?php $this->applyComponentHook($status, 'after') ?>
    <?php endforeach ?>
    <?php $this->applyComponentHook('end') ?>
</mc-tabs>
