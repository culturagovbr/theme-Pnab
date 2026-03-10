/**
 * Componente: Reserva de vagas (cotas)
 * Estrutura: 3 cotas obrigatórias por lei (índices 0,1,2) + cotas extras + 1 cota fixa "Ampla concorrência" sempre por último.
 */
const NUM_COTAS_LEI = 3;

/** Identificadores de campo para metadata (data-field-identifier) e detecção de erro */
const COTA_FIELD_NEGRAS = 'negras';
const COTA_FIELD_INDIGENAS = 'indigenas';
const COTA_FIELD_PCD = 'pcd';
const COTA_FIELD_AMPLA = 'ampla-concorrencia';
const COTA_FIELD_EXTRA_PREFIX = 'extra-';

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
        totalVagas() {
            const arr = this.cotas;
            return arr.reduce((acc, c) => acc + (Number(c.vagas) || 0), 0);
        },
        totalValorDestinado() {
            const arr = this.cotas;
            return arr.reduce((acc, c) => acc + (Number(c.valorDestinado) || 0), 0);
        },
        totalValorDestinadoFormatted() {
            const n = this.totalValorDestinado;
            if (!Number.isFinite(n)) return 'R$ 0,00';
            return 'R$ ' + n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        totalPercentualFormatted() {
            const total = this.entity.vacancies;
            if (total == null || total === '' || Number(total) <= 0) {
                return '—';
            }
            const arr = this.cotas;
            const sumPct = arr.reduce((acc, c) => {
                if (c.naoAplicavel) return acc;
                const n = Number(c.vagas) || 0;
                return acc + (n / Number(total)) * 100;
            }, 0);
            return sumPct.toFixed(1).replace('.', ',') + '%';
        },
    },

    created() {
        const labelsLei = [this.text('labelCota1'), this.text('labelCota2'), this.text('labelCota3')];
        const labelAmpla = this.text('labelAmplaConcorrencia');
        ensureCotas(this.entity, labelsLei, labelAmpla);
    },

    methods: {
        recalcAmplaConcorrencia() {
            const total = Number(this.entity.vacancies) || 0;
            const arr = Array.isArray(this.entity.reservaVagasCotas) ? [...this.entity.reservaVagasCotas] : [];
            const totalCotas = arr.length;
            if (totalCotas === 0) {
                return;
            }

            const lastIndex = totalCotas - 1;
            let somaOutras = 0;
            arr.forEach((cota, idx) => {
                if (idx !== lastIndex) {
                    somaOutras += Number(cota.vagas) || 0;
                }
            });

            let novaAmplaVagas = total - somaOutras;
            if (!Number.isFinite(novaAmplaVagas)) {
                novaAmplaVagas = 0;
            }
            if (novaAmplaVagas < 0) {
                novaAmplaVagas = 0;
            }

            const amplaAtual = arr[lastIndex] || defaultCota(this.text('labelAmplaConcorrencia'));
            if ((Number(amplaAtual.vagas) || 0) !== novaAmplaVagas) {
                const novaAmpla = {
                    ...amplaAtual,
                    vagas: novaAmplaVagas,
                };
                this.entity.reservaVagasCotas = [
                    ...arr.slice(0, lastIndex),
                    novaAmpla,
                ];
            }
        },
        getCotaLabels() {
            const t = this.text;
            return [t('labelCota1'), t('labelCota2'), t('labelCota3')];
        },
        autoSave() {
            this.entity.save(3000);
        },
        /** Remove o erro de reservaVagasCotas para a borda voltar ao normal; se o save falhar, o erro volta. */
        clearReservaVagasCotasError() {
            if (!this.entity.__validationErrors || !this.entity.__validationErrors.reservaVagasCotas) {
                return;
            }
            const next = { ...this.entity.__validationErrors };
            delete next.reservaVagasCotas;
            this.entity.__validationErrors = next;
        },
        onBlurField(index) {
            if (!this.isPendingNewCota(index)) {
                if (this.isAmplaConcorrencia(index)) {
                    const cota = this.entity.reservaVagasCotas[index];
                    if (cota && ((Number(cota.vagas) || 0) > 0 || (Number(cota.valorDestinado) || 0) > 0)) {
                        cota.naoAplicavel = false;
                    }
                } else {
                    this.recalcAmplaConcorrencia();
                }
                this.clearReservaVagasCotasError();
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
            const arr = this.entity.reservaVagasCotas;
            if (!Array.isArray(arr)) {
                return;
            }
            const idx = arr.indexOf(cota);
            if (idx === -1) {
                return;
            }

            if (cota.naoAplicavel) {
                const updated = {
                    ...cota,
                    vagas: 0,
                    valorDestinado: 0,
                };
                arr.splice(idx, 1, updated);
            }

            this.$nextTick(() => {
                const isAmpla = idx === arr.length - 1;
                if (!isAmpla) {
                    this.recalcAmplaConcorrencia();
                }
                this.clearReservaVagasCotasError();
                this.autoSave();
            });
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
            this.$nextTick(() => {
                this.recalcAmplaConcorrencia();
                this.autoSave();
            });
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
            this.$nextTick(() => {
                this.recalcAmplaConcorrencia();
                this.autoSave();
            });
        },
        hasErrorForIndex(index) {
            if (!this.hasError) {
                return false;
            }
            const msg = this.errorMessage;
            if (typeof msg !== 'string') {
                return false;
            }
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            if (total === 0) {
                return false;
            }

            // Erro de soma: não cita cota específica; destaca a linha Ampla concorrência (última).
            if (msg.indexOf('A soma das vagas reservadas às cotas deve ser igual ao Total de vagas') !== -1) {
                return index === total - 1;
            }

            const cota = Array.isArray(this.entity.reservaVagasCotas)
                ? this.entity.reservaVagasCotas[index]
                : null;
            if (!cota) {
                return false;
            }
            // Mensagem cita o label entre aspas; tenta casar pelo texto exato.
            const label = cota.label ? String(cota.label).trim() : '';
            if (label && msg.indexOf(`"${label}"`) !== -1) {
                return true;
            }
            // Fallback: identifica pela cota da lei usando trecho único da mensagem do backend.
            if (index === 0 && msg.includes('negras')) return true;
            if (index === 1 && msg.includes('indígenas')) return true;
            if (index === 2 && msg.includes('deficiência')) return true;
            return false;
        },
        /** Retorna o identificador do campo para data-field-identifier (negras, indigenas, pcd, ampla-concorrencia, extra-N). */
        getFieldIdentifier(index) {
            if (index < NUM_COTAS_LEI) {
                const ids = [COTA_FIELD_NEGRAS, COTA_FIELD_INDIGENAS, COTA_FIELD_PCD];
                return ids[index];
            }
            const total = this.entity.reservaVagasCotas?.length ?? 0;
            if (total > 0 && index === total - 1) {
                return COTA_FIELD_AMPLA;
            }
            return COTA_FIELD_EXTRA_PREFIX + index;
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
