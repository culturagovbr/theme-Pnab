(function () {
    if (typeof MapasCulturais === 'undefined') {
        window.MapasCulturais = {};
    }

    if (typeof MapasCulturais.Hook !== 'undefined') {
        return;
    }

    MapasCulturais.Hook = {
        _hooks: {},

        add(name, fn) {
            if (!this._hooks[name]) {
                this._hooks[name] = [];
            }
            this._hooks[name].push(fn);
        },

        apply(name, args) {
            if (!this._hooks[name]) {
                return [];
            }

            let results = [];
            this._hooks[name].forEach(fn => {
                try {
                    let res = fn.apply(null, args);
                    if (res) results.push(res);
                } catch (e) {
                    console.error("Hook error:", e);
                }
            });
            return results;
        }
    };
})();
