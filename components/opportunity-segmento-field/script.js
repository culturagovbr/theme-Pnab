/**
 * Campo Segmento artístico-cultural com opções especiais (tema Pnab).
 * - "Edital não se direciona a segmentos específicos": ao marcar, desmarca as demais.
 * - "Todas as opções": ao marcar, marca todas as opções.
 */
app.component('opportunity-segmento-field', {
    template: $TEMPLATES['opportunity-segmento-field'],

    props: {
        entity: { type: Object, required: true },
        classes: { type: [String, Array, Object], default: null },
        autosave: { type: Number, default: 0 },
    },

    data() {
        return {
            propId: 'segmento-' + Math.random().toString(36).slice(2, 9),
            readonly: false,
        };
    },

    computed: {
        description() {
            return this.entity?.$PROPERTIES?.segmento || null;
        },
        segmentoArray() {
            let val = this.entity?.segmento;
            if (val == null || val === '') return [];
            if (!Array.isArray(val)) return typeof val === 'string' ? val.split(';') : [val];
            return val;
        },
        /** Opções do select sem "Não se aplica" e sem "Todas as opções" (ficam só nos checkboxes). */
        segmentoOptionsForSelect() {
            const opts = this.description?.options;
            if (!opts) return {};
            const out = {};
            for (const k of Object.keys(opts)) {
                if (k !== '__edital_nao_se_direciona__' && k !== '__todas_opcoes__') out[k] = opts[k];
            }
            return out;
        },
        /** Tags a exibir (oculta "não se aplica" e "todas as opções" que ficam nos checkboxes). */
        tagsForDisplay() {
            const arr = this.segmentoArray;
            if (!arr || !arr.length) return [];
            return arr.filter((k) => k !== '__edital_nao_se_direciona__' && k !== '__todas_opcoes__');
        },
        isNaoSeAplica() {
            const arr = this.segmentoArray;
            return arr && arr.length === 1 && arr[0] === '__edital_nao_se_direciona__';
        },
        naoSeAplicaLabel() {
            return this.description?.options?.__edital_nao_se_direciona__ || 'Edital não se direciona a segmentos específicos';
        },
        isTodasOpcoes() {
            return this.segmentoArray && this.segmentoArray.includes('__todas_opcoes__');
        },
        todaOpcoesLabel() {
            return this.description?.options?.__todas_opcoes__ || 'Todas as opções';
        },
        /** True quando "Outros" está selecionado no segmento (mostra o campo especificar). */
        isSegmentoOutros() {
            const key = this.getOutrosKey();
            return key !== null && this.segmentoArray && this.segmentoArray.includes(key);
        },
        hasErrors() {
            const err = this.entity?.__validationErrors?.segmento;
            return Array.isArray(err) && err.length > 0;
        },
        errorsText() {
            const err = this.entity?.__validationErrors?.segmento;
            return Array.isArray(err) ? err.join('; ') : '';
        },
    },

    watch: {
        segmentoArray: {
            handler(arr) {
                if (!this.entity || !arr || arr === this.entity.segmento) return;
                if (!Array.isArray(this.entity.segmento)) {
                    this.entity.segmento = Array.isArray(arr) ? [...arr] : [];
                }
            },
            deep: true,
        },
        isSegmentoOutros: {
            handler(mostrar) {
                if (mostrar && this.entity && (this.entity.segmentoOutros === null || this.entity.segmentoOutros === undefined)) {
                    this.entity.segmentoOutros = '';
                }
            },
        },
    },

    created() {
        if (this.entity && (this.entity.segmento === null || this.entity.segmento === undefined)) {
            this.entity.segmento = [];
        } else if (this.entity && typeof this.entity.segmento !== 'object') {
            this.entity.segmento = this.entity.segmento ? String(this.entity.segmento).split(';') : [];
        }
    },

    methods: {
        /** Retorna a chave cujo label é "Outros" ou "Outros (especificar)" (para ignorar ao marcar "Todas as opções" e para mostrar campo especificar). */
        getOutrosKey() {
            const opts = this.description?.options;
            if (!opts) return null;
            for (const k of Object.keys(opts)) {
                if (k === '__todas_opcoes__' || k === '__edital_nao_se_direciona__') continue;
                const label = typeof opts[k] === 'string' ? opts[k].toLowerCase() : '';
                if (label === 'outros' || label === 'outros (especificar)') return k;
            }
            return null;
        },

        onSelected(key) {
            const arr = this.segmentoArray;
            if (!arr || !this.description?.options) return;
            if (key === '__edital_nao_se_direciona__' || key === '__todas_opcoes__') return;
            this.triggerSave();
        },

        /** Chamado quando o usuário desmarca um item no dropdown do mc-multiselect (evento @removed). */
        onRemovedFromSelect(key) {
            this.triggerSave();
        },

        /** Chamado quando o usuário clica no X da tag no mc-tag-list. O mc-tag-list emite o valor da tag; precisamos remover de entity.segmento (pois tags é tagsForDisplay, uma cópia). Ao remover "Outros", limpa segmentoOutros. */
        onRemove(tag) {
            if (!this.entity.segmento || !Array.isArray(this.entity.segmento)) return;
            const idx = this.entity.segmento.indexOf(tag);
            if (idx >= 0) {
                this.entity.segmento.splice(idx, 1);
                if (tag === this.getOutrosKey()) {
                    this.entity.segmentoOutros = null;
                }
            }
            this.triggerSave();
        },

        /** Checkbox "Não se aplica": marcar = só esse valor e desabilita o select; desmarcar = limpar. */
        onNaoSeAplicaChange(e) {
            if (e.target.checked) {
                this.entity.segmento = ['__edital_nao_se_direciona__'];
                this.entity.segmentoOutros = null;
            } else {
                this.entity.segmento = [];
            }
            this.triggerSave();
        },

        /** Checkbox "Todas as opções": marcar = preenche com todas (exceto Não se aplica e Outros); desmarcar = limpar. */
        onTodasOpcoesChange(e) {
            if (e.target.checked) {
                const opts = this.description?.options;
                if (!opts) return;
                const allKeys = Object.keys(opts);
                const outrosKey = this.getOutrosKey();
                const arr = ['__todas_opcoes__'];
                allKeys.forEach((k) => {
                    if (k === '__edital_nao_se_direciona__' || k === '__todas_opcoes__') return;
                    if (outrosKey !== null && k === outrosKey) return;
                    arr.push(k);
                });
                this.entity.segmento = arr;
                this.entity.segmentoOutros = null;
            } else {
                this.entity.segmento = [];
                this.entity.segmentoOutros = null;
            }
            this.triggerSave();
        },

        triggerSave() {
            if (this.autosave && this.entity && typeof this.entity.save === 'function') {
                this.entity.save(this.autosave);
            }
        },
    },
});
