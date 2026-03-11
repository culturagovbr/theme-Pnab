/**
 * Reserva de vagas (cotas). 3 cotas da lei (0,1,2) + extras + Ampla concorrência sempre por último.
 */
const NUM_LAW_QUOTAS = 3;

/** data-field-identifier e detecção de erro por linha */
const QUOTA_FIELD_BLACK = 'negras';
const QUOTA_FIELD_INDIGENOUS = 'indigenas';
const QUOTA_FIELD_PCD = 'pcd';
const QUOTA_FIELD_GENERAL_COMPETITION = 'ampla-concorrencia';
const QUOTA_FIELD_EXTRA_PREFIX = 'extra-';

function defaultQuota(label, notApplicable = true) {
    return {
        label,
        vagas: 0,
        valorDestinado: 0,
        naoAplicavel: !!notApplicable,
    };
}

function defaultLawQuotas(lawLabels) {
    return lawLabels.map((lawLabel) => defaultQuota(lawLabel));
}

function ensureQuotas(entity, lawLabels, generalCompetitionLabel) {
    let quotas = entity.reservaVagasCotas;
    const fixedStart = Array.isArray(quotas) && quotas.length >= NUM_LAW_QUOTAS
        ? quotas.slice(0, NUM_LAW_QUOTAS).map((quotaItem, lawIndex) => ({
            label: lawLabels[lawIndex] ?? (quotaItem.label || ''),
            vagas: typeof quotaItem.vagas === 'number' ? quotaItem.vagas : (parseInt(quotaItem.vagas, 10) || 0),
            valorDestinado: typeof quotaItem.valorDestinado === 'number' ? quotaItem.valorDestinado : (parseFloat(quotaItem.valorDestinado) || 0),
            naoAplicavel: Boolean(quotaItem.naoAplicavel),
        }))
        : defaultLawQuotas(lawLabels);

    let extras = [];
    let generalCompetition = defaultQuota(generalCompetitionLabel);

    if (Array.isArray(quotas) && quotas.length > NUM_LAW_QUOTAS) {
        const last = quotas[quotas.length - 1];
        generalCompetition = {
            label: generalCompetitionLabel,
            vagas: typeof last.vagas === 'number' ? last.vagas : (parseInt(last.vagas, 10) || 0),
            valorDestinado: typeof last.valorDestinado === 'number' ? last.valorDestinado : (parseFloat(last.valorDestinado) || 0),
            naoAplicavel: Boolean(last.naoAplicavel),
        };
        if (quotas.length > NUM_LAW_QUOTAS + 1) {
            extras = quotas.slice(NUM_LAW_QUOTAS, -1).map((quotaItem) => ({
                label: typeof quotaItem.label === 'string' ? quotaItem.label.trim() : '',
                vagas: typeof quotaItem.vagas === 'number' ? quotaItem.vagas : (parseInt(quotaItem.vagas, 10) || 0),
                valorDestinado: typeof quotaItem.valorDestinado === 'number' ? quotaItem.valorDestinado : (parseFloat(quotaItem.valorDestinado) || 0),
            }));
        }
    }

    entity.reservaVagasCotas = [...fixedStart, ...extras, generalCompetition];
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
            pendingNewQuotaIndex: null,
        };
    },

    computed: {
        quotas() {
            const arr = this.entity.reservaVagasCotas;
            const minLength = NUM_LAW_QUOTAS + 1;
            return Array.isArray(arr) && arr.length >= minLength ? arr : [];
        },
        fixedQuotas() {
            return this.quotas.slice(0, NUM_LAW_QUOTAS);
        },
        extraQuotas() {
            return this.quotas.length > NUM_LAW_QUOTAS + 1 ? this.quotas.slice(NUM_LAW_QUOTAS, -1) : [];
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
            const textFn = this.text;
            return [textFn('infoBlockTitle'), textFn('infoBlockItem1'), textFn('infoBlockItem2'), textFn('infoBlockItem3')].join('\n');
        },
        totalVagas() {
            const arr = this.quotas;
            return arr.reduce((sum, quota) => sum + (Number(quota.vagas) || 0), 0);
        },
        totalAllocatedValue() {
            const arr = this.quotas;
            return arr.reduce((sum, quota) => sum + (Number(quota.valorDestinado) || 0), 0);
        },
        totalAllocatedValueFormatted() {
            const totalValue = this.totalAllocatedValue;
            if (!Number.isFinite(totalValue)) return 'R$ 0,00';
            return 'R$ ' + totalValue.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        totalPercentFormatted() {
            const totalVacancies = this.entity.vacancies;
            if (totalVacancies == null || totalVacancies === '' || Number(totalVacancies) <= 0) {
                return '—';
            }
            const arr = this.quotas;
            const sumPct = arr.reduce((sum, quota) => {
                if (quota.naoAplicavel) return sum;
                const slotCount = Number(quota.vagas) || 0;
                return sum + (slotCount / Number(totalVacancies)) * 100;
            }, 0);
            return sumPct.toFixed(1).replace('.', ',') + '%';
        },
    },

    created() {
        const lawLabels = [this.text('labelCota1'), this.text('labelCota2'), this.text('labelCota3')];
        const generalCompetitionLabel = this.text('labelAmplaConcorrencia');
        ensureQuotas(this.entity, lawLabels, generalCompetitionLabel);
    },

    methods: {
        recalcGeneralCompetition() {
            const total = Number(this.entity.vacancies) || 0;
            const arr = Array.isArray(this.entity.reservaVagasCotas) ? [...this.entity.reservaVagasCotas] : [];
            const totalQuotas = arr.length;
            if (totalQuotas === 0) {
                return;
            }

            const lastIndex = totalQuotas - 1;
            let othersSum = 0;
            arr.forEach((quota, index) => {
                if (index !== lastIndex) {
                    othersSum += Number(quota.vagas) || 0;
                }
            });

            let newGeneralSlots = total - othersSum;
            if (!Number.isFinite(newGeneralSlots)) {
                newGeneralSlots = 0;
            }
            if (newGeneralSlots < 0) {
                newGeneralSlots = 0;
            }

            const currentGeneral = arr[lastIndex] || defaultQuota(this.text('labelAmplaConcorrencia'));
            if ((Number(currentGeneral.vagas) || 0) !== newGeneralSlots) {
                const newGeneral = {
                    ...currentGeneral,
                    vagas: newGeneralSlots,
                };
                this.entity.reservaVagasCotas = [
                    ...arr.slice(0, lastIndex),
                    newGeneral,
                ];
            }
        },
        getQuotaLabels() {
            const textFn = this.text;
            return [textFn('labelCota1'), textFn('labelCota2'), textFn('labelCota3')];
        },
        autoSave() {
            this.entity.save(3000);
        },
        clearQuotasReservationError() {
            if (!this.entity.__validationErrors || !this.entity.__validationErrors.reservaVagasCotas) {
                return;
            }
            const next = { ...this.entity.__validationErrors };
            delete next.reservaVagasCotas;
            this.entity.__validationErrors = next;
        },
        onBlurField(quotaIndex) {
            if (!this.isPendingNewQuota(quotaIndex)) {
                if (this.isGeneralCompetition(quotaIndex)) {
                    const quota = this.entity.reservaVagasCotas[quotaIndex];
                    if (quota && ((Number(quota.vagas) || 0) > 0 || (Number(quota.valorDestinado) || 0) > 0)) {
                        quota.naoAplicavel = false;
                    }
                } else {
                    this.recalcGeneralCompetition();
                }
                this.clearQuotasReservationError();
                this.autoSave();
            }
        },
        quotaPercent(quota) {
            const totalVacancies = this.entity.vacancies;
            if (totalVacancies == null || totalVacancies === '' || Number(totalVacancies) <= 0) {
                return '—';
            }
            const slotCount = Number(quota.vagas) || 0;
            if (quota.naoAplicavel) {
                return '—';
            }
            const percentValue = (slotCount / Number(totalVacancies)) * 100;
            return percentValue.toFixed(1).replace('.', ',') + '%';
        },
        onNotApplicableChange(quota) {
            const arr = this.entity.reservaVagasCotas;
            if (!Array.isArray(arr)) {
                return;
            }
            const quotaIndex = arr.indexOf(quota);
            if (quotaIndex === -1) {
                return;
            }

            if (quota.naoAplicavel) {
                const updated = {
                    ...quota,
                    vagas: 0,
                    valorDestinado: 0,
                };
                arr.splice(quotaIndex, 1, updated);
            }

            this.$nextTick(() => {
                const isGeneral = quotaIndex === arr.length - 1;
                if (!isGeneral) {
                    this.recalcGeneralCompetition();
                }
                this.clearQuotasReservationError();
                this.autoSave();
            });
        },
        addQuota() {
            if (this.pendingNewQuotaIndex !== null) return;
            const arr = [...this.entity.reservaVagasCotas];
            arr.splice(arr.length - 1, 0, { label: '', vagas: 0, valorDestinado: 0 });
            this.entity.reservaVagasCotas = arr;
            this.pendingNewQuotaIndex = arr.length - 2;
        },
        confirmNewQuota() {
            if (this.pendingNewQuotaIndex === null) return;
            this.pendingNewQuotaIndex = null;
            this.$nextTick(() => {
                this.recalcGeneralCompetition();
                this.autoSave();
            });
        },
        cancelNewQuota() {
            if (this.pendingNewQuotaIndex === null) return;
            const pendingIndex = this.pendingNewQuotaIndex;
            this.pendingNewQuotaIndex = null;
            const arr = [...this.entity.reservaVagasCotas];
            arr.splice(pendingIndex, 1);
            this.entity.reservaVagasCotas = arr;
            this.$nextTick(() => this.autoSave());
        },
        isPendingNewQuota(quotaIndex) {
            return this.pendingNewQuotaIndex === quotaIndex;
        },
        removeQuota(quotaIndex) {
            if (quotaIndex < NUM_LAW_QUOTAS || quotaIndex === this.entity.reservaVagasCotas.length - 1) return;
            const arr = [...this.entity.reservaVagasCotas];
            arr.splice(quotaIndex, 1);
            this.entity.reservaVagasCotas = arr;
            this.$nextTick(() => {
                this.recalcGeneralCompetition();
                this.autoSave();
            });
        },
        hasErrorForIndex(quotaIndex) {
            if (!this.hasError) {
                return false;
            }
            const msg = this.errorMessage;
            if (typeof msg !== 'string') {
                return false;
            }
            const totalCount = this.entity.reservaVagasCotas?.length ?? 0;
            if (totalCount === 0) {
                return false;
            }

            if (msg.indexOf('A soma das vagas reservadas às cotas deve ser igual ao Total de vagas') !== -1) {
                return quotaIndex === totalCount - 1;
            }

            const quota = Array.isArray(this.entity.reservaVagasCotas)
                ? this.entity.reservaVagasCotas[quotaIndex]
                : null;
            if (!quota) {
                return false;
            }
            const label = quota.label ? String(quota.label).trim() : '';
            if (label && msg.indexOf(`"${label}"`) !== -1) {
                return true;
            }
            if (quotaIndex === 0 && msg.includes('negras')) return true;
            if (quotaIndex === 1 && msg.includes('indígenas')) return true;
            if (quotaIndex === 2 && msg.includes('deficiência')) return true;
            return false;
        },
        getFieldIdentifier(quotaIndex) {
            if (quotaIndex < NUM_LAW_QUOTAS) {
                const ids = [QUOTA_FIELD_BLACK, QUOTA_FIELD_INDIGENOUS, QUOTA_FIELD_PCD];
                return ids[quotaIndex];
            }
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            if (total > 0 && quotaIndex === total - 1) {
                return QUOTA_FIELD_GENERAL_COMPETITION;
            }
            return QUOTA_FIELD_EXTRA_PREFIX + quotaIndex;
        },
        isFixedQuota(quotaIndex) {
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            return quotaIndex < NUM_LAW_QUOTAS || quotaIndex === total - 1;
        },
        isLawQuota(quotaIndex) {
            return quotaIndex < NUM_LAW_QUOTAS;
        },
        isGeneralCompetition(quotaIndex) {
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            return total > 0 && quotaIndex === total - 1;
        },
        isExtraQuota(quotaIndex) {
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            return quotaIndex >= NUM_LAW_QUOTAS && quotaIndex < total - 1;
        },
    },
});
