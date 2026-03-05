app.component('agent-edit-defaults', {
    template: $TEMPLATES['agent-edit-defaults'],

    props: {
        entity: {
            type: Object,
            required: true
        }
    },

    watch: {
        entity: {
            handler(entity) {
                if (!entity || entity.__objectType !== 'agent') return;
                const v = entity.anosExperienciaAreaCultural;
                if (v === undefined || v === null || v === '') {
                    entity.anosExperienciaAreaCultural = 0;
                }
            },
            immediate: true
        }
    }
});
