<?php
/**
 * Exibe o metadado tipoAgenteColetivo (Pnab).
 *
 * @var \MapasCulturais\Themes\BaseV2\Theme $this
 * @var \MapasCulturais\Entities\Agent $entity
 */
use MapasCulturais\i;
?>
<div class="col-12">
    <entity-field :entity="entity" classes="col-12" prop="tipoAgenteColetivo" label="<?= i::esc_attr__('Tipo de Agente Coletivo') ?>"></entity-field>
</div>
