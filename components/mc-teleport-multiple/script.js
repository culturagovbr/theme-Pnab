const DEFAULT_MESSAGE_STEP_MS = 3500;

app.component('mc-teleport-multiple', {
    template: $TEMPLATES['mc-teleport-multiple'],
    props: {
        /** Alvo do Vue Teleport (seletor CSS, ex. `body`, ou elemento DOM). */
        to: {
            type: [String, Object],
            default: 'body',
        },
        /** Quando true, o Teleport não move o conteúdo (renderiza no lugar). */
        disabled: {
            type: Boolean,
            default: false,
        },
        /** Exibe overlay + painel (spinner e mensagem). */
        show: {
            type: Boolean,
            default: false,
        },
        /** Texto único; usado se `messages` não tiver strings válidas. */
        message: {
            type: String,
            default: '',
        },
        /** Lista de mensagens alternadas enquanto `show` for true (2+ itens ativam rotação). */
        messages: {
            type: Array,
            default: () => [],
        },
        /** Intervalo em ms entre trocas. Default 3500; `0` desativa rotação (fica no primeiro passo). */
        messageStepMs: {
            type: Number,
            default: DEFAULT_MESSAGE_STEP_MS,
        },
        /** Bloqueia clique/toque no overlay (`preventDefault` + `pointer-events` exceto em modo pass-through). */
        blockInteraction: {
            type: Boolean,
            default: true,
        },
        /** Duração do fade entre mensagens (ms). */
        fadeDurationMs: {
            type: Number,
            default: 320,
        },
    },
    data() {
        return {
            stepIndex: 0,
            _stepTimerId: null,
        };
    },
    computed: {
        steps() {
            const list = this.messages;
            let fromList = [];
            if (Array.isArray(list) && list.length > 0) {
                fromList = list.filter((s) => typeof s === 'string' && s.length > 0);
            }
            if (fromList.length > 0) {
                return fromList;
            }
            if (this.message) {
                return [this.message];
            }
            return [];
        },
        displayedText() {
            const s = this.steps;
            if (!s.length) {
                return '';
            }
            return s[this.stepIndex % s.length];
        },
        effectiveMessageStepMs() {
            const n = Number(this.messageStepMs);
            if (n === 0) {
                return 0;
            }
            if (!Number.isFinite(n) || n < 0) {
                return DEFAULT_MESSAGE_STEP_MS;
            }
            return n;
        },
        messageTrackStyle() {
            const ms = Number(this.fadeDurationMs);
            const v = Number.isFinite(ms) && ms >= 0 ? ms : 320;
            return {
                '--mc-teleport-multiple-fade-duration': `${v}ms`,
            };
        },
    },
    watch: {
        show: {
            immediate: true,
            handler(val) {
                if (val) {
                    this.stepIndex = 0;
                    this.restartStepTimer();
                } else {
                    this.clearStepTimer();
                }
            },
        },
        steps: {
            handler() {
                this.stepIndex = 0;
                if (this.show) {
                    this.restartStepTimer();
                } else {
                    this.clearStepTimer();
                }
            },
        },
        messageStepMs() {
            if (this.show) {
                this.restartStepTimer();
            }
        },
    },
    unmounted() {
        this.clearStepTimer();
    },
    methods: {
        handleOverlayPointer(e) {
            if (!this.blockInteraction) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
        },
        restartStepTimer() {
            this.clearStepTimer();
            this.startStepTimer();
        },
        startStepTimer() {
            if (!this.show) {
                return;
            }
            if (this.steps.length <= 1) {
                return;
            }
            const ms = this.effectiveMessageStepMs;
            if (!ms || ms <= 0) {
                return;
            }
            this._stepTimerId = setInterval(() => {
                this.stepIndex = (this.stepIndex + 1) % this.steps.length;
            }, ms);
        },
        clearStepTimer() {
            if (this._stepTimerId != null) {
                clearInterval(this._stepTimerId);
                this._stepTimerId = null;
            }
        },
    },
});
