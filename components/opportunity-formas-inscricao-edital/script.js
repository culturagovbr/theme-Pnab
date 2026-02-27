/**
 * Componente: Formas de inscrição previstas no edital (edição de oportunidade).
 * Gerencia o metadado formasInscricaoEdital (objeto com previstasNoEdital e formas[]).
 */
const TIPOS_FORMAS = ['email', 'presencial', 'correspondencia', 'oralidade', 'outros'];

app.component('opportunity-formas-inscricao-edital', {
    template: $TEMPLATES['opportunity-formas-inscricao-edital'],

    props: {
        entity: {
            type: Object,
            required: true
        }
    },

    setup() {
        const text = Utils.getTexts('opportunity-formas-inscricao-edital');
        return { text, TIPOS_FORMAS };
    },

    computed: {
        data() {
            const raw = this.entity.formasInscricaoEdital;
            return this.normalizeData(raw);
        },
        previstasNoEdital: {
            get() { return this.data.previstasNoEdital || 'nao'; },
            set(val) {
                this.ensureData();
                this.entity.formasInscricaoEdital.previstasNoEdital = val;
                if (val === 'nao') {
                    this.entity.formasInscricaoEdital.formas = [];
                }
            }
        },
        isSim() {
            return this.data.previstasNoEdital === 'sim';
        },
        formasArray() {
            const arr = this.entity.formasInscricaoEdital?.formas;
            return Array.isArray(arr) ? arr : [];
        },
        algumaFormaMarcada() {
            return this.formasArray.length > 0;
        },
        todasDescricoesPreenchidas() {
            return this.formasArray.every(f => (f.descricao || '').trim() !== '');
        },
        hasError() {
            const err = this.entity.__validationErrors?.formasInscricaoEdital;
            return Array.isArray(err) && err.length > 0;
        },
        errorMessage() {
            const err = this.entity.__validationErrors?.formasInscricaoEdital;
            return Array.isArray(err) && err.length > 0 ? err[0] : '';
        }
    },

    created() {
        this.ensureData();
    },

    methods: {
        getDefaultData() {
            return {
                previstasNoEdital: 'nao',
                formas: []
            };
        },
        normalizeData(raw) {
            if (raw === null || raw === undefined) return this.getDefaultData();
            if (typeof raw === 'object' && !Array.isArray(raw)) {
                const d = { ...this.getDefaultData(), ...raw };
                if (Array.isArray(d.formas)) {
                    d.formas = d.formas.filter(f => f && TIPOS_FORMAS.includes(f.tipo));
                } else {
                    d.formas = [];
                }
                return d;
            }
            return this.getDefaultData();
        },
        ensureData() {
            if (!this.entity.formasInscricaoEdital || typeof this.entity.formasInscricaoEdital !== 'object') {
                this.entity.formasInscricaoEdital = { ...this.getDefaultData() };
            }
            if (!Array.isArray(this.entity.formasInscricaoEdital.formas)) {
                this.entity.formasInscricaoEdital.formas = [];
            }
        },
        isTipoMarcado(tipo) {
            return this.formasArray.some(f => f.tipo === tipo);
        },
        getDescricao(tipo) {
            const item = this.formasArray.find(f => f.tipo === tipo);
            return item ? (item.descricao || '') : '';
        },
        setMarcado(tipo, checked) {
            this.ensureData();
            let arr = [...(this.entity.formasInscricaoEdital.formas || [])];
            if (checked) {
                if (!arr.some(f => f.tipo === tipo)) {
                    arr.push({ tipo, descricao: '' });
                }
            } else {
                arr = arr.filter(f => f.tipo !== tipo);
            }
            this.entity.formasInscricaoEdital.formas = arr;
        },
        setDescricao(tipo, valor) {
            this.ensureData();
            const arr = this.entity.formasInscricaoEdital.formas;
            const item = arr.find(f => f.tipo === tipo);
            if (item) item.descricao = valor;
        }
    }
});
