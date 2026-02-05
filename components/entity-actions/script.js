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
            // Hook: entity.save:before
            // Expects hooks to return a string error message or string[] or nothing if valid.
            const errors = MapasCulturais.Hook.apply('entity.save:before', [this.entity]);

            // Flatten results if some hooks return arrays
            const flatErrors = errors.flat().filter(e => e);

            if (flatErrors.length > 0) {
                // Show first error or all? For now, first one to maintain user experience.
                const firstError = flatErrors[0];
                this.$emit('error', firstError);

                // Force reactivity/re-render if error is the same
                this.validationError = null;
                this.$nextTick(() => {
                    this.validationError = firstError;
                });
                return;
            }

            this.validationError = null; // Clear error on valid save attempt

            const event = new Event("entitySave");
            this.entity.save().then(() => {
                window.dispatchEvent(event);
                //this.exit();
            });
        },
        exit() {
            window.location.href = this.entity.getUrl('single');
        },
    },
});
