<?php
/**
 * Ações da página "Complete seu cadastro": um único botão que salva o perfil
 * e redireciona conforme o backend (painel ou escolha de ente federado).
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-loading
');
?>
<div class="entity-actions">
    <div class="entity-actions__content">
        <mc-loading :entity="entity"></mc-loading>
        <template v-if="!entity?.__processing">
            <div class="entity-actions__content--groupBtn" ref="buttons2">
                <button
                    type="button"
                    class="button button--md publish publish-exit"
                    :disabled="loading"
                    @click="saveAndContinue">
                    <span v-if="loading"><?php i::_e('Salvando...') ?></span>
                    <span v-else><?php i::_e('Salvar e continuar') ?></span>
                </button>
            </div>
        </template>
    </div>
</div>
