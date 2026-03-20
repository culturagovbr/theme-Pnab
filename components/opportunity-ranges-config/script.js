app.component('opportunity-ranges-config', {
    template: $TEMPLATES['opportunity-ranges-config'],
    setup() {
        const messages = useMessages();
        return { messages };
    },
    props: {
        entity: {
            type: Entity,
            required: true
        }
    },
    data() {
        return {
            timeout: null
        }
    },
    created() {
        this.entity.registrationRanges = this.entity.registrationRanges || [];
    },
    computed: {
        hasVacanciesError() {
            const errors = this.entity.__validationErrors?.registrationRangesVacancies || [];
            return Array.isArray(errors) && errors.length > 0;
        },
        hasValuesError() {
            const errors = this.entity.__validationErrors?.registrationRangesTotalResource || [];
            return Array.isArray(errors) && errors.length > 0;
        },
        hasError() {
            return this.hasVacanciesError || this.hasValuesError;
        },
        errors() {
            const allErrors = [];
            const vacanciesErrors = this.entity.__validationErrors?.registrationRangesVacancies || [];
            const valuesErrors = this.entity.__validationErrors?.registrationRangesTotalResource || [];

            if (Array.isArray(vacanciesErrors)) {
                allErrors.push(...vacanciesErrors);
            }
            if (Array.isArray(valuesErrors)) {
                allErrors.push(...valuesErrors);
            }

            return allErrors;
        }
    },
    methods: { 
        addRange() {
            if (this.areAllRangesValid()) {
                this.entity.registrationRanges.push({
                    label: '',
                    limit: 0,
                    value: NaN
                });

                this.$nextTick(() => {
                    const lastIndex = this.entity.registrationRanges.length - 1;
                    const descriptionInput = this.$refs['description-' + lastIndex];
                    if (descriptionInput && descriptionInput.length > 0) {
                        descriptionInput[0].focus();
                    }
                });
            } else{
               this.messages.error("Por favor, preencha todos os campos da categoria antes de adicionar uma nova categoria.");
            }
        },
        removeRange(item) {
            this.entity.registrationRanges = this.entity.registrationRanges.filter(function(value, key) {
                return item != key;
            });
            this.autoSave();
        },
        autoSaveRange(item) {
            item.label = item.label.trim();
            if(item.label.length > 0) {
                this.autoSave();
            } 
            else { 
                const index = this.entity.registrationRanges.indexOf(item);
                if (index !== -1) {
                    this.removeRange(index);
                }
            }
        },
        /** Persistência apenas ao clicar em «Salvar» — evita PATCH com entidade incompleta e erros de obrigatoriedade. */
        autoSave() {},
        areAllRangesValid() {
            return this.entity.registrationRanges.every(range => range.label.trim().length > 0);
        },
    }
});

