/**
 * Componente: Reserva de vagas (cotas)
 * Estrutura: 3 cotas obrigatórias por lei (índices 0,1,2) + cotas extras + 1 cota fixa "Ampla concorrência" sempre por último.
 */
const NUM_COTAS_LEI = 3;

function defaultCota(label, naoAplicavel = true) {
    return {
        label,
        vagas: 0,
        valorDestinado: 0,
        naoAplicavel: !!naoAplicavel,
    };
}

function defaultCotasLei(labelsLei) {
    return labelsLei.map((label) => defaultCota(label));
}

function ensureCotas(entity, labelsLei, labelAmpla) {
    let cotas = entity.reservaVagasCotas;
    const fixedStart = Array.isArray(cotas) && cotas.length >= NUM_COTAS_LEI
        ? cotas.slice(0, NUM_COTAS_LEI).map((c, i) => ({
            label: labelsLei[i] ?? (c.label || ''),
            vagas: typeof c.vagas === 'number' ? c.vagas : (parseInt(c.vagas, 10) || 0),
            valorDestinado: typeof c.valorDestinado === 'number' ? c.valorDestinado : (parseFloat(c.valorDestinado) || 0),
            naoAplicavel: Boolean(c.naoAplicavel),
        }))
        : defaultCotasLei(labelsLei);

    let extras = [];
    let ampla = defaultCota(labelAmpla);

    if (Array.isArray(cotas) && cotas.length > NUM_COTAS_LEI) {
        const ultima = cotas[cotas.length - 1];
        ampla = {
            label: labelAmpla,
            vagas: typeof ultima.vagas === 'number' ? ultima.vagas : (parseInt(ultima.vagas, 10) || 0),
            valorDestinado: typeof ultima.valorDestinado === 'number' ? ultima.valorDestinado : (parseFloat(ultima.valorDestinado) || 0),
            naoAplicavel: Boolean(ultima.naoAplicavel),
        };
        if (cotas.length > NUM_COTAS_LEI + 1) {
            extras = cotas.slice(NUM_COTAS_LEI, -1).map((c) => ({
                label: typeof c.label === 'string' ? c.label.trim() : '',
                vagas: typeof c.vagas === 'number' ? c.vagas : (parseInt(c.vagas, 10) || 0),
                valorDestinado: typeof c.valorDestinado === 'number' ? c.valorDestinado : (parseFloat(c.valorDestinado) || 0),
            }));
        }
    }

    entity.reservaVagasCotas = [...fixedStart, ...extras, ampla];
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

    data() {
        return {
            pendingNewCotaIndex: null, // índice da cota extra em edição (aguardando confirmar ou cancelar)
        };
    },

    computed: {
        cotas() {
            const arr = this.entity.reservaVagasCotas;
            const minLength = NUM_COTAS_LEI + 1; // 3 lei + ampla concorrência
            return Array.isArray(arr) && arr.length >= minLength ? arr : [];
        },
        cotasFixas() {
            return this.cotas.slice(0, NUM_COTAS_LEI);
        },
        cotasExtras() {
            return this.cotas.length > NUM_COTAS_LEI + 1 ? this.cotas.slice(NUM_COTAS_LEI, -1) : [];
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
        const labelsLei = [this.text('labelCota1'), this.text('labelCota2'), this.text('labelCota3')];
        const labelAmpla = this.text('labelAmplaConcorrencia');
        ensureCotas(this.entity, labelsLei, labelAmpla);
    },

    methods: {
        getCotaLabels() {
            const t = this.text;
            return [t('labelCota1'), t('labelCota2'), t('labelCota3')];
        },
        autoSave() {
            this.entity.save(3000);
        },
        onBlurField(index) {
            if (!this.isPendingNewCota(index)) {
                this.autoSave();
            }
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
            if (this.pendingNewCotaIndex !== null) return;
            const arr = [...this.entity.reservaVagasCotas];
            arr.splice(arr.length - 1, 0, { label: '', vagas: 0, valorDestinado: 0 });
            this.entity.reservaVagasCotas = arr;
            this.pendingNewCotaIndex = arr.length - 2;
        },
        confirmNewCota() {
            if (this.pendingNewCotaIndex === null) return;
            this.pendingNewCotaIndex = null;
            this.$nextTick(() => this.autoSave());
        },
        cancelNewCota() {
            if (this.pendingNewCotaIndex === null) return;
            const idx = this.pendingNewCotaIndex;
            this.pendingNewCotaIndex = null;
            const arr = [...this.entity.reservaVagasCotas];
            arr.splice(idx, 1);
            this.entity.reservaVagasCotas = arr;
            this.$nextTick(() => this.autoSave());
        },
        isPendingNewCota(index) {
            return this.pendingNewCotaIndex === index;
        },
        removeCota(index) {
            if (index < NUM_COTAS_LEI || index === this.entity.reservaVagasCotas.length - 1) return;
            const arr = [...this.entity.reservaVagasCotas];
            arr.splice(index, 1);
            this.entity.reservaVagasCotas = arr;
            this.$nextTick(() => this.autoSave());
        },
        isCotaFixa(index) {
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            return index < NUM_COTAS_LEI || index === total - 1;
        },
        isCotaLei(index) {
            return index < NUM_COTAS_LEI;
        },
        isAmplaConcorrencia(index) {
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            return total > 0 && index === total - 1;
        },
        isCotaExtra(index) {
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            return index >= NUM_COTAS_LEI && index < total - 1;
        },
    },
});
