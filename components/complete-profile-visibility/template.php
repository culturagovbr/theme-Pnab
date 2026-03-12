<?php
/**
 * Wrapper que expõe isFieldVisible, isAddressVisible e flags de card
 * para a view Complete-profile aplicar v-if nos campos.
 *
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
?>
<slot
    :isFieldVisible="isFieldVisible"
    :isAddressVisible="addressVisible"
    :showCardApresentacao="showCardApresentacao"
    :showCardPessoais="showCardPessoais"
    :showCardSensiveis="showCardSensiveis"
></slot>
