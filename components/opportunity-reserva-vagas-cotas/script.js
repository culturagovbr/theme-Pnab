/**
 * Componente: Reserva de vagas (cotas)
 * Metadado reservaVagasCotas (array de 3 itens fixos) na phase (primeira fase).
 * Labels vêm de texts.php (i18n).
 */
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
    if (!Array.isArray(cotas) || cotas.length !== 3) {
        entity.reservaVagasCotas = defaultCotas(labels);
        return;
    }
    // Garante labels fixos e estrutura em cada item
    entity.reservaVagasCotas = cotas.map((c, i) => ({
        label: labels[i] ?? (c.label || ''),
        vagas: typeof c.vagas === 'number' ? c.vagas : (parseInt(c.vagas, 10) || 0),
        valorDestinado: typeof c.valorDestinado === 'number' ? c.valorDestinado : (parseFloat(c.valorDestinado) || 0),
        naoAplicavel: Boolean(c.naoAplicavel),
    }));
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
            return Array.isArray(arr) && arr.length === 3 ? arr : [];
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
    },
});
