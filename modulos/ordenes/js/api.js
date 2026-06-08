/**
 * Parchea fetch() para enviar automáticamente el X-API-Token
 * Debe cargarse DESPUÉS de que API_TOKEN esté definido.
 */
(function() {
    const origFetch = window.fetch;
    window.fetch = function(url, opts = {}) {
        if (!opts.headers) opts.headers = {};
        if (typeof window.API_TOKEN !== 'undefined' && window.API_TOKEN) {
            opts.headers['X-API-Token'] = window.API_TOKEN;
        }
        return origFetch.call(this, url, opts);
    };

    // También parchea FormData para POST legacy
    const origFormData = window.FormData;
    if (!window.__formDataPatched) {
        window.__formDataPatched = true;
    }
})();
