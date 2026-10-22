/** Lista de beneficiários do resultado: de/para das colunas e planilha editável dos contemplados. */
app.component('opportunity-resultado-beneficiarios', {
    template: $TEMPLATES['opportunity-resultado-beneficiarios'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },
});
