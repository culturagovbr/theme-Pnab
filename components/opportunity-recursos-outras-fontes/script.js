/**
 * Componente: Utilização de recursos de outras fontes (edição de oportunidade).
 * Gerencia o metadado recursosOutrasFontes (objeto). Os dados são salvos junto com o formulário da oportunidade.
 */
app.component('opportunity-recursos-outras-fontes', {
    template: $TEMPLATES['opportunity-recursos-outras-fontes'],

    props: {
        entity: {
            type: Object,
            required: true
        }
    },

    setup() {
        const text = Utils.getTexts('opportunity-recursos-outras-fontes');
        return { text };
    },

    computed: {
        data() {
            const raw = this.entity.recursosOutrasFontes;
            return this.normalizeData(raw);
        },
        houveUtilizacao: {
            get() { return this.data.houveUtilizacao || 'nao'; },
            set(val) {
                this.ensureData();
                this.entity.recursosOutrasFontes.houveUtilizacao = val;
                if (val === 'nao') {
                    this.limparDetalhamento();
                }
            }
        },
        isSim() {
            return this.data.houveUtilizacao === 'sim';
        },
        recursosPropriosChecked: {
            get() { return this.data.recursosProprios !== null; },
            set(checked) {
                this.ensureData();
                this.entity.recursosOutrasFontes.recursosProprios = checked ? 0 : null;
            }
        },
        conveniosParceriasChecked: {
            get() { return this.data.conveniosParcerias !== null; },
            set(checked) {
                this.ensureData();
                this.entity.recursosOutrasFontes.conveniosParcerias = checked ? 0 : null;
            }
        },
        emendasParlamentaresChecked: {
            get() { return this.data.emendasParlamentares !== null; },
            set(checked) {
                this.ensureData();
                this.entity.recursosOutrasFontes.emendasParlamentares = checked ? 0 : null;
            }
        },
        remanescentesCiclo1Checked: {
            get() { return this.data.remanescentesCiclo1 !== null; },
            set(checked) {
                this.ensureData();
                this.entity.recursosOutrasFontes.remanescentesCiclo1 = checked ? 0 : null;
            }
        },
        outrasFontesChecked: {
            get() {
                const arr = this.entity.recursosOutrasFontes?.outrasFontes;
                return Array.isArray(arr);
            },
            set(checked) {
                this.ensureData();
                if (checked) {
                    this.entity.recursosOutrasFontes.outrasFontes = [
                        { nomeFonte: '', valor: 0, _id: this.uid() }
                    ];
                } else {
                    this.entity.recursosOutrasFontes.outrasFontes = null;
                }
            }
        },
        algumaFonteMarcada() {
            const d = this.data;
            if (d.recursosProprios !== null || d.conveniosParcerias !== null ||
                d.emendasParlamentares !== null || d.remanescentesCiclo1 !== null) return true;
            const of = d.outrasFontes;
            return Array.isArray(of) && of.length > 0;
        },
        podeIncluirOutraFonte() {
            const of = this.data.outrasFontes;
            if (!Array.isArray(of) || of.length === 0) return true;
            return of.every(e => (e.nomeFonte || '').trim() !== '');
        },
        hasError() {
            const err = this.entity.__validationErrors?.recursosOutrasFontes;
            return Array.isArray(err) && err.length > 0;
        },
        errorMessage() {
            const err = this.entity.__validationErrors?.recursosOutrasFontes;
            return Array.isArray(err) && err.length > 0 ? err[0] : '';
        },
        outrasFontesList() {
            const of = this.entity.recursosOutrasFontes?.outrasFontes;
            return Array.isArray(of) ? of : [];
        }
    },

    created() {
        this.ensureData();
        this.ensureOutrasFontesIds();
    },

    watch: {
        'entity.recursosOutrasFontes.outrasFontes': {
            handler(arr) {
                if (Array.isArray(arr)) this.ensureOutrasFontesIds();
            },
            deep: true
        }
    },

    methods: {
        uid() {
            return 'rf-' + Math.random().toString(36).slice(2, 11);
        },
        getDefaultData() {
            return {
                houveUtilizacao: 'nao',
                recursosProprios: null,
                conveniosParcerias: null,
                emendasParlamentares: null,
                remanescentesCiclo1: null,
                outrasFontes: null
            };
        },
        normalizeData(raw) {
            if (raw === null || raw === undefined) return this.getDefaultData();
            if (typeof raw === 'object' && !Array.isArray(raw)) {
                const d = { ...this.getDefaultData(), ...raw };
                if (Array.isArray(d.outrasFontes)) {
                    d.outrasFontes = d.outrasFontes.map(e => ({
                        nomeFonte: e.nomeFonte ?? '',
                        valor: typeof e.valor === 'number' ? e.valor : this.parseValor(e.valor),
                        _id: e._id || this.uid()
                    }));
                }
                return d;
            }
            return this.getDefaultData();
        },
        ensureData() {
            if (!this.entity.recursosOutrasFontes || typeof this.entity.recursosOutrasFontes !== 'object') {
                this.entity.recursosOutrasFontes = { ...this.getDefaultData() };
            }
        },
        ensureOutrasFontesIds() {
            const arr = this.entity.recursosOutrasFontes?.outrasFontes;
            if (Array.isArray(arr)) {
                arr.forEach(e => {
                    if (!e._id) e._id = this.uid();
                });
            }
        },
        limparDetalhamento() {
            this.ensureData();
            const d = this.entity.recursosOutrasFontes;
            d.recursosProprios = null;
            d.conveniosParcerias = null;
            d.emendasParlamentares = null;
            d.remanescentesCiclo1 = null;
            d.outrasFontes = null;
        },
        parseValor(str) {
            if (str === null || str === undefined) return 0;
            if (typeof str === 'number' && !Number.isNaN(str)) return Math.max(0, str);
            let s = String(str).replace(/\s/g, '').trim();
            if (s === '' || s === ',' || s === '.') return 0;
            s = s.replace(/\./g, '').replace(',', '.');
            const n = parseFloat(s);
            return Number.isNaN(n) ? 0 : Math.max(0, n);
        },
        onCurrencyChange(value, key) {
            this.ensureData();
            const num = typeof value === 'number' ? Math.max(0, value) : this.parseValor(value);
            this.entity.recursosOutrasFontes[key] = num;
        },
        onOutraFonteCurrencyChange(index, value) {
            const entrada = this.outrasFontesList[index];
            if (!entrada) return;
            const num = typeof value === 'number' ? Math.max(0, value) : this.parseValor(value);
            entrada.valor = num;
        },
        incluirOutraFonte() {
            if (!this.podeIncluirOutraFonte) return;
            this.ensureData();
            let arr = this.entity.recursosOutrasFontes.outrasFontes;
            if (!Array.isArray(arr)) arr = [];
            arr = [...arr, { nomeFonte: '', valor: 0, _id: this.uid() }];
            this.entity.recursosOutrasFontes.outrasFontes = arr;
        },
        removerOutraFonte(index) {
            this.ensureData();
            const arr = this.entity.recursosOutrasFontes.outrasFontes;
            if (!Array.isArray(arr) || index < 0 || index >= arr.length) return;
            const next = arr.filter((_, i) => i !== index);
            this.entity.recursosOutrasFontes.outrasFontes = next.length > 0 ? next : null;
        }
    }
});
