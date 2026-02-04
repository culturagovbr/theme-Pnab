app.component('create-agent', {
    template: $TEMPLATES['create-agent'],
    emits: ['create'],

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('create-agent')
        return { text }
    },

    created() {
    },

    data() {
        return {
            entity: null,
        }
    },

    props: {
        editable: {
            type: Boolean,
            default: true
        },
    },

    computed: {
        areaErrors() {
            return this.entity.__validationErrors['term-area'];
        },
        areaClasses() {
            return this.areaErrors ? 'field error' : 'field';
        },
        modalTitle() {
            if (this.entity?.id) {
                if (this.entity.status == 1) {
                    return __('agenteCriado', 'create-agent');
                } else {
                    return __('criarRascunho', 'create-agent');
                }
            } else {
                return __('criarAgente', 'create-agent');

            }
        },
    },

    methods: {
        createEntity() {
            this.entity = Vue.ref(new Entity('agent'));
            this.entity.type = 2;
            this.entity.terms = { area: [] }
        },
        createDraft(modal) {
            this.entity.status = 0;
            this.save(modal);
        },
        createPublic(modal) {
            //lançar dois eventos
            this.entity.status = 1;
            this.save(modal);
        },
        save(modal) {
            const lists = useEntitiesLists(); // obtem o storage de listas de entidades

            modal.loading(true);
            this.entity.save().then((response) => {
                this.$emit('create', response);
                modal.loading(false);
                Utils.pushEntityToList(this.entity);
            }).catch((e) => {
                modal.loading(false);

            });
        },

        destroyEntity() {
            // para o conteúdo da modal não sumir antes dela fechar
            setTimeout(() => this.entity = null, 200);
        },
    },
});
