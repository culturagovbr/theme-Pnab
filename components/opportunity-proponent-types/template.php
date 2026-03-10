<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

?>

<div class="opportunity-proponent-types" :class="{'field--error': hasError && getErrors.length > 0}">
    <h4 class="bold"><?= i::__("Tipos do proponente")?> <span class="required" style="color:red">*</span></h4>
    <h6><?= i::__("Selecione um ou mais tipos de proponente que poderá participar do edital")?></h6>
    <div>
        <div class="opportunity-proponent-types__fields">
            <div class="opportunity-proponent-types__field" v-for="optionValue in description.optionsOrder" :key="optionValue">
                <label>
                    <input 
                        :checked="value?.includes(optionValue)" 
                        type="checkbox" 
                        :value="optionValue" 
                        @change="modifyCheckbox($event)"
                    > 
                    {{ description.options[optionValue] }}
                </label>
            </div>
        </div>
        <small v-if="hasError && getErrors.length > 0" class="field__error">{{ getErrors.join('; ') }}</small>
    </div>
</div>
