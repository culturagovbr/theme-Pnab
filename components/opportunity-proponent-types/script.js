app.component('opportunity-proponent-types', {
    template: $TEMPLATES['opportunity-proponent-types'],

    setup() {
        const text = Utils.getTexts('opportunity-proponent-types');
        return { text };
    },

    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        let description = this.entity.$PROPERTIES.registrationProponentTypes || {};
        let value = this.entity.registrationProponentTypes || [];

        return {
            description,
            value,
            proponentAgentRelation: this.entity.proponentAgentRelation || {
                "Coletivo": false,
                "Pessoa Jurídica": false
            },
        };
    },

    methods: {
        modifyCheckbox(event) {
            const optionValue = event.target.value;
            const index = this.value.indexOf(optionValue);

            if (index === -1) {
                this.value.push(optionValue);
                this.proponentAgentRelation[optionValue] = true;
            } else {
                this.value.splice(index, 1);
                this.proponentAgentRelation[optionValue] = false;
            }

            this.updateProponentAgentRelation();
            this.entity.save();
        },

        updateProponentAgentRelation() {
            const anyAgentRelationChecked = Object.values(this.proponentAgentRelation).includes(true);
            this.entity.useAgentRelationColetivo = anyAgentRelationChecked ? 'required' : 'dontUse';
            this.entity.proponentAgentRelation = this.proponentAgentRelation;
        }
    }
});
