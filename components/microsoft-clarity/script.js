app.component('microsoft-clarity', {
    template: $TEMPLATES['microsoft-clarity'],

    data() {
        return {
            projectId: $MAPAS.config.microsoftClarity?.projectId || '',
        };
    },

    mounted() {
        const projectId = this.projectId.trim();

        if (!/^[A-Z0-9]+$/i.test(projectId) || document.getElementById('microsoft-clarity-script')) {
            return;
        }

        window.clarity = window.clarity || function () {
            (window.clarity.q = window.clarity.q || []).push(arguments);
        };

        const script = document.createElement('script');
        script.id = 'microsoft-clarity-script';
        script.async = true;
        script.src = `https://www.clarity.ms/tag/${projectId}`;
        document.head.appendChild(script);
    },
});
