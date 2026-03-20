/**
 * Campos em cascata Exercício → Meta → Ação → Atividade a partir do JSON `exercices` do ente.
 * Dados: prop `exercicios`, depois `config.mcFederativeEntityPar.exercicios` (init.php + FederativeEntityService),
 * e por fim GET aldirblanc/parExercicios se `loadParExercicios` e ainda vazio.
 */
app.component('mc-federative-entity-par', {
    template: $TEMPLATES['mc-federative-entity-par'],
    emits: ['update:modelValue'],

    setup() {
        const translateMessage = Utils.getTexts('mc-federative-entity-par');
        return { translateMessage };
    },

    props: {
        /** Lista no mesmo formato da coluna FederativeEntity.exercices (array de exercícios com metas → ações → atividades). */
        exercicios: {
            type: Array,
            default: () => [],
        },
        /**
         * { parExercicioId, parMetaId, parAcaoId, parAtividadeId } — strings, vazio = não selecionado.
         */
        modelValue: {
            type: Object,
            default: null,
        },
        /** Se preenchido, substitui a mensagem padrão quando `exercicios` está vazio (texto definido pelo chamador). */
        emptyHint: {
            type: String,
            default: '',
        },
        /** Se true, exibe rótulos e textos resolvidos (sem selects); não emite alterações. */
        readonly: {
            type: Boolean,
            default: false,
        },
        /**
         * Se true e a lista ainda estiver vazia (prop + injeção PHP), busca via GET aldirblanc/parExercicios.
         */
        loadParExercicios: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            showFieldErrors: false,
            fieldErrors: {
                exercicio: false,
                meta: false,
                acao: false,
                atividade: false,
            },
            fetchedExercicios: [],
            /** Preenchido em init.php a partir da sessão (FederativeEntityService). */
            configExercicios: [],
        };
    },

    created() {
        const exerciciosFromMapasConfig =
            $MAPAS?.config?.mcFederativeEntityPar?.exercicios;
        if (
            Array.isArray(exerciciosFromMapasConfig) &&
            exerciciosFromMapasConfig.length > 0
        ) {
            this.configExercicios = exerciciosFromMapasConfig;
        }
        this.maybeLoadParExercicios();
    },

    computed: {
        resolvedExercicios() {
            if (Array.isArray(this.exercicios) && this.exercicios.length > 0) {
                return this.exercicios;
            }
            if (Array.isArray(this.configExercicios) && this.configExercicios.length > 0) {
                return this.configExercicios;
            }
            return this.fetchedExercicios;
        },

        normalizedModel() {
            const boundModel = this.modelValue;
            return {
                parExercicioId:
                    boundModel?.parExercicioId != null
                        ? String(boundModel.parExercicioId)
                        : '',
                parMetaId:
                    boundModel?.parMetaId != null
                        ? String(boundModel.parMetaId)
                        : '',
                parAcaoId:
                    boundModel?.parAcaoId != null
                        ? String(boundModel.parAcaoId)
                        : '',
                parAtividadeId:
                    boundModel?.parAtividadeId != null
                        ? String(boundModel.parAtividadeId)
                        : '',
            };
        },

        /** Exercício escolhido, mas a API não retornou nenhuma meta. */
        exercicioSemMetasDisponiveis() {
            return (
                !!this.normalizedModel.parExercicioId &&
                this.parMetas.length === 0
            );
        },

        /** Meta escolhida, mas não há ações na hierarquia. */
        metaSemAcoesDisponiveis() {
            return (
                !!this.normalizedModel.parMetaId && this.parAcoes.length === 0
            );
        },

        /** Ação escolhida, mas não há atividades na hierarquia. */
        acaoSemAtividadesDisponiveis() {
            return (
                !!this.normalizedModel.parAcaoId &&
                this.parAtividades.length === 0
            );
        },

        parExercicioId: {
            get() {
                return this.normalizedModel.parExercicioId;
            },
            set(incomingSelectValue) {
                const normalizedId =
                    incomingSelectValue != null && incomingSelectValue !== ''
                        ? String(incomingSelectValue)
                        : '';
                this.$emit('update:modelValue', {
                    parExercicioId: normalizedId,
                    parMetaId: '',
                    parAcaoId: '',
                    parAtividadeId: '',
                });
                this.clearErrors();
            },
        },

        parMetaId: {
            get() {
                return this.normalizedModel.parMetaId;
            },
            set(incomingSelectValue) {
                const normalizedId =
                    incomingSelectValue != null && incomingSelectValue !== ''
                        ? String(incomingSelectValue)
                        : '';
                this.$emit('update:modelValue', {
                    ...this.normalizedModel,
                    parMetaId: normalizedId,
                    parAcaoId: '',
                    parAtividadeId: '',
                });
                this.clearErrors();
            },
        },

        parAcaoId: {
            get() {
                return this.normalizedModel.parAcaoId;
            },
            set(incomingSelectValue) {
                const normalizedId =
                    incomingSelectValue != null && incomingSelectValue !== ''
                        ? String(incomingSelectValue)
                        : '';
                this.$emit('update:modelValue', {
                    ...this.normalizedModel,
                    parAcaoId: normalizedId,
                    parAtividadeId: '',
                });
                this.clearErrors();
            },
        },

        parAtividadeId: {
            get() {
                return this.normalizedModel.parAtividadeId;
            },
            set(incomingSelectValue) {
                const normalizedId =
                    incomingSelectValue != null && incomingSelectValue !== ''
                        ? String(incomingSelectValue)
                        : '';
                this.$emit('update:modelValue', {
                    ...this.normalizedModel,
                    parAtividadeId: normalizedId,
                });
                this.clearErrors();
            },
        },

        parMetas() {
            if (
                !this.normalizedModel.parExercicioId ||
                !this.resolvedExercicios.length
            ) {
                return [];
            }
            const exercicioSelecionado = this.resolvedExercicios.find(
                (exercicioEntry) =>
                    String(exercicioEntry.id) ===
                    String(this.normalizedModel.parExercicioId)
            );
            return exercicioSelecionado &&
                Array.isArray(exercicioSelecionado.metas)
                ? exercicioSelecionado.metas
                : [];
        },

        parAcoes() {
            if (!this.normalizedModel.parMetaId || !this.parMetas.length) {
                return [];
            }
            const metaSelecionada = this.parMetas.find(
                (metaEntry) =>
                    String(metaEntry.id) ===
                    String(this.normalizedModel.parMetaId)
            );
            return metaSelecionada && Array.isArray(metaSelecionada.acoes)
                ? metaSelecionada.acoes
                : [];
        },

        parAtividades() {
            if (!this.normalizedModel.parAcaoId || !this.parAcoes.length) {
                return [];
            }
            const acaoSelecionada = this.parAcoes.find(
                (acaoEntry) =>
                    String(acaoEntry.id) ===
                    String(this.normalizedModel.parAcaoId)
            );
            return acaoSelecionada &&
                Array.isArray(acaoSelecionada.atividades)
                ? acaoSelecionada.atividades
                : [];
        },

        /** Textos resolvidos para modo somente leitura (ano / nomes ou fallback para o id). */
        readonlyExercicioLegenda() {
            const exercicioId = this.normalizedModel.parExercicioId;
            if (!exercicioId) {
                return '';
            }
            const exercicioRow = this.resolvedExercicios.find(
                (row) => String(row.id) === String(exercicioId)
            );
            if (exercicioRow?.ano != null) {
                return String(exercicioRow.ano);
            }
            return String(exercicioId);
        },

        readonlyMetaLegenda() {
            const exercicioId = this.normalizedModel.parExercicioId;
            const metaId = this.normalizedModel.parMetaId;
            if (!metaId) {
                return '';
            }
            const exercicioRow = this.resolvedExercicios.find(
                (row) => String(row.id) === String(exercicioId)
            );
            if (!exercicioRow || !Array.isArray(exercicioRow.metas)) {
                return String(metaId);
            }
            const metaRow = exercicioRow.metas.find(
                (row) => String(row.id) === String(metaId)
            );
            if (metaRow?.nome != null) {
                return String(metaRow.nome);
            }
            return String(metaId);
        },

        readonlyAcaoLegenda() {
            const exercicioId = this.normalizedModel.parExercicioId;
            const metaId = this.normalizedModel.parMetaId;
            const acaoId = this.normalizedModel.parAcaoId;
            if (!acaoId) {
                return '';
            }
            const exercicioRow = this.resolvedExercicios.find(
                (row) => String(row.id) === String(exercicioId)
            );
            if (!exercicioRow || !Array.isArray(exercicioRow.metas)) {
                return String(acaoId);
            }
            const metaRow = exercicioRow.metas.find(
                (row) => String(row.id) === String(metaId)
            );
            if (!metaRow || !Array.isArray(metaRow.acoes)) {
                return String(acaoId);
            }
            const acaoRow = metaRow.acoes.find(
                (row) => String(row.id) === String(acaoId)
            );
            if (acaoRow?.nome != null) {
                return String(acaoRow.nome);
            }
            return String(acaoId);
        },

        readonlyAtividadeLegenda() {
            const exercicioId = this.normalizedModel.parExercicioId;
            const metaId = this.normalizedModel.parMetaId;
            const acaoId = this.normalizedModel.parAcaoId;
            const atividadeId = this.normalizedModel.parAtividadeId;
            if (!atividadeId) {
                return '';
            }
            const exercicioRow = this.resolvedExercicios.find(
                (row) => String(row.id) === String(exercicioId)
            );
            if (!exercicioRow || !Array.isArray(exercicioRow.metas)) {
                return String(atividadeId);
            }
            const metaRow = exercicioRow.metas.find(
                (row) => String(row.id) === String(metaId)
            );
            if (!metaRow || !Array.isArray(metaRow.acoes)) {
                return String(atividadeId);
            }
            const acaoRow = metaRow.acoes.find(
                (row) => String(row.id) === String(acaoId)
            );
            if (!acaoRow || !Array.isArray(acaoRow.atividades)) {
                return String(atividadeId);
            }
            const atividadeRow = acaoRow.atividades.find(
                (row) => String(row.id) === String(atividadeId)
            );
            if (atividadeRow?.nome != null) {
                return String(atividadeRow.nome);
            }
            return String(atividadeId);
        },
    },

    methods: {
        async maybeLoadParExercicios() {
            if (!this.loadParExercicios) {
                return;
            }
            const hasExerciciosFromProp =
                Array.isArray(this.exercicios) && this.exercicios.length > 0;
            const hasExerciciosFromPhpConfig =
                Array.isArray(this.configExercicios) &&
                this.configExercicios.length > 0;
            if (hasExerciciosFromProp || hasExerciciosFromPhpConfig) {
                return;
            }

            const selectedFederativeEntityIdFromSession =
                typeof $MAPAS !== 'undefined'
                    ? $MAPAS?.config?.aldirblanc?.selectedFederativeEntityId
                    : null;
            if (
                selectedFederativeEntityIdFromSession == null ||
                selectedFederativeEntityIdFromSession === ''
            ) {
                return;
            }

            const aldirblancApiClient = new API('aldirblanc');
            const parExerciciosUrl = aldirblancApiClient.createUrl(
                'parExercicios'
            );
            parExerciciosUrl.searchParams.set(
                'federativeEntityId',
                String(selectedFederativeEntityIdFromSession)
            );

            try {
                const httpResponse = await aldirblancApiClient.GET(
                    parExerciciosUrl
                );
                if (!httpResponse.ok) {
                    return;
                }
                const responseBody = await httpResponse.json();
                if (Array.isArray(responseBody?.exercicios)) {
                    this.fetchedExercicios = responseBody.exercicios;
                }
            } catch (loadError) {
                console.error(loadError);
            }
        },

        clearErrors() {
            this.showFieldErrors = false;
            this.fieldErrors = {
                exercicio: false,
                meta: false,
                acao: false,
                atividade: false,
            };
        },

        /**
         * Marca erros visíveis e devolve se o conjunto PAR está completo.
         * O chamador pode usar via ref, ex.: if (!this.$refs.par.validate()) return;
         */
        validate() {
            if (this.readonly) {
                this.clearErrors();
                return true;
            }
            const parSelectionState = this.normalizedModel;
            const exercicioSemFilhos =
                !!parSelectionState.parExercicioId &&
                this.parMetas.length === 0;
            const metaSemFilhos =
                !!parSelectionState.parMetaId && this.parAcoes.length === 0;
            const acaoSemFilhos =
                !!parSelectionState.parAcaoId &&
                this.parAtividades.length === 0;

            const fieldValidationErrors = {
                exercicio: !parSelectionState.parExercicioId,
                meta:
                    !parSelectionState.parMetaId || exercicioSemFilhos,
                acao: !parSelectionState.parAcaoId || metaSemFilhos,
                atividade:
                    !parSelectionState.parAtividadeId || acaoSemFilhos,
            };
            this.showFieldErrors = true;
            this.fieldErrors = fieldValidationErrors;
            return (
                !fieldValidationErrors.exercicio &&
                !fieldValidationErrors.meta &&
                !fieldValidationErrors.acao &&
                !fieldValidationErrors.atividade
            );
        },

        fieldErrorMessage(validationFieldKey) {
            if (
                validationFieldKey === 'meta' &&
                this.exercicioSemMetasDisponiveis
            ) {
                return this.translateMessage('sem_metas_para_exercicio');
            }
            if (
                validationFieldKey === 'acao' &&
                this.metaSemAcoesDisponiveis
            ) {
                return this.translateMessage('sem_acoes_para_meta');
            }
            if (
                validationFieldKey === 'atividade' &&
                this.acaoSemAtividadesDisponiveis
            ) {
                return this.translateMessage('sem_atividades_para_acao');
            }
            const messageKeyByField = {
                exercicio: 'erro_exercicio',
                meta: 'erro_meta',
                acao: 'erro_acao',
                atividade: 'erro_atividade',
            };
            return (
                this.translateMessage(
                    messageKeyByField[validationFieldKey]
                ) || ''
            );
        },
    },
});
