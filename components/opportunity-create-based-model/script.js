/**
 * Modal "Usar modelo" - versão Pnab.
 * Não permite vincular o edital a uma entidade (modelo oficial).
 */
app.component('opportunity-create-based-model', {
    template: $TEMPLATES['opportunity-create-based-model'],
    setup() {
        const messages = useMessages();
        const text = Utils.getTexts('opportunity-create-based-model');
        return { text, messages };
    },
    props: {
        entitydefault: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            sendSuccess: false,
            formData: {
                name: '',
            },
        };
    },

    methods: {
        async save(modal) {
            const api = new API(this.entitydefault.__objectType);

            const objt = {
                name: this.formData.name,
                entityId: this.entitydefault.id,
            };

            if (this.validate(objt)) {
                this.messages.error(this.text('Todos os campos são obrigatórios.'));
                return;
            }

            await api.POST(`/opportunity/generateopportunity/${objt.entityId}`, objt).then(response =>
                response.json().then((dataReturn) => {
                    this.messages.success(
                        this.text('Aguarde. Estamos gerando a oportunidade baseada no modelo.'),
                        6000
                    );
                    this.sendSuccess = true;
                    setTimeout(() => {
                        window.location.href = `/gestao-de-oportunidade/${dataReturn.id}/#info`;
                    }, 5000);
                })
            );
        },

        validate(objt) {
            return !objt.name || !objt.entityId;
        },

        createEntity() {
            // Mantido para compatibilidade com @open; não usamos entity nesta versão
        },
    },
});
