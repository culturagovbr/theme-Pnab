/**
 * Componente opportunity-basic-info customizado para o tema Pnab
 * 
 * Este script sobrescreve completamente o componente do core.
 * 
 * Adições do tema Pnab:
 * - Watchers para gerenciar os campos condicionais (etapaOutros e pautaOutros):
 *   * Limpa os campos quando etapa/pauta não é mais "Outra"
 *   * Inicializa os campos como string vazia quando etapa/pauta é "Outra" e o campo está null/undefined
 *   * Usa zero-width space temporariamente para garantir que os campos sejam incluídos no payload mesmo sem interação do usuário
 * - Lógica no mounted para inicializar campos quando a página carrega com "Outra" já selecionada
 * - Inicialização e validação de campos obrigatórios (segmento, etapa, pauta, territorio):
 *   * Garante que campos null/undefined sejam inicializados como string vazia
 *   * Valida campos obrigatórios antes do save usando zero-width space temporariamente para garantir que estejam no payload
 * - Interceptação do método save para garantir que campos condicionais e obrigatórios estejam no payload
 */
app.component('opportunity-basic-info' , {
    template: $TEMPLATES['opportunity-basic-info'],

    setup() {
        const text = Utils.getTexts('opportunity-basic-info');
        return { text }
    },

    data () {
        return {
            continuousFlowDate: $MAPAS.config.opportunityBasicInfo.date,
            phases: [],
            requiredFields: ['segmento', 'etapa', 'pauta', 'territorio'],
            etapaOutrosField: 'etapaOutros',
            pautaOutrosField: 'pautaOutros',
        };
    },

    async created() {
        if($MAPAS.opportunityPhases && $MAPAS.opportunityPhases.length > 0) {
            this.phases = $MAPAS.opportunityPhases;
        } else {
            const api = new OpportunitiesAPI();
            this.phases = await api.getPhases(this.entity.id);
        }
    },

    mounted() {
        this.initializeOutrosField('etapa', this.etapaOutrosField);
        this.initializeOutrosField('pauta', this.pautaOutrosField);

        this.initializeRequiredFields();

        const originalSave = this.entity.save.bind(this.entity);
        this.entity.save = (...args) => {
            this.ensureOutrosFieldInPayload('etapa', this.etapaOutrosField);
            this.ensureOutrosFieldInPayload('pauta', this.pautaOutrosField);
            this.validateRequiredFields();

            return Vue.nextTick().then(() => {
                return originalSave(...args);
            });
        };
    },

    props: {
        entity: {
            type: Entity,
            required: true
        }
    },

    computed: {
        lastPhase () {
            const phase = this.phases.find(item => item.isLastPhase);
            return phase;
        },
        
        isEtapaOutra() {
            return this.entity.etapa === $MAPAS.config.opportunityOtherOptions.etapa;
        },
        
        isPautaOutra() {
            return this.entity.pauta === $MAPAS.config.opportunityOtherOptions.pauta;
        }
    },

    watch: {
        'entity.isContinuousFlow'(newVal, oldValue) {
            if(Boolean(newVal) != Boolean(oldValue)){
                if (!newVal) {
                    this.entity.hasEndDate = false;
                    this.entity.continuousFlow = null;
                    this.entity.publishedRegistrations = false;

                    if (this.entity.registrationFrom && this.entity.registrationFrom._date instanceof Date) {
                        this.incrementRegistrationTo();
                    } 
                       
                    this.lastPhase.name = this.text("Publicação final do resultado");
                       
                } else {
                    const myDate = new McDate(new Date(this.continuousFlowDate));
                    
                    this.entity.continuousFlow = myDate.sql('full');
                    this.entity.registrationTo = myDate.sql('full');
                    this.entity.publishedRegistrations = true;

                    if(!this.entity.registrationFrom){
                        let actualDate = new Date();
                        this.entity.registrationFrom = Vue.reactive(new McDate(actualDate));
                    }
                    
                    this.lastPhase.name = this.text("Resultado");
                }

                this.lastPhase.disableMessages();
                this.lastPhase.save();
                this.entity.save();
            }
        },

        'entity.hasEndDate'(newVal, oldValue) {
            if(Boolean(newVal) != Boolean(oldValue)){
                if (this.entity.isContinuousFlow) {
                    if(newVal){
                        this.entity.continuousFlow = null;
                        this.entity.registrationTo = null;
                        this.entity.publishedRegistrations = false;

                        if (this.entity.registrationFrom && this.entity.registrationFrom._date instanceof Date) {
                           this.incrementRegistrationTo();
                        } 

                    } else {
                        const myDate = new McDate(new Date(this.continuousFlowDate));
                        
                        this.entity.continuousFlow = myDate;
                        this.entity.registrationTo = myDate;
                    }
                } 
            }
        },

        // ===== WATCHERS ADICIONAIS DO TEMA PNAB =====
        // Gerencia etapaOutros quando etapa mudar
        'entity.etapa'(newVal, oldValue) {
            if (newVal !== oldValue) {
                this.handleOutrosFieldChange('etapa', this.etapaOutrosField, newVal);
            }
        },

        // Gerencia pautaOutros quando pauta mudar
        'entity.pauta'(newVal, oldValue) {
            if (newVal !== oldValue) {
                this.handleOutrosFieldChange('pauta', this.pautaOutrosField, newVal);
            }
        },
    },

    methods: {
        incrementRegistrationTo (){
            let newDate = new Date(this.entity.registrationFrom._date);
            newDate.setDate(newDate.getDate() + 2);
    
            this.entity.registrationTo = new McDate(newDate);
        },

        createEntities() {
            this.collectionPhase = reactive(new Entity('opportunity'));
            this.evaluationPhase = reactive(new Entity('evaluationmethodconfiguration'));
        },

        /**
         * Verifica se um valor corresponde à opção "Outra (especificar)" para um campo específico
         * @param {string} valor - O valor a ser verificado
         * @param {string} tipoCampo - O tipo do campo ('etapa' ou 'pauta')
         * @returns {boolean} - true se o valor corresponde à opção "Outra"
         */
        isOutra(valor, tipoCampo) {
            if (!valor || !tipoCampo) return false;
            const valorOutra = $MAPAS.config.opportunityOtherOptions[tipoCampo];
            return valor === valorOutra;
        },

        /**
         * Inicializa o campo "Outros" se o campo principal for "Outra" e o campo estiver null/undefined
         */
        initializeOutrosField(campoPrincipal, campoOutros) {
            const valorPrincipal = this.entity[campoPrincipal];
            if (this.isOutra(valorPrincipal, campoPrincipal)) {
                const valorOutros = this.entity[campoOutros];
                if (valorOutros === null || valorOutros === undefined) {
                    this.entity[campoOutros] = '';
                }
            }
        },

        /**
         * Gerencia a mudança do campo "Outros" quando o campo principal muda
         */
        handleOutrosFieldChange(campoPrincipal, campoOutros, newVal) {
            const isOutra = this.isOutra(newVal, campoPrincipal);
            
            if (!isOutra) {
                if (this.entity[campoOutros]) {
                    this.entity[campoOutros] = null;
                    this.entity.save();
                }
            } else {
                const valorAtual = this.entity[campoOutros];
                this.entity[campoOutros] = valorAtual === null || valorAtual === undefined ? undefined : (valorAtual + '\u200B');
                this.entity.save();
                this.$nextTick(() => {
                    this.entity[campoOutros] = valorAtual === null || valorAtual === undefined ? '' : valorAtual;
                });
            }
        },

        /**
         * Garante que o campo "Outros" esteja no payload quando necessário
         */
        ensureOutrosFieldInPayload(campoPrincipal, campoOutros) {
            const valorPrincipal = this.entity[campoPrincipal];
            if (this.isOutra(valorPrincipal, campoPrincipal)) {
                const valorAtual = this.entity[campoOutros];
                if (valorAtual === null || valorAtual === undefined || valorAtual === '') {
                    this.entity[campoOutros] = valorAtual === null ? undefined : (valorAtual === undefined ? null : ' ');
                    Vue.nextTick(() => {
                        this.entity[campoOutros] = '';
                    });
                }
            }
        },

        /**
         * Inicializa os campos obrigatórios (segmento, etapa, pauta, territorio)
         * Garante que campos null/undefined sejam inicializados como string vazia
         */
        initializeRequiredFields() {
            const camposObrigatorios = this.requiredFields;
            
            camposObrigatorios.forEach(campo => {
                const valor = this.entity[campo];
                if (valor === null || valor === undefined) {
                    this.entity[campo] = '';
                }
            });
        },

        /**
         * Valida os campos obrigatórios antes do save
         * Garante que os campos estejam presentes no payload mesmo se vazios
         */
        validateRequiredFields() {
            const camposObrigatorios = this.requiredFields;
            
            camposObrigatorios.forEach(campo => {
                const valor = this.entity[campo];
                
                if (valor === null || valor === undefined) {
                    this.entity[campo] = valor === null ? undefined : null;
                    Vue.nextTick(() => {
                        this.entity[campo] = '';
                    });
                }
            });
        },

        cleanZeroWidthSpace(campo) {
            console.log('🔵 [cleanZeroWidthSpace] INÍCIO', {
                campo,
                valor: this.entity[campo]
            });
        },
    }
});
