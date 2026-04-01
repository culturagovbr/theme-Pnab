app.component('home-header', {
    template: $TEMPLATES['home-header'],

    setup() {
        const text = Utils.getTexts('home-header');
        return { text };
    },

    data() {
        return {
            subsite: $MAPAS.subsite ?? {},

            banner: $MAPAS.config.homeHeader.banner,
            bannerLink: $MAPAS.config.homeHeader.bannerLink,
            downloadableLink: $MAPAS.config.homeHeader.downloadableLink,

            secondBanner: $MAPAS.config.homeHeader.secondBanner,
            secondBannerLink: $MAPAS.config.homeHeader.secondBannerLink,
            secondDownloadableLink: $MAPAS.config.homeHeader.secondDownloadableLink,

            thirdBanner: $MAPAS.config.homeHeader.thirdBanner,
            thirdBannerLink: $MAPAS.config.homeHeader.thirdBannerLink,
            thirdDownloadableLink: $MAPAS.config.homeHeader.thirdDownloadableLink,

            _parallaxEl: null,
            _parallaxCurrent: 0,
            _parallaxLoopActive: false,
            _parallaxRafId: null,
            _parallaxOnScroll: null,
        };
    },

    computed: {
        background() {
            if (this.subsite?.files?.header) {
                return this.subsite.files.header.url;
            }
            return $MAPAS.config.homeHeader.background;
        },
    },

    mounted() {
        this._parallaxEl = this.$el.querySelector('.home-header__parallax');
        if (!this._parallaxEl) {
            return;
        }
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        this._parallaxOnScroll = () => this._requestParallaxFrame();
        window.addEventListener('scroll', this._parallaxOnScroll, { passive: true });
        this._requestParallaxFrame();
    },

    beforeUnmount() {
        if (this._parallaxOnScroll) {
            window.removeEventListener('scroll', this._parallaxOnScroll);
        }
        if (this._parallaxRafId != null) {
            cancelAnimationFrame(this._parallaxRafId);
        }
    },

    methods: {
        _parallaxMaxShiftPx() {
            return window.innerWidth <= 768 ? 10 : 18;
        },

        _requestParallaxFrame() {
            if (!this._parallaxEl || this._parallaxLoopActive) {
                return;
            }
            this._parallaxLoopActive = true;
            this._parallaxRafId = requestAnimationFrame(() => this._parallaxStep());
        },

        _parallaxStep() {
            if (!this._parallaxEl) {
                this._parallaxLoopActive = false;
                return;
            }

            const maxShift = this._parallaxMaxShiftPx();
            const scrollY = window.scrollY || 0;
            const target = Math.max(
                -maxShift,
                Math.min(maxShift, scrollY * 0.04),
            );

            this._parallaxCurrent += (target - this._parallaxCurrent) * 0.18;

            this._parallaxEl.style.setProperty(
                '--home-header-parallax-y',
                `${this._parallaxCurrent.toFixed(2)}px`,
            );

            if (Math.abs(target - this._parallaxCurrent) > 0.02) {
                this._parallaxRafId = requestAnimationFrame(() => this._parallaxStep());
            } else {
                this._parallaxCurrent = target;
                this._parallaxEl.style.setProperty(
                    '--home-header-parallax-y',
                    `${this._parallaxCurrent.toFixed(2)}px`,
                );
                this._parallaxLoopActive = false;
            }
        },
    },
});
