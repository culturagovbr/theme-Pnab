/**
 * Componente: Reserva de vagas (cotas)
 * Metadado reservaVagasCotas: 3 cotas obrigatórias por lei (índices 0,1,2) + cotas extras (índice 3+).
 */
const NUM_COTAS_FIXAS = 3;

function defaultCotas(labels) {
    return labels.map((label) => ({
        label,
        vagas: 0,
        valorDestinado: 0,
        naoAplicavel: true,
    }));
}

function ensureCotas(entity, labels) {
    let cotas = entity.reservaVagasCotas;
    if (!Array.isArray(cotas) || cotas.length < NUM_COTAS_FIXAS) {
        entity.reservaVagasCotas = defaultCotas(labels);
        return;
    }
    const fixed = cotas.slice(0, NUM_COTAS_FIXAS).map((c, i) => ({
        label: labels[i] ?? (c.label || ''),
        vagas: typeof c.vagas === 'number' ? c.vagas : (parseInt(c.vagas, 10) || 0),
        valorDestinado: typeof c.valorDestinado === 'number' ? c.valorDestinado : (parseFloat(c.valorDestinado) || 0),
        naoAplicavel: Boolean(c.naoAplicavel),
    }));
    const extras = cotas.slice(NUM_COTAS_FIXAS).map((c) => ({
        label: typeof c.label === 'string' ? c.label.trim() : '',
        vagas: typeof c.vagas === 'number' ? c.vagas : (parseInt(c.vagas, 10) || 0),
        valorDestinado: typeof c.valorDestinado === 'number' ? c.valorDestinado : (parseFloat(c.valorDestinado) || 0),
    }));
    entity.reservaVagasCotas = [...fixed, ...extras];
}

app.component('opportunity-reserva-vagas-cotas', {
    template: $TEMPLATES['opportunity-reserva-vagas-cotas'],

    props: {
        entity: {
            type: Object,
            required: true,
        },
    },

    setup() {
        const text = Utils.getTexts('opportunity-reserva-vagas-cotas');
        return { text };
    },

    computed: {
        cotas() {
            const arr = this.entity.reservaVagasCotas;
            return Array.isArray(arr) && arr.length >= NUM_COTAS_FIXAS ? arr : [];
        },
        cotasFixas() {
            return this.cotas.slice(0, NUM_COTAS_FIXAS);
        },
        cotasExtras() {
            return this.cotas.slice(NUM_COTAS_FIXAS);
        },
        hasError() {
            const err = this.entity.__validationErrors?.reservaVagasCotas;
            return Array.isArray(err) && err.length > 0;
        },
        errorMessage() {
            const err = this.entity.__validationErrors?.reservaVagasCotas;
            return Array.isArray(err) && err.length > 0 ? err[0] : '';
        },
        hintPercentuais() {
            const t = this.text;
            return [t('infoBlockTitle'), t('infoBlockItem1'), t('infoBlockItem2'), t('infoBlockItem3')].join('\n');
        },
    },

    created() {
        ensureCotas(this.entity, this.getCotaLabels());
    },

    methods: {
        getCotaLabels() {
            const t = this.text;
            return [t('labelCota1'), t('labelCota2'), t('labelCota3')];
        },
        autoSave() {
            this.entity.save(3000);
        },
        percentualCota(cota) {
            const total = this.entity.vacancies;
            if (total == null || total === '' || Number(total) <= 0) {
                return '—';
            }
            const n = Number(cota.vagas) || 0;
            if (cota.naoAplicavel) {
                return '—';
            }
            const pct = (n / Number(total)) * 100;
            return pct.toFixed(1).replace('.', ',') + '%';
        },
        onNaoAplicavelChange(cota) {
            if (cota.naoAplicavel) {
                const idx = this.entity.reservaVagasCotas.indexOf(cota);
                if (idx !== -1) {
                    this.entity.reservaVagasCotas[idx] = {
                        ...cota,
                        vagas: 0,
                        valorDestinado: 0,
                    };
                } else {
                    cota.vagas = 0;
                    cota.valorDestinado = 0;
                }
            }
            this.$nextTick(() => this.autoSave());
        },
        addCota() {
            this.entity.reservaVagasCotas = [
                ...this.entity.reservaVagasCotas,
                { label: '', vagas: 0, valorDestinado: 0 },
            ];
            this.$nextTick(() => this.autoSave());
        },
        removeCota(index) {
            if (index < NUM_COTAS_FIXAS) return;
            const arr = [...this.entity.reservaVagasCotas];
            arr.splice(index, 1);
            this.entity.reservaVagasCotas = arr;
            this.$nextTick(() => this.autoSave());
        },
        isCotaFixa(index) {
            return index < NUM_COTAS_FIXAS;
        },
    },
});
