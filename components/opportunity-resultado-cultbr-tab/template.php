<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\Pnab\Theme $this
 */

use MapasCulturais\i;
?>
<mc-tab v-if="visivel" label="<?= i::__('Envio de Resultado ao CultBr') ?>" slug="resultado-cultbr">
    <mc-container>
        <main class="resultado-cultbr-tab__conteudo">
            <mc-tabs class="resultado-cultbr-tab__subabas" :sync-hash="false" default-tab="formulario" @changed="selectSubAba($event.tab.slug)">
                <mc-tab slug="formulario" label="<?= i::__('Formulário do Resultado') ?>"></mc-tab>
                <mc-tab v-if="!isAcaoSemResultado" slug="beneficiarios" label="<?= i::__('Lista de beneficiários') ?>"></mc-tab>
            </mc-tabs>

            <!-- v-show (não v-if): alternar as sub-abas preserva o autosave em andamento do formulário. -->
            <div v-show="subAba === 'formulario'">
                <opportunity-resultado-cultbr-card :entity="entity" :preloaded="isEstadoInicialUsado ? null : estadoInicial" :outra-gravacao-pendente="isDeParaPendente" @vue:mounted="isEstadoInicialUsado = true"></opportunity-resultado-cultbr-card>
            </div>
            <div v-if="!isAcaoSemResultado" v-show="subAba === 'beneficiarios'">
                <opportunity-resultado-beneficiarios :entity="entity" @pendente="isDeParaPendente = $event"></opportunity-resultado-beneficiarios>
            </div>
        </main>
    </mc-container>
</mc-tab>
