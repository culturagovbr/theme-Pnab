<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-icon
');
?>
<div class="consolidating-data-page">
    <div class="consolidating-data-page__container">
        <div class="consolidating-data-page__content">
            <div v-if="!hasError" class="consolidating-data-page__spinner"></div>
            <mc-icon v-else name="alert-circle" class="consolidating-data-page__error-icon"></mc-icon>
            <h1 class="consolidating-data-page__title" :class="{ 'consolidating-data-page__title--error': hasError }">
                <span v-if="!hasError"><?php i::_e('Estamos consolidando os seus dados...aguarde alguns instantes') ?></span>
                <span v-else>{{ errorMessage || '<?php i::_e('Não foi possível consolidar seus dados. Você será desconectado para tentar novamente.') ?>' }}</span>
            </h1>
            <div v-if="hasError" class="consolidating-data-page__actions">
                <button @click="logoutOnError" class="button button--primary">
                    <?php i::_e('Ir para o Login') ?>
                </button>
            </div>
        </div>
    </div>
</div>
