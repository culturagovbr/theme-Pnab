app.component('main-footer', {
    template: $TEMPLATES['main-footer'],

    setup() { 
        const text = Utils.getTexts('main-footer')
        const globalState = useGlobalState();
        return { text, globalState}
    },

    data() {
        return {
            canAccess: $MAPAS.config.canAccess,
        }
    },

    methods: {
        getVisibility(key) {
            if (key !== 'opportunities') {
                return this.global.enabledEntities[key];
            }
            return this.canAccess && this.global.enabledEntities[key];
        }
    },
});
