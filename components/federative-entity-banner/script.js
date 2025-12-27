app.component('federative-entity-banner', {
    template: $TEMPLATES['federative-entity-banner'],

    setup() {
        const text = Utils.getTexts('federative-entity-banner')
        return { text }
    },

    data() {
        return {
            selectedEntity: null,
        }
    },

    computed: {
        selectedEntityName() {
            return this.selectedEntity?.name || ''
        },
        selectedEntityDocument() {
            return this.selectedEntity?.document || null
        }
    },

    mounted() {
        this.loadSelectedEntity()
    },

    methods: {
        loadSelectedEntity() {
            if (typeof $MAPAS !== 'undefined' && $MAPAS.selectedFederativeEntity) {
                this.selectedEntity = $MAPAS.selectedFederativeEntity
            }
        },

        openConfirmModal() {
            this.$refs.confirmModal.open()
        },

        changeFederativeEntity(modal) {
            if (modal) {
                modal.close()
            }

            window.location.href = '/aldirblanc/changeFederativeEntity'
        }
    }
})

