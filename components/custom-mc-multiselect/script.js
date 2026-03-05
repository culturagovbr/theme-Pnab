/**
 * Multiselect customizado (tema Pnab): checkboxes "Não se aplica" e "Todas as opções",
 * e campo "Outros (especificar)" quando a opção correspondente está selecionada.
 * Genérico: não enxerga detalhes dos campos (segmento, pauta, etapa, territorio).
 *
 * Valores das constantes (NOT_APPLICABLE_KEY, ALL_OPTIONS_KEY) são mantidos em português
 * para compatibilidade com o backend/API.
 */
const NOT_APPLICABLE_KEY = '__edital_nao_se_direciona__';
const ALL_OPTIONS_KEY = '__todas_opcoes__';

app.component('custom-mc-multiselect', {
    template: $TEMPLATES['custom-mc-multiselect'],

    props: {
        entity: { type: Object, required: true },
        /** Nome da propriedade no entity (ex: 'segmento', 'pauta', 'etapa', 'territorio'). */
        prop: { type: String, required: true },
        /** Nome da propriedade "Outros" no entity (ex: 'segmentoOutros'). Opcional: se não houver, não exibe campo especificar. */
        outrosProp: { type: String, default: null },
        /** Exibe o checkbox "Todas as opções" (apenas Segmento). Pauta, Etapa e Território devem receber false. */
        showAllOptions: { type: Boolean, default: true },
        /** Texto do checkbox "Não se aplica". Se não informado, usa a opção registrada ou fallback. */
        notApplicableLabel: { type: String, default: '' },
        classes: { type: [String, Array, Object], default: null },
        autosave: { type: Number, default: 0 },
    },

    data() {
        return {
            propId: 'custom-multiselect-' + this.prop + '-' + Math.random().toString(36).slice(2, 9),
            readonly: false,
        };
    },

    computed: {
        description() {
            return this.entity?.$PROPERTIES?.[this.prop] || null;
        },
        valueArray() {
            let val = this.entity?.[this.prop];
            if (val == null || val === '') return [];
            if (!Array.isArray(val)) return typeof val === 'string' ? val.split(';') : [val];
            return val;
        },
        optionsForSelect() {
            const opts = this.description?.options;
            if (!opts) return {};
            const out = {};
            for (const k of Object.keys(opts)) {
                if (k !== NOT_APPLICABLE_KEY && k !== ALL_OPTIONS_KEY) out[k] = opts[k];
            }
            return out;
        },
        tagsForDisplay() {
            const arr = this.valueArray;
            if (!arr || !arr.length) return [];
            return arr.filter((k) => k !== NOT_APPLICABLE_KEY && k !== ALL_OPTIONS_KEY);
        },
        isNotApplicable() {
            const arr = this.valueArray;
            return arr && arr.length === 1 && arr[0] === NOT_APPLICABLE_KEY;
        },
        notApplicableText() {
            if (this.notApplicableLabel && this.notApplicableLabel.trim() !== '') {
                return this.notApplicableLabel;
            }
            return this.description?.options?.[NOT_APPLICABLE_KEY] || 'Edital não se direciona a segmentos específicos';
        },
        isAllOptionsSelected() {
            return this.valueArray && this.valueArray.includes(ALL_OPTIONS_KEY);
        },
        allOptionsLabel() {
            return this.description?.options?.[ALL_OPTIONS_KEY] || 'Todas as opções';
        },
        isOutrosSelected() {
            const key = this.getOutrosKey();
            return key !== null && this.valueArray && this.valueArray.includes(key);
        },
        hasErrors() {
            const err = this.entity?.__validationErrors?.[this.prop];
            return Array.isArray(err) && err.length > 0;
        },
        errorsText() {
            const err = this.entity?.__validationErrors?.[this.prop];
            return Array.isArray(err) ? err.join('; ') : '';
        },
    },

    watch: {
        valueArray: {
            handler(arr) {
                if (!this.entity || !arr || arr === this.entity[this.prop]) return;
                if (!Array.isArray(this.entity[this.prop])) {
                    this.entity[this.prop] = Array.isArray(arr) ? [...arr] : [];
                }
            },
            deep: true,
        },
        isOutrosSelected: {
            handler(mostrar) {
                if (!this.outrosProp || !mostrar || !this.entity) return;
                const val = this.entity[this.outrosProp];
                if (val === null || val === undefined) {
                    this.entity[this.outrosProp] = '';
                }
            },
        },
    },

    created() {
        if (this.entity && (this.entity[this.prop] === null || this.entity[this.prop] === undefined)) {
            this.entity[this.prop] = [];
        } else if (this.entity && typeof this.entity[this.prop] !== 'object') {
            this.entity[this.prop] = this.entity[this.prop] ? String(this.entity[this.prop]).split(';') : [];
        }
    },

    methods: {
        getOutrosKey() {
            const opts = this.description?.options;
            if (!opts) return null;
            for (const k of Object.keys(opts)) {
                if (k === ALL_OPTIONS_KEY || k === NOT_APPLICABLE_KEY) continue;
                const label = typeof opts[k] === 'string' ? opts[k].toLowerCase() : '';
                if (label === 'outros' || label === 'outros (especificar)' || label === 'outra (especificar)') return k;
            }
            return null;
        },

        onSelected() {
            this.triggerSave();
        },

        onRemovedFromSelect() {
            this.triggerSave();
        },

        onRemove(tag) {
            if (!this.entity[this.prop] || !Array.isArray(this.entity[this.prop])) return;
            const idx = this.entity[this.prop].indexOf(tag);
            if (idx >= 0) {
                this.entity[this.prop].splice(idx, 1);
                if (this.outrosProp && tag === this.getOutrosKey()) {
                    this.entity[this.outrosProp] = null;
                }
            }
            this.triggerSave();
        },

        onNotApplicableChange(e) {
            if (e.target.checked) {
                this.entity[this.prop] = [NOT_APPLICABLE_KEY];
                if (this.outrosProp) this.entity[this.outrosProp] = null;
            } else {
                this.entity[this.prop] = [];
            }
            this.triggerSave();
        },

        onAllOptionsChange(e) {
            if (e.target.checked) {
                const opts = this.description?.options;
                if (!opts) return;
                const allKeys = Object.keys(opts);
                const outrosKey = this.getOutrosKey();
                const arr = [ALL_OPTIONS_KEY];
                allKeys.forEach((k) => {
                    if (k === NOT_APPLICABLE_KEY || k === ALL_OPTIONS_KEY) return;
                    if (outrosKey !== null && k === outrosKey) return;
                    arr.push(k);
                });
                this.entity[this.prop] = arr;
                if (this.outrosProp) this.entity[this.outrosProp] = null;
            } else {
                this.entity[this.prop] = [];
                if (this.outrosProp) this.entity[this.outrosProp] = null;
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
