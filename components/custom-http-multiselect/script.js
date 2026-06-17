const CULTBR_CONNECTION_MINIMUM_LOADING_TIME = 5000

app.component('custom-http-multiselect', {
    template: $TEMPLATES['custom-http-multiselect'],

    minimumLoadingTime: CULTBR_CONNECTION_MINIMUM_LOADING_TIME,

    setup() {
        const text = Utils.getTexts('custom-http-multiselect')
        return { text }
    },

    props: {
        entity: { type: Object, required: true },
        prop: { type: String, required: true },
        label: { type: String, required: true },
        endpoint: { type: String, required: true },
        classes: { type: [String, Array, Object], default: null },
    },

    emits: ['loading'],

    data() {
        const config = $MAPAS.config?.customHttpMultiselect || {}
        const defaultSkip = config.defaultSkip || 0

        return {
            propId: 'custom-http-multiselect-' + this.prop + '-' + Math.random().toString(36).slice(2, 9),
            defaultSkip,
            skip: defaultSkip,
            limit: config.defaultLimit || 0,
            loading: false,
            initializedSelection: false,
            hasLoadError: false,
            loadErrorMessage: '',
            items: [],
            selectedValues: [],
            selectedLabels: {},
            pagination: {
                skip: defaultSkip,
                limit: config.defaultLimit || 0,
                total: 0,
                next: null,
                previous: null,
            },
        }
    },

    created() {
        this.normalizeEntityValue()
        this.loadPage(this.defaultSkip)
    },

    watch: {
        selectedValues: {
            handler() {
                this.syncSelectedValues()
            },
            deep: true,
        },
    },

    computed: {
        optionsForSelect() {
            const options = {}
            this.items.forEach((item) => {
                options[item.value] = item.label
            })
            return options
        },

        paginationInfo() {
            const total = Number(this.pagination.total || 0)
            if (!total) {
                return '0/0'
            }

            const start = Number(this.pagination.skip || 0) + 1
            const end = Math.min(Number(this.pagination.skip || 0) + Number(this.pagination.limit || this.limit), total)
            return `${start}-${end}/${total}`
        },

        hasPagination() {
            return this.pagination.previous !== null || this.pagination.next !== null
        },

        hasNoItems() {
            return !this.loading && !this.hasLoadError && this.items.length === 0
        },
    },

    methods: {
        async loadPage(skip) {
            this.loading = true
            this.$emit('loading', this.loading)
            this.hasLoadError = false
            this.loadErrorMessage = ''
            this.skip = skip ?? this.defaultSkip
            const loadingStartedAt = Date.now()

            try {
                const api = new API('aldirblanc')
                const url = api.createUrl(this.endpoint, {
                    skip: this.skip,
                    limit: this.limit,
                })

                const response = await api.GET(url)

                if (!response.ok) {
                    throw new Error(await this.getResponseErrorMessage(response))
                }
                const payload = await response.json()

                this.items = Array.isArray(payload.data) ? payload.data : []
                this.pagination = {
                    skip: Number(payload.pagination?.skip ?? this.skip),
                    limit: Number(payload.pagination?.limit ?? this.limit),
                    total: Number(payload.pagination?.total ?? this.items.length),
                    next: payload.pagination?.next ?? null,
                    previous: payload.pagination?.previous ?? null,
                }

                this.items.forEach((item) => {
                    this.selectedLabels[item.value] = item.label
                })

                if (!this.initializedSelection) {
                    this.initializeSelectionFromCurrentPage()
                    this.initializedSelection = true
                }
            } catch (error) {
                this.hasLoadError = true
                this.loadErrorMessage = error?.message || this.text('Não foi possível carregar as ações do PAR.')
                this.items = []
            } finally {
                await this.waitMinimumLoadingTime(loadingStartedAt)
                this.loading = false
                this.$emit('loading', this.loading)
            }
        },

        waitMinimumLoadingTime(startedAt) {
            const elapsedTime = Date.now() - startedAt
            const remainingTime = Math.max(0, this.$options.minimumLoadingTime - elapsedTime)
            return new Promise((resolve) => setTimeout(resolve, remainingTime))
        },

        async getResponseErrorMessage(response) {
            if (response.status === 502) {
                return this.text('Não recebemos dados pela API CultBr')
            }

            if (response.status === 504) {
                return this.text('Não conseguimos estabelecer conexão com a API CultBr')
            }

            try {
                const text = await response.text()
                const payload = text ? JSON.parse(text) : null
                const message = payload?.error || payload?.message || payload?.detail

                if (typeof message === 'string' && message.trim() !== '') {
                    return message
                }
            } catch (error) {
                // mantém mensagem padrão
            }

            return this.text('Não foi possível carregar as ações do PAR.')
        },

        initializeSelectionFromCurrentPage() {
            const persistedValues = Array.isArray(this.entity?.[this.prop])
                ? this.entity[this.prop].map((value) => String(value))
                : []
            const currentPageValues = this.items.map((item) => String(item.value))
            this.selectedValues = persistedValues.filter((value) => currentPageValues.includes(value))
        },

        normalizeEntityValue() {
            if (!this.entity) {
                return
            }

            if (this.entity[this.prop] === null || this.entity[this.prop] === undefined) {
                this.entity[this.prop] = []
                return
            }

            if (!Array.isArray(this.entity[this.prop])) {
                this.entity[this.prop] = this.entity[this.prop] ? String(this.entity[this.prop]).split(';') : []
            }
        },

        syncSelectedValues() {
            if (!this.entity) {
                return
            }

            this.entity[this.prop] = this.selectedValues.map((value) => String(value))
        },

        onSelected(value) {
            const selectedItem = this.items.find((item) => String(item.value) === String(value))
            if (selectedItem) {
                this.selectedLabels[selectedItem.value] = selectedItem.label
            }
            this.syncSelectedValues()
        },

        onRemoved(value) {
            this.removeSelectedValue(value)
        },

        onTagRemove(value) {
            this.removeSelectedValue(value)
        },

        removeSelectedValue(value) {
            const index = this.selectedValues.indexOf(value)
            if (index >= 0) {
                this.selectedValues.splice(index, 1)
            }
            this.syncSelectedValues()
        },
    },
})
