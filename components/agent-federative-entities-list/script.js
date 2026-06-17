app.component('agent-federative-entities-list', {
    template: $TEMPLATES['agent-federative-entities-list'],
    emits: [],

    computed: {
        items() {
            return this.entities.map((item) => {
                const entity = Entity.fromJson({
                    '@entityType': 'agent',
                    id: item.id,
                    name: item.name,
                    status: 1,
                    type: {
                        id: 2,
                        name: item.document,
                    },
                    files: {},
                    currentUserPermissions: {},
                });

                entity.singleUrl = item.singleUrl;

                return entity;
            });
        },
    },

    methods: {
        showContent(name) {
            if (name.length > 45) {
                return name.substring(0, 45) + '...';
            }

            return name;
        },
    },

    props: {
        title: {
            type: String,
            required: true,
        },
        entities: {
            type: Array,
            required: true,
        },
    },
});
