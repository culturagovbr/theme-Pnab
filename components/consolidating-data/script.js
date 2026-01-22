app.component('consolidating-data', {
    template: $TEMPLATES['consolidating-data'],

    setup() {
        const text = Utils.getTexts('consolidating-data')
        return { text }
    },

    data() {
        return {
            syncStarted: false,
            checkingStatus: false
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
                    // Erro ao iniciar sync, tenta novamente após 3 segundos
                    this.syncStarted = false
                    setTimeout(() => this.startSync(), 3000)
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
                    // Sync terminou, redireciona para o painel
                    window.location.href = Utils.createUrl('panel', 'index')
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
        }
    }
})
