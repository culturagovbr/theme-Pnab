<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-alert
    mc-modal
    mc-icon
');
?>

<div class="selected-federative-entity-banner">
    <button @click="openConfirmModal" class="button button-small button--primary-outline button--icon selected-federative-entity-banner__button">
        <mc-icon name="settings"></mc-icon>
        <?php i::_e('Alterar Ente Federado') ?>
    </button>

    <mc-modal ref="confirmModal" title="<?php i::_e('Confirmar Alteração de Ente Federado') ?>" classes="change-federative-entity-confirm-modal" button-label="<?php i::_e('Alterar Ente Federado') ?>">
        <template #button>
            <!-- Botão vazio para não renderizar o botão padrão do modal -->
        </template>
        <template #default="modal">
            <div class="change-federative-entity-confirm__content">
                <div class="change-federative-entity-confirm__icon-wrapper">
                    <span class="change-federative-entity-confirm__icon">⚠</span>
                </div>
                <p class="change-federative-entity-confirm__message">
                    <span class="change-federative-entity-confirm__attention">Atenção!</span> <?php i::_e('Ao alterar o ente federado, você pode perder informações não salvas em formulários que esteja preenchendo.') ?>
                </p>
                <p class="change-federative-entity-confirm__question">
                    <?php i::_e('Deseja realmente alterar o ente federado?') ?>
                </p>
            </div>
        </template>
        <template #actions="modal">
            <button class="button button--text" @click="modal.close()">
                <?php i::_e('Cancelar') ?>
            </button>
            <button class="button button--primary" @click="changeFederativeEntity(modal)">
                <?php i::_e('Confirmar Alteração') ?>
            </button>
        </template>
    </mc-modal>

    <mc-alert type="warning" class="selected-federative-entity-banner__alert">
        <?= i::__('<strong>Atenção</strong>: Você está utilizando o sistema com o ente federado ') . "<strong>{{ selectedEntityName }}</strong>" . " <span v-if=\"selectedEntityDocument\">(<code>{{ selectedEntityDocument }}</code>)</span>" ?>
    </mc-alert>
</div>