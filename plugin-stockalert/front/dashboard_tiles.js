/**
 * Stock Alert — récupère les couleurs depuis l'API PHP et injecte une balise <style>
 * persistante. Pas de lecture DOM : les couleurs viennent directement de la DB.
 */
(function () {
    'use strict';
    window.__sa_loaded = true;

    function luminance(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var r = parseInt(hex.slice(0,2),16);
        var g = parseInt(hex.slice(2,4),16);
        var b = parseInt(hex.slice(4,6),16);
        return (0.299*r + 0.587*g + 0.114*b) / 255;
    }

    function injectCSS(alertes) {
        function apply() {
            if (document.getElementById('sa-colors')) return true;

            var lines = document.querySelectorAll('.card.summary-numbers a.line[href*="contains"]');
            if (lines.length === 0) return false;

            var card = lines[0].closest('.card');
            if (!card) return false;
            var cardId = card.id || ('sa-' + Date.now());
            if (!card.id) card.id = cardId;

            var css = '';
            alertes.forEach(function (a, i) {
                var bg  = a.couleur;
                var txt = luminance(bg) > 0.55 ? '#1a1a1a' : '#ffffff';
                var nth = i + 1;
                var sel = '#' + cardId + ' .line:nth-of-type(' + nth + ')';
                css += sel + '{background-color:' + bg + '!important;border-radius:4px!important}';
                css += sel + ' .content,' + sel + ' .number,' + sel + ' .suffix,' + sel + ' .icon,' + sel + ' .label{color:' + txt + '!important}';
            });

            var style = document.createElement('style');
            style.id = 'sa-colors';
            style.textContent = css;
            document.head.appendChild(style);
            return true;
        }

        // Polling jusqu'à ce que les tuiles soient dans le DOM
        var t = setInterval(function () {
            if (apply()) clearInterval(t);
        }, 200);
        setTimeout(function () { clearInterval(t); }, 30000);

        // Réapplique si le widget est rafraîchi (ID ou style tag disparu)
        new MutationObserver(function () {
            if (!document.getElementById('sa-colors')) apply();
        }).observe(document.body, { childList: true, subtree: true });
    }

    // Récupérer les couleurs depuis notre backend PHP
    fetch('/plugins/stockalert/front/colors.php', { credentials: 'include' })
        .then(function (r) { return r.json(); })
        .then(injectCSS)
        .catch(function (e) { console.warn('[StockAlert] colors.php error:', e); });
})();
