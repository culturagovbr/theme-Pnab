app.component('google-analytics', {
    template: $TEMPLATES['google-analytics'],

    data() {
        return {
            trackingId: $MAPAS.config.googleAnalytics?.trackingId || '',
        };
    },

    mounted() {
        const trackingId = this.trackingId.trim();

        if (!/^G-[A-Z0-9]+$/.test(trackingId) || document.getElementById('google-analytics-script')) {
            return;
        }

        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () {
            window.dataLayer.push(arguments);
        };

        window.gtag('js', new Date());
        window.gtag('config', trackingId);

        const script = document.createElement('script');
        script.id = 'google-analytics-script';
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${trackingId}`;
        document.head.appendChild(script);
    },
});
