<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-confirm-button
    mc-icon
');
?>
<div class="resultado-formulario__anexo">
    <ul v-if="arquivos.length" class="resultado-formulario__arquivos">
        <li v-for="arquivo in arquivos" :key="arquivo.id" class="resultado-formulario__arquivo">
            <a class="resultado-formulario__arquivo-nome" :href="arquivo.url" :download="arquivo.nome">
                <mc-icon name="download"></mc-icon>
                <span>{{ arquivo.nome }}</span>
            </a>
            <mc-confirm-button @confirm="$emit('retirar', arquivo.id)">
                <template #button="modal">
                    <button type="button" class="button button--sm button--text" :disabled="bloqueado" @click="modal.open()"><?php i::_e('Retirar') ?></button>
                </template>
                <template #message>
                    <?php i::_e('Deseja retirar este arquivo?') ?>
                </template>
            </mc-confirm-button>
        </li>
    </ul>

    <label v-if="podeAnexar" class="button button--icon button--sm button--primary-outline resultado-formulario__anexar" :class="{ 'resultado-formulario__anexar--bloqueado': bloqueado }">
        <mc-icon name="upload"></mc-icon>
        {{ enviando ? translateMessage('enviando') : translateMessage('anexar') }}
        <input type="file" :id="'resultado-questao-' + questao.id_questao" :accept="formatos.join(',') || undefined" :disabled="bloqueado" @change="anexar">
    </label>

    <small class="field__description">{{ dica }}</small>

    <p v-if="isGrandeDemais" class="field__error" role="alert">{{ translateMessage('grande_demais', { tamanho }) }}</p>
</div>
