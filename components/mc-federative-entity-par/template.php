<?php
/**
 * Cascata PAR (exercícios / metas / ações / atividades) — prop `exercicios` e/ou `load-par-exercicios`.
 *
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
?>
<div
    class="mc-federative-entity-par"
    :class="{ 'mc-federative-entity-par--readonly': readonly }"
>
    <template v-if="readonly">
        <div class="field field--disabled col-12 sm:col-6">
            <label class="field__title">{{ translateMessage('label_exercicio') }}</label>
            <div class="field__input">
                <div class="field__input--readonly">{{ readonlyExercicioLegenda || translateMessage('texto_indisponivel') }}</div>
            </div>
        </div>
        <div class="field field--disabled col-12 sm:col-6">
            <label class="field__title">{{ translateMessage('label_meta') }}</label>
            <div class="field__input">
                <div class="field__input--readonly">{{ readonlyMetaLegenda || translateMessage('texto_indisponivel') }}</div>
            </div>
        </div>
        <div class="field field--disabled col-12 sm:col-6">
            <label class="field__title">{{ translateMessage('label_acao') }}</label>
            <div class="field__input">
                <div class="field__input--readonly">{{ readonlyAcaoLegenda || translateMessage('texto_indisponivel') }}</div>
            </div>
        </div>
        <div class="field field--disabled col-12 sm:col-6">
            <label class="field__title">{{ translateMessage('label_atividade') }}</label>
            <div class="field__input">
                <div class="field__input--readonly">{{ readonlyAtividadeLegenda || translateMessage('texto_indisponivel') }}</div>
            </div>
        </div>
    </template>

    <template v-else>
        <p
            v-if="resolvedExercicios.length === 0"
            class="mc-federative-entity-par__empty"
        >{{ emptyHint || translateMessage('lista_vazia') }}</p>
        <template v-else>
            <div class="field" :class="{ error: showFieldErrors && fieldErrors.exercicio }">
                <label class="field__title">{{ translateMessage('label_exercicio') }} <span class="required">*{{ translateMessage('obrigatorio') }}</span></label>
                <div class="field__input">
                    <select v-model="parExercicioId" required>
                        <option value="">{{ translateMessage('selecionar') }}</option>
                        <option
                            v-for="exercicio in resolvedExercicios"
                            :key="exercicio.id"
                            :value="exercicio.id"
                        >{{ exercicio.ano }}</option>
                    </select>
                </div>
                <small v-if="showFieldErrors && fieldErrors.exercicio" class="field__error">{{ fieldErrorMessage('exercicio') }}</small>
            </div>

            <div class="field" :class="{ error: showFieldErrors && fieldErrors.meta }">
                <label class="field__title">{{ translateMessage('label_meta') }} <span class="required">*{{ translateMessage('obrigatorio') }}</span></label>
                <p
                    v-if="exercicioSemMetasDisponiveis"
                    class="mc-federative-entity-par__no-options"
                >{{ translateMessage('sem_metas_para_exercicio') }}</p>
                <div v-else class="field__input">
                    <select v-model="parMetaId" required :disabled="!parExercicioId">
                        <option value="">{{ translateMessage('selecionar') }}</option>
                        <option
                            v-for="meta in parMetas"
                            :key="meta.id"
                            :value="meta.id"
                        >{{ meta.nome }}</option>
                    </select>
                </div>
                <small v-if="showFieldErrors && fieldErrors.meta" class="field__error">{{ fieldErrorMessage('meta') }}</small>
            </div>

            <div class="field" :class="{ error: showFieldErrors && fieldErrors.acao }">
                <label class="field__title">{{ translateMessage('label_acao') }} <span class="required">*{{ translateMessage('obrigatorio') }}</span></label>
                <p
                    v-if="metaSemAcoesDisponiveis"
                    class="mc-federative-entity-par__no-options"
                >{{ translateMessage('sem_acoes_para_meta') }}</p>
                <div v-else class="field__input">
                    <select v-model="parAcaoId" required :disabled="!parMetaId">
                        <option value="">{{ translateMessage('selecionar') }}</option>
                        <option
                            v-for="acao in parAcoes"
                            :key="acao.id"
                            :value="acao.id"
                        >{{ acao.nome }}</option>
                    </select>
                </div>
                <small v-if="showFieldErrors && fieldErrors.acao" class="field__error">{{ fieldErrorMessage('acao') }}</small>
            </div>

            <div class="field" :class="{ error: showFieldErrors && fieldErrors.atividade }">
                <label class="field__title">{{ translateMessage('label_atividade') }} <span class="required">*{{ translateMessage('obrigatorio') }}</span></label>
                <p
                    v-if="acaoSemAtividadesDisponiveis"
                    class="mc-federative-entity-par__no-options"
                >{{ translateMessage('sem_atividades_para_acao') }}</p>
                <div v-else class="field__input">
                    <select v-model="parAtividadeId" required :disabled="!parAcaoId">
                        <option value="">{{ translateMessage('selecionar') }}</option>
                        <option
                            v-for="atividade in parAtividades"
                            :key="atividade.id"
                            :value="atividade.id"
                        >{{ atividade.nome }}</option>
                    </select>
                </div>
                <small v-if="showFieldErrors && fieldErrors.atividade" class="field__error">{{ fieldErrorMessage('atividade') }}</small>
            </div>
        </template>
    </template>
</div>
