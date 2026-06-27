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
            keyword: '',
            order: 'name ASC',
            loading: false,
        }
    },

    async created() {
        this.api = new API('aldirblanc')
        await this.loadFederativeEntities()
    },

    computed: {
        filteredFederativeEntities() {
            const keyword = this.keyword.trim().toLowerCase()

            return this.federativeEntities
                .filter((entity) => {
                    if (!keyword) {
                        return true
                    }

                    const name = (entity.name || '').toLowerCase()
                    const document = (entity.document || '').toLowerCase()

                    return name.includes(keyword) || document.includes(keyword)
                })
                .sort((entityA, entityB) => {
                    if (this.order === 'name DESC') {
                        return this.compareName(entityB, entityA)
                    }

                    return this.compareName(entityA, entityB)
                })
        }
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
                const data = await response.json()

                if (!response.ok || data.error) {
                    throw new Error(data.data || data.message || 'Erro ao salvar seleção.')
                }

                // Redireciona para a URI salva ou para o painel
                const redirectUri = data.redirectUri || Utils.createUrl('panel', 'index')
                window.location.href = redirectUri
            } catch (error) {
                console.error('Erro ao salvar seleção:', error)
                alert('Erro ao salvar seleção. Tente novamente.')
                this.loading = false
            }
        },

        resetSelection() {
            this.selectedEntity = null
        },

        compareName(entityA, entityB) {
            return (entityA.name || '').localeCompare(entityB.name || '', 'pt-BR')
        }
    }
})
