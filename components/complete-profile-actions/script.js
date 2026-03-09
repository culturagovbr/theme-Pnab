/**
 * Ações da página "Complete seu cadastro".
 * Salva a entidade (PATCH), em seguida chama POST aldirblanc/completeProfile
 * e redireciona para a redirectUri retornada (painel ou escolha de ente federado).
 */
app.component('complete-profile-actions', {
    template: $TEMPLATES['complete-profile-actions'],

    props: {
        entity: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            loading: false
        }
    },

    methods: {
        async saveAndContinue() {
            if (!this.entity || this.loading) return

            this.loading = true
            try {
                // 1) Persiste o formulário (PATCH agent/single)
                await this.entity.save()

                // 2) POST para o plugin: confirma conclusão e obtém redirectUri
                const api = new API('aldirblanc')
                const response = await api.POST('completeProfile', {})

                if (!response.ok) {
                    const err = await response.json().catch(() => ({}))
                    throw new Error(err.message || 'Erro ao concluir cadastro.')
                }

                const data = await response.json()
                const redirectUri = data?.redirectUri || $MAPAS?.completeProfile?.redirectUri || '/painel'
                window.location.href = redirectUri
            } catch (error) {
                console.error('Complete profile:', error)
            } finally {
                this.loading = false
            }
        }
    }
})
