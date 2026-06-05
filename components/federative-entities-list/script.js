app.component('federative-entities-list', {
    template: $TEMPLATES['federative-entities-list'],

    props: {
        entities: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            keyword: '',
            order: 'name ASC',
        };
    },

    computed: {
        cardEntities() {
            const keyword = this.keyword.trim().toLocaleLowerCase();

            return this.entities.filter((item) => {
                if (!keyword) {
                    return true;
                }

                const name = `${item.name || ''}`.toLocaleLowerCase();
                const document = `${item.document || ''}`.toLocaleLowerCase();

                return name.includes(keyword) || document.includes(keyword);
            }).sort((a, b) => {
                if (this.order === 'updateTimestamp ASC') {
                    return this.compareUpdatedAt(a, b, 'ASC');
                }

                if (this.order === 'updateTimestamp DESC') {
                    return this.compareUpdatedAt(a, b, 'DESC');
                }

                return this.compareName(a, b);
            }).map((item) => {
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

    methods: {
        compareName(a, b) {
            return `${a.name || ''}`.localeCompare(`${b.name || ''}`, 'pt-BR');
        },

        compareUpdatedAt(a, b, direction) {
            const aDate = Number(a.updatedAtOrder || 0);
            const bDate = Number(b.updatedAtOrder || 0);
            const result = direction === 'ASC' ? aDate - bDate : bDate - aDate;

            if (result !== 0) {
                return result;
            }

            return direction === 'ASC'
                ? Number(a.id || 0) - Number(b.id || 0)
                : Number(b.id || 0) - Number(a.id || 0);
        },
    },
});
