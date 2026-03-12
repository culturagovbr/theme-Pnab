<?php
/**
 * Tema Pnab: override do componente agent-data-1 (exibição em resumo na single do agente individual).
 * Base: modules/Entities/components/agent-data-1/
 */

use MapasCulturais\i;

$this->import('
    mc-card
    entity-data
');
?>
<div class="col-12 agent-data">
    <template v-if="verifyFields()">
        <div class="agent-data__title">
            <h4 class="title bold" v-if="alwaysShowTitle || entity.telefonePublico"><?php i::_e("Dados Pessoais") ?>
                <?php if($this->isEditable()): ?>
                    <?php $this->info('cadastro -> configuracoes-entidades -> dados-pessoais') ?>
                <?php endif; ?>
            </h4>
        </div>
        <div class="agent-data__fields">
            <entity-data v-if="entity.nomeCompleto && !showOnlyPublicData" class="agent-data__fields--field" :entity="entity" prop="nomeCompleto" label="<?php i::_e("Nome completo")?>"></entity-data>
            <entity-data v-if="entity.nomeSocial && !showOnlyPublicData" class="agent-data__fields--field" :entity="entity" prop="nomeSocial" label="<?php i::_e("Nome artístico ou Nome Social")?>"></entity-data>
            <entity-data v-if="entity.cpf && !showOnlyPublicData" class="agent-data__fields--field" :entity="entity" prop="cpf" label="<?php i::_e("CPF")?>"></entity-data>
            <entity-data v-if="entity.telefonePublico && !showOnlyPublicData" class="agent-data__fields--field" :entity="entity" prop="telefonePublico" label="<?php i::_e("Telefone")?>"></entity-data>
            <entity-data v-if="entity.emailPrivado && !showOnlyPublicData" class="agent-data__fields--field" :entity="entity" prop="emailPrivado" label="<?php i::_e("E-mail")?>"></entity-data>
        </div>
    </template>
    <template v-if="verifySensitiveFields() && entity.currentUserPermissions.viewPrivateData && !showOnlyPublicData">
        <div class="agent-data__secondTitle">
            <h4 class="title bold"><?php i::_e("Dados pessoais sensíveis") ?>
                <?php if($this->isEditable()): ?>
                    <?php $this->info('cadastro -> configuracoes-entidades -> dados-pessoais-sensiveis') ?>
                <?php endif; ?>
            </h4>
        </div>
        <div class="agent-data__fields">
            <entity-data v-if="entity.dataDeNascimento" class="agent-data__fields--field" :entity="entity" prop="dataDeNascimento" label="<?php i::_e("Data de Nascimento")?>"></entity-data>
            <entity-data v-if="entity.genero" class="agent-data__fields--field" :entity="entity" prop="genero" label="<?php i::_e("Gênero")?>"></entity-data>
            <entity-data v-if="entity.orientacaoSexual" class="agent-data__fields--field" :entity="entity" prop="orientacaoSexual" label="<?php i::_e("Orientação Sexual")?>"></entity-data>
            <entity-data v-if="entity.raca" class="agent-data__fields--field" :entity="entity" prop="raca" label="<?php i::_e("Cor/raça/etnia") ?>"></entity-data>
            <entity-data v-if="entity.escolaridade" class="agent-data__fields--field" :entity="entity" prop="escolaridade" label="<?php i::_e("Escolaridade") ?>"></entity-data>
            <entity-data v-if="entity.pessoaDeficiente" class="agent-data__fields--field" :entity="entity" prop="pessoaDeficiente" label="<?php i::_e("É pessoa com deficiência?") ?>"></entity-data>
            <entity-data v-if="entity.pessoaDeficiente && entity.pessoaDeficiente.length==1 && entity.pessoaDeficiente[0]==''" class="agent-data__fields--field" :entity="entity" prop="pessoaDeficiente" label="<?php i::_e("Não sou") ?>"></entity-data>
            <entity-data v-if="entity.comunidadesTradicional" class="agent-data__fields--field" :entity="entity" prop="comunidadesTradicional" label="<?php i::_e("Pertence a povos e comunidades tradicionais?") ?>"></entity-data>
            <entity-data v-if="entity.comunidadesTradicionalOutros" class="agent-data__fields--field" :entity="entity" prop="comunidadesTradicionalOutros" label="<?php i::_e("Não encontrou sua comunidade Tradicional") ?>"></entity-data>
        </div>
    </template>
</div>
