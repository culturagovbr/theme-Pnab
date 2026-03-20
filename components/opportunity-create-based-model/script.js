/**
 * Modal "Usar modelo" - versão Pnab.
 * Não permite vincular o edital a uma entidade (modelo oficial).
 */
app.component('opportunity-create-based-model', {
    template: $TEMPLATES['opportunity-create-based-model'],
    setup() {
        const messages = useMessages();
        const text = Utils.getTexts('opportunity-create-based-model');
        return { text, messages };
    },
    props: {
        entitydefault: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            sendSuccess: false,
            generating: false,
            formData: {
                name: '',
            },
        };
    },

    computed: {
        /** Passos do overlay: chaves alinhadas a `texts.php` (i18n / `text()`). */
        generatingMessages() {
            return [
                this.text('Estamos gerando a oportunidade a partir do modelo…'),
                this.text('Copiando os dados do modelo…'),
                this.text('Copiando os dados das fases…'),
                this.text('Copiando os dados do formulário…'),
                this.text('Consolidando os dados…'),
                this.text('Preparando a nova oportunidade…'),
            ];
        },
    },

    watch: {
        generating(val) {
            document.body.classList.toggle(
                'opportunity-create-based-model--body-locked',
                !!val
            );
        },
    },

    unmounted() {
        document.body.classList.remove('opportunity-create-based-model--body-locked');
    },

    methods: {
        async save(modal) {
            if (this.generating) {
                return;
            }

            const api = new API(this.entitydefault.__objectType);

            const objt = {
                name: this.formData.name,
                entityId: this.entitydefault.id,
            };

            if (this.validate(objt)) {
                this.messages.error(this.text('Todos os campos são obrigatórios.'));
                return;
            }

            this.generating = true;

            try {
                const response = await api.POST(
                    `/opportunity/generateopportunity/${objt.entityId}`,
                    objt
                );

                if (!response.ok) {
                    let errText = this.text(
                        'Não foi possível gerar a oportunidade. Tente novamente.'
                    );
                    try {
                        const errBody = await response.json();
                        if (errBody?.message) {
                            errText = errBody.message;
                        } else if (errBody?.error) {
                            errText =
                                typeof errBody.error === 'string'
                                    ? errBody.error
                                    : errText;
                        }
                    } catch (e) {
                        /* mantém mensagem genérica */
                    }
                    this.messages.error(errText);
                    this.generating = false;
                    modal.close();
                    return;
                }

                const dataReturn = await response.json();
                if (!dataReturn?.id) {
                    this.messages.error(
                        this.text('Não foi possível gerar a oportunidade. Tente novamente.')
                    );
                    this.generating = false;
                    modal.close();
                    return;
                }

                this.sendSuccess = true;

                await new Promise((r) => setTimeout(r, 5000));
                window.location.href = `/gestao-de-oportunidade/${dataReturn.id}/#info`;
            } catch (e) {
                this.messages.error(
                    this.text('Não foi possível gerar a oportunidade. Tente novamente.')
                );
                this.generating = false;
                modal.close();
            }
        },

        validate(objt) {
            return !objt.name || !objt.entityId;
        },

        createEntity() {
            // Mantido para compatibilidade com @open; não usamos entity nesta versão
        },
    },
});
