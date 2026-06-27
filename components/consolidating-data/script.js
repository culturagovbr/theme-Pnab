app.component('consolidating-data', {
    template: $TEMPLATES['consolidating-data'],

    setup() {
        const text = Utils.getTexts('consolidating-data')
        return { text }
    },

    data() {
        return {
            syncStarted: false,
            checkingStatus: false,
            hasError: false,
            errorMessage: '',
            startSyncAttempts: 0,
            statusMessage: '',
            retryCountdown: 0,
            retryCountdownInterval: null
        }
    },

    async created() {
        this.api = new API('aldirblanc')
        await this.startSync()
    },

    unmounted() {
        this.clearRetryCountdown()
    },

    methods: {
        async startSync() {
            if (this.syncStarted) {
                return
            }

            const MAX_START_SYNC_ATTEMPTS = 3
            const RETRY_DELAY_SECONDS = 10
            const MINIMUM_CONNECTING_MESSAGE_TIME = 4000

            try {
                this.syncStarted = true
                this.hasError = false
                this.errorMessage = ''
                this.startSyncAttempts++
                this.statusMessage = this.getSyncAttemptMessage()

                const response = await this.withMinimumDelay(
                    this.api.POST('startSync'),
                    this.startSyncAttempts > 1 ? MINIMUM_CONNECTING_MESSAGE_TIME : 0
                )
                const data = await response.json()

                if (data.started) {
                    this.startSyncAttempts = 0
                    this.statusMessage = ''
                    // Sync iniciado, começa a verificar o status
                    this.checkSyncStatus()
                } else {
                    // Erro ao iniciar sync
                    if (data.error) {
                        this.retryStartSync(MAX_START_SYNC_ATTEMPTS, RETRY_DELAY_SECONDS, data.errorMessage)
                    } else {
                        this.retryStartSync(MAX_START_SYNC_ATTEMPTS, RETRY_DELAY_SECONDS)
                    }
                }
            } catch (error) {
                console.error('Erro ao iniciar sincronização:', error)
                this.retryStartSync(MAX_START_SYNC_ATTEMPTS, RETRY_DELAY_SECONDS)
            }
        },

        retryStartSync(maxAttempts, retryDelaySeconds, errorMessage) {
            this.syncStarted = false

            if (this.startSyncAttempts >= maxAttempts) {
                this.hasError = true
                this.statusMessage = ''
                this.errorMessage = errorMessage || 'Não conseguimos estabelecer conexão com a API CultBr. Tente novamente mais tarde.'
                return
            }

            this.startRetryCountdown(maxAttempts, retryDelaySeconds)
        },

        startRetryCountdown(maxAttempts, retryDelaySeconds) {
            this.clearRetryCountdown()
            this.retryCountdown = retryDelaySeconds
            this.updateRetryCountdownMessage(maxAttempts)

            this.retryCountdownInterval = setInterval(() => {
                this.retryCountdown--

                if (this.retryCountdown <= 0) {
                    this.clearRetryCountdown()
                    this.startSync()
                    return
                }

                this.updateRetryCountdownMessage(maxAttempts)
            }, 1000)
        },

        clearRetryCountdown() {
            if (this.retryCountdownInterval) {
                clearInterval(this.retryCountdownInterval)
                this.retryCountdownInterval = null
            }
        },

        updateRetryCountdownMessage(maxAttempts) {
            this.statusMessage = [
                'Não foi possível conectar à API CultBr.',
                `Nova tentativa em ${this.retryCountdown}....`,
                `Tentativa ${this.startSyncAttempts + 1}/${maxAttempts}`,
            ].join('\n')
        },

        getSyncAttemptMessage() {
            if (this.startSyncAttempts <= 1) {
                return ''
            }

            return 'Estamos nos conectando à API CultBr....'
        },

        async withMinimumDelay(promise, minimumDelay) {
            if (minimumDelay <= 0) {
                return promise
            }

            const [result] = await Promise.allSettled([
                promise,
                new Promise(resolve => setTimeout(resolve, minimumDelay))
            ])

            if (result.status === 'rejected') {
                throw result.reason
            }

            return result.value
        },

        async checkSyncStatus() {
            if (this.checkingStatus) {
                return
            }

            try {
                this.checkingStatus = true
                const response = await this.api.GET('checkSyncStatus')
                const data = await response.json()

                if (data.ready) {
                    // Verifica se houve erro
                    if (data.error && data.errorMessage) {
                        // Mostra mensagem de erro
                        this.hasError = true
                        this.errorMessage = data.errorMessage
                        this.checkingStatus = false
                    } else {
                        // Sync terminou com sucesso, redireciona para o painel
                        window.location.href = Utils.createUrl('panel', 'index')
                    }
                } else {
                    // Sync ainda em andamento, tenta novamente após 2 segundos
                    this.checkingStatus = false
                    setTimeout(() => this.checkSyncStatus(), 2000)
                }
            } catch (error) {
                console.error('Erro ao verificar status da sincronização:', error)
                // Em caso de erro, tenta novamente após 3 segundos
                this.checkingStatus = false
                setTimeout(() => this.checkSyncStatus(), 3000)
            }
        },

        async logoutOnError() {
            try {
                const response = await this.api.POST('logoutOnError')
                const data = await response.json()
                
                if (data.success && data.redirectTo) {
                    // Redireciona para login após logout
                    window.location.href = data.redirectTo
                } else {
                    // Fallback: tenta fazer logout manualmente
                    window.location.href = Utils.createUrl('auth', 'logout')
                }
            } catch (error) {
                console.error('Erro ao fazer logout:', error)
                // Fallback: redireciona para logout
                window.location.href = Utils.createUrl('auth', 'logout')
            }
        }
    }
})
