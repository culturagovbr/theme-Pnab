app.component('federative-entity-selector', {
    template: $TEMPLATES['federative-entity-selector'],

    setup() {
        const text = Utils.getTexts('federative-entity-selector')
        return { text }
    },

    data() {
        return {
            federativeEntities: [],
            selectedEntity: null,
            loading: false,
        }
    },

    async created() {
        this.api = new API('aldirblanc')
        await this.loadFederativeEntities()
    },

    methods: {
        async loadFederativeEntities() {
            this.loading = true
            try {
                const response = await this.api.GET('federativeEntities')
                const data = await response.json()
                this.federativeEntities = data || []
            } catch (error) {
                console.error('Erro ao carregar entes federados:', error)
                this.federativeEntities = []
            } finally {
                this.loading = false
            }
        },

        selectEntity(entity) {
            this.selectedEntity = entity
        },

        async confirmSelection() {
            if (!this.selectedEntity) {
                return
            }

            this.loading = true

            try {
                // Salva o ente federado selecionado na sessão via API
                const response = await this.api.POST('selectFederativeEntity', {
                    entityId: this.selectedEntity.id,
                    entityName: this.selectedEntity.name,
                    entityDocument: this.selectedEntity.document
                })

                // Redireciona para a URI salva ou para o painel
                const redirectUri = response?.redirectUri || '/painel'
                window.location.href = redirectUri
            } catch (error) {
                console.error('Erro ao salvar seleção:', error)
                alert('Erro ao salvar seleção. Tente novamente.')
                this.loading = false
            }
        },

        resetSelection() {
            this.selectedEntity = null
        }
    }
})

