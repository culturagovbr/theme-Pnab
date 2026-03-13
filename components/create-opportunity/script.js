app.component('create-opportunity', {
    template: $TEMPLATES['create-opportunity'],
    emits: ['create'],

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('create-opportunity')
        return { text }
    },

    data() {
        return {
            canAccess: $MAPAS.config.canAccess,
            entity: null,
            fields: [],
            entityTypeSelected: null,
            // PAR: exercício → meta → ação → atividade (dados do ente selecionado)
            parExercicios: [],
            parExercicioId: '',
            parMetaId: '',
            parAcaoId: '',
            parAtividadeId: '',
            parLoading: false,
            parErrors: { exercicio: false, meta: false, acao: false, atividade: false },
            createdEntity: null,
            showSuccessModal: false,
        }
    },

    props: {
        editable: {
            type: Boolean,
            default: true
        },
    },

    computed: {
        areaClasses() {
            return this.areaErrors ? 'field error' : 'field';
        },

        modalTitle() {
            if (!this.entity?.id) {
                return __('criarOportunidade', 'create-opportunity');
            }
            if (this.entity.status == 0) {
                return __('oportunidadeCriada', 'create-opportunity');
            }
        },

        entityType() {
            switch (this.entity.ownerEntity.__objectType) {
                case 'project':
                    return __('projeto', 'create-opportunity');
                case 'event':
                    return __('evento', 'create-opportunity');
                case 'space':
                    return __('espaço', 'create-opportunity');
                case 'agent':
                    return __('agente', 'create-opportunity');
            }
        },

        entityColorClass() {
            switch (this.entity.ownerEntity.__objectType) {
                case 'project':
                    return 'project__color';
                case 'event':
                    return 'event__color';
                case 'space':
                    return 'space__color';
                case 'agent':
                    return 'agent__color--dark';
            }
        },

        entityColorBorder() {
            switch (this.entity.ownerEntity.__objectType) {
                case 'project':
                    return 'project__border';
                case 'event':
                    return 'event__border';
                case 'space':
                    return 'space__border';
                case 'agent':
                    return 'agent__border--dark';
            }
        },

        // PAR: opções em cascata a partir do exercício selecionado
        parMetas() {
            if (!this.parExercicioId || !this.parExercicios.length) return [];
            const ex = this.parExercicios.find((e) => String(e.id) === String(this.parExercicioId));
            return ex && Array.isArray(ex.metas) ? ex.metas : [];
        },
        parAcoes() {
            if (!this.parMetaId || !this.parMetas.length) return [];
            const meta = this.parMetas.find((m) => String(m.id) === String(this.parMetaId));
            return meta && Array.isArray(meta.acoes) ? meta.acoes : [];
        },
        parAtividades() {
            if (!this.parAcaoId || !this.parAcoes.length) return [];
            const acao = this.parAcoes.find((a) => String(a.id) === String(this.parAcaoId));
            return acao && Array.isArray(acao.atividades) ? acao.atividades : [];
        },
    },

    methods: {
        handleSubmit(event) {
            event.preventDefault();
        },

        createEntity() {
            this.entity = new Entity('opportunity');
            this.entity.type = this.getOpportunityTypeIdByLabel('Edital');
            this.entity.tipoDeEdital = null;
            this.entity.terms = { area: [] };
            this.resetParSelection();
            this.fetchSelectedEnteExercicios();
        },

        fetchSelectedEnteExercicios() {
            this.parLoading = true;
            this.clearParErrors();
            const base = ($MAPAS.baseUrl || '').replace(/\/$/, '');
            const url = base + '/aldirblanc/selectedEnteExercicios';
            fetch(url, { credentials: 'include' })
                .then((r) => r.ok ? r.json() : null)
                .then((data) => {
                    this.parExercicios = data && Array.isArray(data.exercicios) ? data.exercicios : [];
                })
                .catch(() => {
                    this.parExercicios = [];
                })
                .finally(() => {
                    this.parLoading = false;
                });
        },

        clearParErrors() {
            this.parErrors = { exercicio: false, meta: false, acao: false, atividade: false };
        },

        resetParSelection() {
            this.parExercicioId = '';
            this.parMetaId = '';
            this.parAcaoId = '';
            this.parAtividadeId = '';
        },

        onParExercicioChange() {
            this.parMetaId = '';
            this.parAcaoId = '';
            this.parAtividadeId = '';
            this.clearParErrors();
        },
        onParMetaChange() {
            this.parAcaoId = '';
            this.parAtividadeId = '';
            this.clearParErrors();
        },
        onParAcaoChange() {
            this.parAtividadeId = '';
            this.clearParErrors();
        },

        applyParToEntity() {
            if (!this.entity) return;
            this.entity.parExercicioId = this.parExercicioId || null;
            this.entity.parMetaId = this.parMetaId || null;
            this.entity.parAcaoId = this.parAcaoId || null;
            this.entity.parAtividadeId = this.parAtividadeId || null;
        },

        validatePar() {
            this.clearParErrors();
            const e = { exercicio: !this.parExercicioId, meta: !this.parMetaId, acao: !this.parAcaoId, atividade: !this.parAtividadeId };
            const any = e.exercicio || e.meta || e.acao || e.atividade;
            if (any) {
                this.parErrors = e;
                return false;
            }
            return true;
        },

        parErrorMsg(key) {
            const msg = this.text?.['parCampoObrigatorio_' + key];
            if (msg) return msg;
            const labels = { exercicio: 'Exercício', meta: 'Meta', acao: 'Ação', atividade: 'Atividade' };
            return `O campo ${labels[key]} é obrigatório.`;
        },

        createDraft(modal) {
            if (!this.entity.ownerEntity && $MAPAS.user && $MAPAS.user.profile) {
                this.entity.ownerEntity = $MAPAS.user.profile;
            }

            if (!this.validatePar()) return;

            this.applyParToEntity();
            this.entity.status = 0;
            this.save(modal);
        },

        createPublic(modal) {
            if (!this.entity.ownerEntity && $MAPAS.user && $MAPAS.user.profile) {
                this.entity.ownerEntity = $MAPAS.user.profile;
            }

            if (!this.validatePar()) return;

            this.applyParToEntity();
            this.entity.status = 1;
            this.save(modal);
        },

        save(modal) {
            modal.loading(true);

            this.entity.save().then((response) => {
                this.createdEntity = this.entity;
                modal.loading(false);
                modal.close();
                this.showSuccessModal = true;
                this.$nextTick(() => {
                    this.$refs.successModal?.open();
                });
                this.$emit('create', response);
                Utils.pushEntityToList(this.entity);
            }).catch((e) => {
                modal.loading(false);
            });
        },

        onCloseSuccessModal() {
            this.showSuccessModal = false;
            this.createdEntity = null;
        },

        setEntity(Entity) {
            this.entity.ownerEntity = Entity;
        },

        resetEntity() {
            this.entity.ownerEntity = null;
            this.entityTypeSelected = null;
        },

        destroyEntity() {
            setTimeout(() => {
                this.entity = null;
                this.entityTypeSelected = null;
                this.resetParSelection();
                this.parExercicios = [];
                this.parLoading = false;
                this.clearParErrors();
                if (!this.showSuccessModal) {
                    this.createdEntity = null;
                }
            }, 200);
        },

        hasObjectTypeErrors() {
            return !this.entity.ownerEntity && this.entity.__validationErrors?.objectType;
        },

        getObjectTypeErrors() {
            return this.hasObjectTypeErrors() ? this.entity.__validationErrors?.objectType : [];
        },
        incrementRegistrationTo() {
            let newDate = new Date(this.entity.registrationFrom._date);
            newDate.setDate(newDate.getDate() + 2);

            this.entity.registrationTo = new McDate(newDate);
        },

        getOpportunityTypeIdByLabel(label) {
            const options = $DESCRIPTIONS?.opportunity?.type?.options || {};
            const normalizedTarget = this.normalizeLabel(label);
            const match = Object.entries(options).find(([, optionLabel]) => {
                return this.normalizeLabel(optionLabel) === normalizedTarget;
            });

            return match ? match[0] : null;
        },

        normalizeLabel(value) {
            return String(value ?? '').trim().toLowerCase();
        },
    },
});
