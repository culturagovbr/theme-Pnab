app.component('entity-actions', {
    template: $TEMPLATES['entity-actions'],
    emits: [],

    setup() {
        const text = Utils.getTexts('entity-actions')
        return { text }
    },

    created() { },

    mounted() {
        const buttons1 = this.$refs.buttons1?.childElementCount;
        const buttons2 = this.$refs.buttons2?.childElementCount;
        this.empty = !(buttons1 || buttons2);
    },

    data() {
        return {
            empty: false,
            validationError: null
        }
    },

    computed: {
        entityType() {
            switch (this.entity['__objectType']) {
                case 'agent':
                    return __('Agente', 'entity-actions');

                case 'event':
                    return __('Evento', 'entity-actions');

                case 'opportunity':
                    return __('Oportunidade', 'entity-actions');

                case 'space':
                    return __('Espaço', 'entity-actions');

                case 'project':
                    return __('Projeto', 'entity-actions');
            }
        },
    },

    props: {
        entity: {
            type: Entity,
            required: true
        },
        editable: {
            type: Boolean,
            default: false
        },
        canDelete: {
            type: Boolean,
            default: true
        }
    },

    methods: {
        save() {
            // Garante que o campo seja incluído no save quando necessário
            if (this.entity.__objectType === 'opportunity' && 
                this.entity.isFirstPhase && 
                this.entity.id) {
                if (!Array.isArray(this.entity.registrationProponentTypes)) {
                    this.entity.registrationProponentTypes = [];
                }
                if (!this.entity.__originalValues) {
                    this.entity.__originalValues = {};
                }
                if (this.entity.__originalValues['registrationProponentTypes'] === undefined) {
                    this.entity.__originalValues['registrationProponentTypes'] = null;
                }
            }
            
            const event = new Event("entitySave");

            // Oportunidade sem campos alterados: força o PATCH para disparar update:finish
            // (e o PUT de sync com o CultBR), que o Entity.save() pularia por short-circuit.
            // Vale em qualquer status (inclusive rascunho), pois o envio ao CultBR ocorre
            // em qualquer save. Com campos alterados, o Entity.save() já persiste e reconcilia.
            const isOpportunity = this.entity.__objectType === 'opportunity' && this.entity.id;
            const hasModifiedFields = Object.keys(this.entity.data(true)).length > 0;

            if (isOpportunity && !hasModifiedFields) {
                this.forceBackendSave().then(() => {
                    window.dispatchEvent(event);
                });
                return;
            }

            this.entity.save().then(() => {
                window.dispatchEvent(event);
            });
        },
        /** PATCH ao backend mesmo sem campos modificados (o Entity.save() abortaria). */
        async forceBackendSave() {
            const entity = this.entity;
            entity.__processing = entity.text('salvando');
            try {
                const res = await entity.API.persistEntity(entity);
                return await entity.doPromise(res, (persisted) => {
                    entity.sendMessage(entity.text('modificacoes salvas'));
                    entity.populate(persisted, true, {});
                    entity.cleanErrors();
                });
            } catch (error) {
                return entity.doCatch(error);
            }
        },
        exit() {
            window.location.href = this.entity.getUrl('single');
        },
    },
});
