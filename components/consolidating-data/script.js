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
            errorMessage: ''
        }
    },

    async created() {
        this.api = new API('aldirblanc')
        await this.startSync()
    },

    methods: {
        async startSync() {
            if (this.syncStarted) {
                return
            }

            try {
                this.syncStarted = true
                const response = await this.api.POST('startSync')
                const data = await response.json()

                if (data.started) {
                    // Sync iniciado, começa a verificar o status
                    this.checkSyncStatus()
                } else {
                    // Erro ao iniciar sync
                    if (data.error) {
                        // Se há mensagem de erro específica, mostra
                        this.hasError = true
                        this.errorMessage = data.error || 'Não foi possível consolidar seus dados, tente novamente mais tarde'
                        this.syncStarted = false
                    } else {
                        // Tenta novamente após 3 segundos
                        this.syncStarted = false
                        setTimeout(() => this.startSync(), 3000)
                    }
                }
            } catch (error) {
                console.error('Erro ao iniciar sincronização:', error)
                // Em caso de erro, tenta novamente após 3 segundos
                this.syncStarted = false
                setTimeout(() => this.startSync(), 3000)
            }
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
