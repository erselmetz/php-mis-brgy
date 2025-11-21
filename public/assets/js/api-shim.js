/* API shim: rewrite root-relative /api/... calls to include the 'public' folder
   This helps setups where the project is served from a subpath (e.g. /php-mis-brgy/public)
   It patches window.fetch and jQuery.ajax to prefix the correct base when necessary.
*/
(function() {
    function detectBase() {
        try {
            const parts = window.location.pathname.split('/').filter(Boolean);
            const publicIndex = parts.indexOf('public');
            if (publicIndex !== -1) {
                return '/' + parts.slice(0, publicIndex + 1).join('/');
            }
            // if repo folder exists (common in local browsing), try to include it
            const repoIndex = parts.indexOf('php-mis-brgy');
            if (repoIndex !== -1) {
                return '/' + parts.slice(0, repoIndex + 2).join('/');
            }
            return '';
        } catch (e) {
            return '';
        }
    }

    const base = detectBase();
    if (!base) return; // nothing to do

    // Patch fetch
    const origFetch = window.fetch.bind(window);
    window.fetch = function(resource, init) {
        if (typeof resource === 'string' && resource.startsWith('/api/')) {
            resource = base + resource;
        }
        return origFetch(resource, init);
    };

    // Patch jQuery.ajax if present
    function patchJQuery($) {
        if (!$ || !$.ajax) return;
        const origAjax = $.ajax.bind($);
        $.ajax = function(url, settings) {
            // signature: $.ajax(url, settings) or $.ajax(settings)
            if (typeof url === 'string') {
                if (url.startsWith('/api/')) url = base + url;
                if (settings && settings.url && settings.url.startsWith('/api/')) settings.url = base + settings.url;
                return origAjax(url, settings);
            }
            if (url && url.url && typeof url.url === 'string' && url.url.startsWith('/api/')) {
                url.url = base + url.url;
            }
            return origAjax(url);
        };
    }

    // If jQuery is already loaded, patch now; otherwise wait for DOM ready
    if (window.jQuery) {
        patchJQuery(window.jQuery);
    } else {
        window.addEventListener('DOMContentLoaded', function() {
            if (window.jQuery) patchJQuery(window.jQuery);
        });
    }
})();
