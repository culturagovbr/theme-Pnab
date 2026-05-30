app.component('tawk-to-chat', {
    template: $TEMPLATES['tawk-to-chat'],

    mounted() {
        if (document.getElementById('tawk-to-chat-script')) {
            return;
        }

        window.Tawk_API = window.Tawk_API || {};
        window.Tawk_LoadStart = window.Tawk_LoadStart || new Date();

        const script = document.createElement('script');
        const firstScript = document.getElementsByTagName('script')[0];

        script.id = 'tawk-to-chat-script';
        script.async = true;
        script.src = 'https://embed.tawk.to/6823ecdb470adc190e4b94f1/1jnae3p47';
        script.charset = 'UTF-8';
        script.setAttribute('crossorigin', '*');

        firstScript.parentNode.insertBefore(script, firstScript);
    },
});
