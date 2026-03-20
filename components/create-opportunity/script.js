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
            parModel: {
                parExercicioId: '',
                parMetaId: '',
                parAcaoId: '',
                parAtividadeId: '',
            },
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
        },

        resetParSelection() {
            this.parModel = {
                parExercicioId: '',
                parMetaId: '',
                parAcaoId: '',
                parAtividadeId: '',
            };
        },

        applyParToEntity() {
            if (!this.entity) return;
            const m = this.parModel;
            this.entity.parExercicioId = m.parExercicioId || null;
            this.entity.parMetaId = m.parMetaId || null;
            this.entity.parAcaoId = m.parAcaoId || null;
            this.entity.parAtividadeId = m.parAtividadeId || null;
        },

        validatePar() {
            const ref = this.$refs.parPar;
            if (!ref || typeof ref.validate !== 'function') {
                return false;
            }
            return ref.validate();
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
