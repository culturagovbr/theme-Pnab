<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>

<div class="federative-entity-par-tree">
    <h2><?php i::_e('Dados do PAR') ?></h2>

    <p v-if="!hasExercicios" class="federative-entity-par-tree__empty">
        <?php i::_e('Este ente federado não possui dados do PAR.') ?>
    </p>

    <template v-else>
        <section
            v-for="exercicio in exercicios"
            :key="exercicio.id"
            class="federative-entity-par-tree__exercicio">
            <h3 class="federative-entity-par-tree__ano">{{ exercicio.ano }}</h3>

            <p v-if="!metasOf(exercicio).length" class="federative-entity-par-tree__vazio">
                <?php i::_e('Sem metas cadastradas.') ?>
            </p>

            <ul class="federative-entity-par-tree__lista">
                <li v-for="meta in metasOf(exercicio)" :key="meta.id">
                    <div class="federative-entity-par-tree__item federative-entity-par-tree__item--meta">
                        <span class="federative-entity-par-tree__nome">{{ meta.nome }}</span>
                        <span v-if="meta.valor != null" class="federative-entity-par-tree__valor">{{ formatCurrency(meta.valor) }}</span>
                    </div>

                    <ul class="federative-entity-par-tree__lista">
                        <li v-for="acao in acoesOf(meta)" :key="acao.id">
                            <div class="federative-entity-par-tree__item federative-entity-par-tree__item--acao">
                                <span class="federative-entity-par-tree__nome">{{ acao.nome }}</span>
                                <span v-if="acao.valor != null" class="federative-entity-par-tree__valor">{{ formatCurrency(acao.valor) }}</span>
                            </div>

                            <ul class="federative-entity-par-tree__lista">
                                <li
                                    v-for="atividade in atividadesOf(acao)"
                                    :key="atividade.id"
                                    class="federative-entity-par-tree__item federative-entity-par-tree__item--atividade">
                                    <span class="federative-entity-par-tree__nome">{{ atividade.nome }}</span>
                                    <span v-if="atividade.valor != null" class="federative-entity-par-tree__valor">{{ formatCurrency(atividade.valor) }}</span>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </section>
    </template>
</div>
