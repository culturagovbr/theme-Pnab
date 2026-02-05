(function () {
    if (typeof MapasCulturais === 'undefined') {
        window.MapasCulturais = {};
    }

    if (typeof MapasCulturais.Hook === 'undefined') {
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
    }

    // Register Pnab Hooks
    MapasCulturais.Hook.add('entity.save:before', function (entity) {
        if (entity.__objectType === 'opportunity' && Array.isArray(entity.registrationProponentTypes) && entity.registrationProponentTypes.length === 0) {
            return 'Selecione pelo menos uma opção em "Tipos do proponente".';
        }
    });

    MapasCulturais.Hook.add('entity.save:before', function (entity) {
        if (entity.__objectType === 'opportunity') {
            const rules = entity.files && entity.files.rules ? entity.files.rules : [];
            if (rules.length === 0) {
                return 'O campo "Adicionar regulamento" é obrigatório.';
            }
        }
    });

})();
