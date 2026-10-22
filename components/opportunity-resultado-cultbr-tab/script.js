/** Aba "Envio de Resultado ao CultBR": GET único que decide se a aba aparece e já entrega o estado ao card. */
app.component('opportunity-resultado-cultbr-tab', {
    template: $TEMPLATES['opportunity-resultado-cultbr-tab'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },

    data() {
        return {
            visivel: false,
            estadoInicial: null,
            subAba: 'formulario',
        };
    },

    created() {
        // Guardado antes do onMounted das abas principais, que sobrescreve o hash ao não achar esta aba ainda.
        this.hashInicial = window.location.hash;
        this.api = new API('aldirblanc', 'resultado-formulario', { cacheMode: 'no-store' });
        this.verificar();
    },

    methods: {
        selectSubAba(slug) {
            this.subAba = slug;
        },

        async verificar() {
            try {
                const url = this.api.createUrl('resultadoFormulario', { opportunityId: this.entity.id });
                const response = await this.api.GET(url);
                const body = await response.json().catch(() => ({}));

                // Sem permissão ou sem formulário no PAR: a aba não existe.
                if (response.status === 403 || body?.estado === 'sem-formulario') {
                    this.visivel = false;
                    return;
                }

                this.estadoInicial = response.ok ? body : null;
                this.visivel = true;
            } catch (error) {
                // Indisponibilidade não é "sem formulário": a aba aparece e o card mostra o erro e o tentar de novo.
                this.estadoInicial = null;
                this.visivel = true;
            }

            this.restaurarAbaPeloHash();
        },

        /** A aba se registra só depois do GET; re-setar o hash dispara o hashchange que agora a encontra. */
        restaurarAbaPeloHash() {
            if (this.visivel && this.hashInicial === '#resultado-cultbr' && window.location.hash !== '#resultado-cultbr') {
                this.$nextTick(() => { window.location.hash = 'resultado-cultbr'; });
            }
        },
    },
});
