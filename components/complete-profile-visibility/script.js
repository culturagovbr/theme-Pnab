/**
 * Visibilidade condicional na tela Complete-profile.
 * Calcula uma vez (no carregamento) quais campos estavam vazios e expõe
 * isFieldVisible(prop) e isAddressVisible para a view aplicar v-if.
 * Campos preenchidos na sessão não deixam de ser exibidos.
 */
app.component('complete-profile-visibility', {
    template: $TEMPLATES['complete-profile-visibility'],

    props: {
        entity: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            initialized: false,
            visibleFieldsSet: new Set(),
            addressVisible: false,
            showCardApresentacao: false,
            showCardPessoais: false,
            showCardSensiveis: false
        };
    },

    computed: {
        isFieldVisible() {
            return (prop) => this.visibleFieldsSet.has(prop);
        }
    },

    watch: {
        entity: {
            handler(entity) {
                if (!entity || this.initialized) return;
                const hasId = entity.id != null || entity.__objectId != null;
                if (!hasId) return;
                this.initVisibility();
            },
            immediate: true
        }
    },

    methods: {
        isEmpty(val) {
            if (val === null || val === undefined) return true;
            if (val === '') return true;
            if (Array.isArray(val) && val.length === 0) return true;
            return false;
        },

        initVisibility() {
            if (this.initialized || !this.entity) return;
            const entity = this.entity;
            const set = new Set();

            const fieldProps = [
                'name',
                'shortDescription',
                'nomeSocial',
                'nomeCompleto',
                'type',
                'cpf',
                'emailPrivado',
                'telefonePublico',
                'acessouFomentoCultural',
                'anosExperienciaAreaCultural',
                'eMestreCulturasTradicionais',
                'dataDeNascimento',
                'genero',
                'orientacaoSexual',
                'raca',
                'renda',
                'escolaridade',
                'pessoaDeficiente',
                'comunidadesTradicional'
            ];

            fieldProps.forEach((prop) => {
                const val = entity[prop];
                if (this.isEmpty(val)) set.add(prop);
            });

            if (this.isEmpty(entity.terms?.area) || (Array.isArray(entity.terms?.area) && entity.terms.area.length === 0)) {
                set.add('terms.area');
            }

            const addressProps = ['En_Nome_Logradouro', 'En_Num', 'En_Bairro', 'En_Municipio', 'En_Estado', 'En_CEP'];
            const anyAddressEmpty = addressProps.some((prop) => this.isEmpty(entity[prop]));
            if (anyAddressEmpty) {
                this.addressVisible = true;
            }

            this.visibleFieldsSet = set;

            this.showCardApresentacao =
                set.has('name') || set.has('terms.area') || set.has('shortDescription');
            this.showCardPessoais =
                set.has('nomeSocial') ||
                set.has('nomeCompleto') ||
                set.has('type') ||
                set.has('cpf') ||
                set.has('emailPrivado') ||
                set.has('telefonePublico') ||
                set.has('acessouFomentoCultural') ||
                set.has('anosExperienciaAreaCultural') ||
                set.has('eMestreCulturasTradicionais') ||
                this.addressVisible;
            this.showCardSensiveis =
                set.has('dataDeNascimento') ||
                set.has('genero') ||
                set.has('orientacaoSexual') ||
                set.has('raca') ||
                set.has('renda') ||
                set.has('escolaridade') ||
                set.has('pessoaDeficiente') ||
                set.has('comunidadesTradicional');

            this.initialized = true;
        }
    }
});
