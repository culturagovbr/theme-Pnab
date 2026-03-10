/**
 * Componente: Outras modalidades de ações afirmativas (edição de oportunidade).
 * Gerencia o metadado outrasModalidadesAcoesAfirmativas (opcoes, sublistas, descrição).
 * Lista de opções com sublista vem do backend (Theme::OPTIONS_OTHER_MODALITIES_WITH_SUBLIST via jsObject).
 */

const OPCAO_EXCLUSIVA = 'nao_previstas';
const OPCAO_OUTRA = 'outra_legislacao';
const MAX_DESCRICAO = 140;

/** Chaves das opções com sublista (fallback se config não estiver disponível; espelha Theme::OPTIONS_OTHER_MODALITIES_WITH_SUBLIST) */
const OPTIONS_WITH_SUBLIST_FALLBACK = ['bonus_agentes', 'bonus_tematicas', 'categoria_especifica', 'edital_especifico'];

app.component('opportunity-outras-modalidades-acoes-afirmativas', {
    template: $TEMPLATES['opportunity-outras-modalidades-acoes-afirmativas'],

    props: {
        entity: {
            type: Object,
            required: true
        }
    },

    setup() {
        const text = Utils.getTexts('opportunity-outras-modalidades-acoes-afirmativas');
        return { text };
    },

    computed: {
        /** Lista { key, labelKey } vinda do backend (fonte única com Theme.php) */
        opcoesComSublista() {
            const config = $MAPAS.config?.opportunityOutrasModalidades?.opcoesComSublista;
            if (Array.isArray(config) && config.length > 0) return config;
            return OPTIONS_WITH_SUBLIST_FALLBACK.map((optionKey) => ({
                key: optionKey,
                labelKey: optionKey.split('_').map((part, partIndex) => (partIndex === 0 ? part : part.charAt(0).toUpperCase() + part.slice(1))).join('')
            }));
        },
        /** Apenas as chaves (para ensureData, validação, etc.) */
        opcoesComSublistaKeys() {
            return this.opcoesComSublista.map((item) => item.key);
        },
        data() {
            const raw = this.entity.outrasModalidadesAcoesAfirmativas;
            return this.normalizeData(raw);
        },
        opcoesArray() {
            const arr = this.entity.outrasModalidadesAcoesAfirmativas?.opcoes;
            return Array.isArray(arr) ? arr : [];
        },
        isNaoPrevistasMarcado() {
            return this.opcoesArray.includes(OPCAO_EXCLUSIVA);
        },
        isOpcaoMarcada() {
            return (optionKey) => this.opcoesArray.includes(optionKey);
        },
        sublistItems() {
            return [
                { value: 'pessoas_negras', label: this.text('pessoasNegras') },
                { value: 'pessoas_indigenas', label: this.text('pessoasIndigenas') },
                { value: 'pessoas_deficiencia', label: this.text('pessoasDeficiencia') },
                { value: 'mulheres', label: this.text('mulheres') },
                { value: 'povos_tradicionais', label: this.text('povosTradicionais') },
                { value: 'lgbtqiapn', label: this.text('lgbtqiapn') },
                { value: 'pessoas_idosas', label: this.text('pessoasIdosas') },
                { value: 'situacao_rua', label: this.text('situacaoRua') },
                { value: 'outros_vulnerabilizados', label: this.text('outrosVulnerabilizados') }
            ];
        },
        sublistLabels() {
            return this.sublistItems.reduce((accumulator, item) => {
                accumulator[item.value] = item.label;
                return accumulator;
            }, {});
        },
        descricaoOutra() {
            return (this.entity.outrasModalidadesAcoesAfirmativas?.outra_legislacao_descricao || '').slice(0, MAX_DESCRICAO);
        },
        descricaoOutraLength() {
            return (this.entity.outrasModalidadesAcoesAfirmativas?.outra_legislacao_descricao || '').length;
        },
        contadorCaracteres() {
            return this.text('contadorCaracteres').replace('%s', this.descricaoOutraLength);
        },
        hasError() {
            const err = this.entity.__validationErrors?.outrasModalidadesAcoesAfirmativas;
            return Array.isArray(err) && err.length > 0;
        },
        errorMessage() {
            const err = this.entity.__validationErrors?.outrasModalidadesAcoesAfirmativas;
            return Array.isArray(err) && err.length > 0 ? err[0] : '';
        },
        /** Erro quando nenhuma opção está marcada — mensagem no topo da seção */
        hasErrorNenhumaOpcao() {
            return this.hasError && this.opcoesArray.length === 0;
        },
        /** Erro no campo "outra legislação" (descrição vazia) — mensagem abaixo do input */
        hasErrorOutraLegislacao() {
            return this.hasError && this.opcoesArray.includes(OPCAO_OUTRA) && this.descricaoOutra.trim() === '';
        }
    },

    created() {
        this.ensureData();
    },

    methods: {
        getDefaultData() {
            const def = {
                opcoes: [],
                outra_legislacao_descricao: ''
            };
            this.opcoesComSublistaKeys.forEach((optionKey) => { def[optionKey] = []; });
            return def;
        },
        normalizeData(raw) {
            if (raw === null || raw === undefined) return this.getDefaultData();
            if (typeof raw === 'object' && !Array.isArray(raw)) {
                const data = { ...this.getDefaultData(), ...raw };
                if (!Array.isArray(data.opcoes)) data.opcoes = [];
                this.opcoesComSublistaKeys.forEach((optionKey) => {
                    data[optionKey] = Array.isArray(data[optionKey]) ? data[optionKey] : [];
                });
                data.outra_legislacao_descricao = typeof data.outra_legislacao_descricao === 'string' ? data.outra_legislacao_descricao : '';
                return data;
            }
            return this.getDefaultData();
        },
        ensureData() {
            if (!this.entity.outrasModalidadesAcoesAfirmativas || typeof this.entity.outrasModalidadesAcoesAfirmativas !== 'object') {
                this.entity.outrasModalidadesAcoesAfirmativas = { ...this.getDefaultData() };
            }
            const data = this.entity.outrasModalidadesAcoesAfirmativas;
            if (!Array.isArray(data.opcoes)) data.opcoes = [];
            this.opcoesComSublistaKeys.forEach((optionKey) => {
                if (!Array.isArray(data[optionKey])) data[optionKey] = [];
            });
            if (typeof data.outra_legislacao_descricao !== 'string') data.outra_legislacao_descricao = '';
        },
        setNaoPrevistas(checked) {
            this.ensureData();
            const data = this.entity.outrasModalidadesAcoesAfirmativas;
            if (checked) {
                data.opcoes = [OPCAO_EXCLUSIVA];
                this.opcoesComSublistaKeys.forEach((optionKey) => { data[optionKey] = []; });
                data.outra_legislacao_descricao = '';
            } else {
                data.opcoes = data.opcoes.filter((optionKey) => optionKey !== OPCAO_EXCLUSIVA);
            }
        },
        setOpcao(optionKey, checked) {
            this.ensureData();
            const data = this.entity.outrasModalidadesAcoesAfirmativas;
            if (checked) {
                if (data.opcoes.includes(OPCAO_EXCLUSIVA)) {
                    data.opcoes = [];
                }
                if (!data.opcoes.includes(optionKey)) data.opcoes.push(optionKey);
                if (this.opcoesComSublistaKeys.includes(optionKey) && !Array.isArray(data[optionKey])) data[optionKey] = [];
            } else {
                data.opcoes = data.opcoes.filter((key) => key !== optionKey);
                if (this.opcoesComSublistaKeys.includes(optionKey)) data[optionKey] = [];
                if (optionKey === OPCAO_OUTRA) data.outra_legislacao_descricao = '';
            }
        },
        getSublistModel(optionKey) {
            this.ensureData();
            const arr = this.entity.outrasModalidadesAcoesAfirmativas[optionKey];
            return Array.isArray(arr) ? arr : [];
        },
        setDescricaoOutra(value) {
            this.ensureData();
            this.entity.outrasModalidadesAcoesAfirmativas.outra_legislacao_descricao = (value || '').slice(0, MAX_DESCRICAO);
        },
        /** Erro na sublista da opção (opção marcada mas nenhuma subcategoria selecionada) — mensagem abaixo desse select */
        hasErrorForSublista(optionKey) {
            if (!this.hasError || !this.opcoesArray.includes(optionKey)) return false;
            const arr = this.getSublistModel(optionKey);
            return !Array.isArray(arr) || arr.length === 0;
        }
    }
});
