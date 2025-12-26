app.component('federative-entity-modal', {
    template: $TEMPLATES['federative-entity-modal'],
    emits: ['select'],

    setup() {
        const text = Utils.getTexts('federative-entity-modal')
        return { text }
    },

    data() {
        return {
            federativeEntities: $MAPAS.aldirBlancConfig?.federativeEntities || [],
            selectedEntity: null,
            loading: false,
        }
    },

    computed: {
        shouldShowModal() {
            return this.federativeEntities.length > 1
        }
    },

    created() {
        this.api = new API('aldirblanc')
    },

    mounted() {
        // Se está na página de seleção, abre o modal automaticamente
        if (window.location.pathname.includes('/aldirblanc/select-federative-entity') && this.shouldShowModal) {
            this.$nextTick(() => {
                if (this.$refs.modal) {
                    this.$refs.modal.open()
                }
            })
        }
    },

    methods: {

        selectEntity(entity) {
            this.selectedEntity = entity
        },

        async confirmSelection(modal) {
            if (!this.selectedEntity) {
                return
            }

            this.loading = true

            try {
                // Salva o ente federado selecionado na sessão via API
                const response = await this.api.POST('selectFederativeEntity', {
                    entityId: this.selectedEntity.id
                })

                modal.close()

                // Se está na página de seleção, redireciona
                if (window.location.pathname.includes('/aldirblanc/select-federative-entity')) {
                    const redirectUri = response?.redirectUri || '/painel'
                    window.location.href = redirectUri
                }
            } catch (error) {
                console.error('Erro ao salvar seleção:', error)
                alert('Erro ao salvar seleção. Tente novamente.')
            } finally {
                this.loading = false
            }
        },

        resetSelection() {
            this.selectedEntity = null
        }
    }
})

