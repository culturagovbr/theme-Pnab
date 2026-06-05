app.component('federative-entities-list', {
    template: $TEMPLATES['federative-entities-list'],

    props: {
        entities: {
            type: Array,
            default: () => [],
        },
    },

    computed: {
        cardEntities() {
            return this.entities.map((item) => {
                const entity = Entity.fromJson({
                    '@entityType': 'agent',
                    id: item.id,
                    name: item.name,
                    status: 1,
                    type: {
                        id: 0,
                        name: item.document,
                    },
                    files: {},
                    currentUserPermissions: {},
                });

                entity.document = item.document;
                entity.managersCount = item.managersCount;
                entity.updatedAt = item.updatedAt;
                entity.singleUrl = '#';
                entity.editUrl = '#';

                return entity;
            });
        },
    },
});
