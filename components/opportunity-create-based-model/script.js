/**
 * Modal "Usar modelo" - versão Pnab.
 * PAR obrigatório quando há exercícios, salvo $MAPAS.config.parOptionalOnCreate (admin).
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
                shortDescription: '',
            },
            parSelectionModel: {
                parExercicioId: '',
                parMetaId: '',
                parAcaoId: '',
                parAtividadeId: '',
            },
        };
    },

    computed: {
        parOptionalOnCreate() {
            return Boolean($MAPAS.config?.parOptionalOnCreate);
        },

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
        generating(isGeneratingOverlayVisible) {
            document.body.classList.toggle(
                'opportunity-create-based-model--body-locked',
                !!isGeneratingOverlayVisible
            );
        },
    },

    unmounted() {
        document.body.classList.remove('opportunity-create-based-model--body-locked');
    },

    methods: {
        /**
         * Título, descrição curta (integração) e id do modelo. PAR validado à parte.
         */
        validateGeneratePayload(payloadGenerate) {
            const tituloOk = Boolean(
                payloadGenerate.name && String(payloadGenerate.name).trim()
            );
            const descricaoOk = Boolean(
                payloadGenerate.shortDescription &&
                String(payloadGenerate.shortDescription).trim()
            );
            return !tituloOk || !descricaoOk || !payloadGenerate.entityId;
        },

        validateParSelectionBeforeGenerate() {
            if (this.parOptionalOnCreate) {
                return true;
            }
            const parComponent = this.$refs.parInstrumentoRef;
            if (!parComponent || typeof parComponent.validate !== 'function') {
                return true;
            }
            const exerciciosDisponiveis = parComponent.resolvedExercicios;
            if (
                !Array.isArray(exerciciosDisponiveis) ||
                exerciciosDisponiveis.length === 0
            ) {
                return true;
            }
            return parComponent.validate();
        },

        /**
         * Descrição curta e PAR via plugin AldirBlanc (evita PATCH /oportunidade/… e validações do tema).
         */
        async persistPostGenerateFields(newOpportunityId) {
            const aldirBlancApiClient = new API('aldirblanc');
            const savePostGenerateRequestPayload = {
                opportunityId: newOpportunityId,
                shortDescription: String(this.formData.shortDescription).trim(),
            };
            const parInstrumentSelection = this.parSelectionModel;
            const userFilledAnyParInstrumentField =
                parInstrumentSelection.parExercicioId ||
                parInstrumentSelection.parMetaId ||
                parInstrumentSelection.parAcaoId ||
                parInstrumentSelection.parAtividadeId;
            if (userFilledAnyParInstrumentField) {
                savePostGenerateRequestPayload.parExercicioId =
                    parInstrumentSelection.parExercicioId || null;
                savePostGenerateRequestPayload.parMetaId =
                    parInstrumentSelection.parMetaId || null;
                savePostGenerateRequestPayload.parAcaoId =
                    parInstrumentSelection.parAcaoId || null;
                savePostGenerateRequestPayload.parAtividadeId =
                    parInstrumentSelection.parAtividadeId || null;
            }
            const savePostGenerateEndpointUrl = Utils.createUrl(
                'aldirblanc',
                'saveOpportunityPostGenerate'
            );
            const httpResponse = await aldirBlancApiClient.POST(
                savePostGenerateEndpointUrl,
                savePostGenerateRequestPayload
            );
            if (!httpResponse.ok) {
                let errorResponseBody;
                try {
                    errorResponseBody = await httpResponse.json();
                } catch {
                    errorResponseBody = null;
                }
                const errorDataFromServer = errorResponseBody?.data;
                let humanReadableMessageFromServer = '';
                if (typeof errorDataFromServer === 'string') {
                    humanReadableMessageFromServer = errorDataFromServer;
                } else if (
                    errorDataFromServer &&
                    typeof errorDataFromServer === 'object'
                ) {
                    const firstValidationEntry = Object.values(errorDataFromServer)[0];
                    humanReadableMessageFromServer = Array.isArray(firstValidationEntry)
                        ? firstValidationEntry[0]
                        : String(firstValidationEntry ?? '');
                }
                const persistenceError = new Error(
                    errorResponseBody?.message ||
                        (typeof errorResponseBody?.error === 'string'
                            ? errorResponseBody.error
                            : '') ||
                        humanReadableMessageFromServer ||
                        'save failed'
                );
                persistenceError.data = errorDataFromServer ?? errorResponseBody;
                throw persistenceError;
            }
        },

        async save(modal) {
            if (this.generating) {
                return;
            }

            const opportunityApiClient = new API(this.entitydefault.__objectType);

            const generateFromModelRequestPayload = {
                name: String(this.formData.name).trim(),
                shortDescription: String(this.formData.shortDescription).trim(),
                entityId: this.entitydefault.id,
            };

            if (this.validateGeneratePayload(generateFromModelRequestPayload)) {
                this.messages.error(this.text('Todos os campos são obrigatórios.'));
                return;
            }

            if (!this.validateParSelectionBeforeGenerate()) {
                this.messages.error(this.text('Todos os campos são obrigatórios.'));
                return;
            }

            this.generating = true;

            try {
                const generateOpportunityHttpResponse = await opportunityApiClient.POST(
                    `/opportunity/generateopportunity/${generateFromModelRequestPayload.entityId}`,
                    generateFromModelRequestPayload
                );

                if (!generateOpportunityHttpResponse.ok) {
                    let errorMessageForUser = this.text(
                        'Não foi possível gerar a oportunidade. Tente novamente.'
                    );
                    try {
                        const generateErrorResponseBody =
                            await generateOpportunityHttpResponse.json();
                        if (generateErrorResponseBody?.message) {
                            errorMessageForUser = generateErrorResponseBody.message;
                        } else if (generateErrorResponseBody?.error) {
                            errorMessageForUser =
                                typeof generateErrorResponseBody.error === 'string'
                                    ? generateErrorResponseBody.error
                                    : errorMessageForUser;
                        }
                    } catch {
                        /* mantém mensagem genérica */
                    }
                    this.messages.error(errorMessageForUser);
                    this.generating = false;
                    modal.close();
                    return;
                }

                const generateOpportunitySuccessPayload =
                    await generateOpportunityHttpResponse.json();
                if (!generateOpportunitySuccessPayload?.id) {
                    this.messages.error(
                        this.text('Não foi possível gerar a oportunidade. Tente novamente.')
                    );
                    this.generating = false;
                    modal.close();
                    return;
                }

                try {
                    await this.persistPostGenerateFields(
                        generateOpportunitySuccessPayload.id
                    );
                } catch (postGenerateFieldsPersistError) {
                    console.error(postGenerateFieldsPersistError);
                    this.messages.error(
                        this.text(
                            'Não foi possível salvar descrição curta ou dados do PAR na nova oportunidade.'
                        )
                    );
                    this.generating = false;
                    return;
                }

                this.sendSuccess = true;

                await new Promise((resolveAfterDelay) =>
                    setTimeout(resolveAfterDelay, 5000)
                );
                window.location.href = `/gestao-de-oportunidade/${generateOpportunitySuccessPayload.id}/#info`;
            } catch (generateOpportunityRequestFailure) {
                console.error(generateOpportunityRequestFailure);
                this.messages.error(
                    this.text('Não foi possível gerar a oportunidade. Tente novamente.')
                );
                this.generating = false;
                modal.close();
            }
        },

        createEntity() {
            this.formData.name = '';
            this.formData.shortDescription = '';
            this.parSelectionModel = {
                parExercicioId: '',
                parMetaId: '',
                parAcaoId: '',
                parAtividadeId: '',
            };
            this.sendSuccess = false;
        },
    },
});
