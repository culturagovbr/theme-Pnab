/**
 * Componente: Outras modalidades de ações afirmativas (edição de oportunidade).
 * Gerencia o metadado outrasModalidadesAcoesAfirmativas (opcoes, sublistas, descrição).
 * Lista de opções com sublista vem do backend (Theme::OPCOES_OUTRAS_MODALIDADES_COM_SUBLISTA via jsObject).
 */

const OPCAO_EXCLUSIVA = 'nao_previstas';
const OPCAO_OUTRA = 'outra_legislacao';
const MAX_DESCRICAO = 140;

/** Chaves das opções com sublista (fallback se config não estiver disponível; espelha Theme::OPCOES_OUTRAS_MODALIDADES_COM_SUBLISTA) */
const OPCOES_COM_SUBLISTA_FALLBACK = ['bonus_agentes', 'bonus_tematicas', 'categoria_especifica', 'edital_especifico'];

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
            return OPCOES_COM_SUBLISTA_FALLBACK.map(key => ({
                key,
                labelKey: key.split('_').map((s, i) => (i === 0 ? s : s.charAt(0).toUpperCase() + s.slice(1))).join('')
            }));
        },
        /** Apenas as chaves (para ensureData, validação, etc.) */
        opcoesComSublistaKeys() {
            return this.opcoesComSublista.map(item => item.key);
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
            return (key) => this.opcoesArray.includes(key);
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
            return this.sublistItems.reduce((acc, item) => {
                acc[item.value] = item.label;
                return acc;
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
            this.opcoesComSublistaKeys.forEach(op => { def[op] = []; });
            return def;
        },
        normalizeData(raw) {
            if (raw === null || raw === undefined) return this.getDefaultData();
            if (typeof raw === 'object' && !Array.isArray(raw)) {
                const d = { ...this.getDefaultData(), ...raw };
                if (!Array.isArray(d.opcoes)) d.opcoes = [];
                this.opcoesComSublistaKeys.forEach(op => {
                    d[op] = Array.isArray(d[op]) ? d[op] : [];
                });
                d.outra_legislacao_descricao = typeof d.outra_legislacao_descricao === 'string' ? d.outra_legislacao_descricao : '';
                return d;
            }
            return this.getDefaultData();
        },
        ensureData() {
            if (!this.entity.outrasModalidadesAcoesAfirmativas || typeof this.entity.outrasModalidadesAcoesAfirmativas !== 'object') {
                this.entity.outrasModalidadesAcoesAfirmativas = { ...this.getDefaultData() };
            }
            const d = this.entity.outrasModalidadesAcoesAfirmativas;
            if (!Array.isArray(d.opcoes)) d.opcoes = [];
            this.opcoesComSublistaKeys.forEach(op => {
                if (!Array.isArray(d[op])) d[op] = [];
            });
            if (typeof d.outra_legislacao_descricao !== 'string') d.outra_legislacao_descricao = '';
        },
        setNaoPrevistas(checked) {
            this.ensureData();
            const d = this.entity.outrasModalidadesAcoesAfirmativas;
            if (checked) {
                d.opcoes = [OPCAO_EXCLUSIVA];
                this.opcoesComSublistaKeys.forEach(op => { d[op] = []; });
                d.outra_legislacao_descricao = '';
            } else {
                d.opcoes = d.opcoes.filter(k => k !== OPCAO_EXCLUSIVA);
            }
        },
        setOpcao(key, checked) {
            this.ensureData();
            const d = this.entity.outrasModalidadesAcoesAfirmativas;
            if (checked) {
                if (d.opcoes.includes(OPCAO_EXCLUSIVA)) {
                    d.opcoes = [];
                }
                if (!d.opcoes.includes(key)) d.opcoes.push(key);
                if (this.opcoesComSublistaKeys.includes(key) && !Array.isArray(d[key])) d[key] = [];
            } else {
                d.opcoes = d.opcoes.filter(k => k !== key);
                if (this.opcoesComSublistaKeys.includes(key)) d[key] = [];
                if (key === OPCAO_OUTRA) d.outra_legislacao_descricao = '';
            }
        },
        getSublistModel(op) {
            this.ensureData();
            const arr = this.entity.outrasModalidadesAcoesAfirmativas[op];
            return Array.isArray(arr) ? arr : [];
        },
        setDescricaoOutra(val) {
            this.ensureData();
            this.entity.outrasModalidadesAcoesAfirmativas.outra_legislacao_descricao = (val || '').slice(0, MAX_DESCRICAO);
        },
        /** Erro na sublista da opção (opção marcada mas nenhuma subcategoria selecionada) — mensagem abaixo desse select */
        hasErrorForSublista(op) {
            if (!this.hasError || !this.opcoesArray.includes(op)) return false;
            const arr = this.getSublistModel(op);
            return !Array.isArray(arr) || arr.length === 0;
        }
    }
});
