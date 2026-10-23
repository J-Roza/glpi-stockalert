/**
 * Stock Alert — style moderne des tuiles + raccourcissement des labels.
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

    function darken(hex, pct) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var r = Math.max(0, Math.round(parseInt(hex.slice(0,2),16) * (1 - pct/100)));
        var g = Math.max(0, Math.round(parseInt(hex.slice(2,4),16) * (1 - pct/100)));
        var b = Math.max(0, Math.round(parseInt(hex.slice(4,6),16) * (1 - pct/100)));
        return '#' + [r,g,b].map(function(v){ return v.toString(16).padStart(2,'0'); }).join('');
    }

    // Raccourcit "NOM (SEUIL : X)" → nom seul + corrige les inline styles de GLPI
    function fixLabels() {
        document.querySelectorAll('.card.summary-numbers a.line').forEach(function(line) {
            // Masque l'icône via inline style (GLPI la ré-affiche par JS)
            var icon = line.querySelector('i.icon');
            if (icon) icon.style.setProperty('display', 'none', 'important');

            // Corrige la font-size du chiffre (GLPI met ~57px en inline)
            var fn = line.querySelector('.formatted-number');
            if (fn) fn.style.setProperty('font-size', '1rem', 'important');

            // Corrige le label
            var label = line.querySelector('.label');
            if (!label) return;
            if (label.dataset.saFixed) {
                // Met à jour la font-size si GLPI l'a recalculée
                label.style.setProperty('font-size', '1rem', 'important');
                return;
            }
            label.dataset.saFixed = '1';
            label.style.setProperty('font-size', '1rem', 'important');
            var text = label.textContent.trim();
            var m = text.match(/^(.*?)\s*\(SEUIL\s*:\s*(\d+)\)\s*$/i);
            label.innerHTML = '<span class="sa-name">' + (m ? m[1].trim() : text) + '</span>';
        });
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

            // grid-stack-item-content est un parent — marqué par attribut pour CSS ciblé
            var gsic = card.closest('.grid-stack-item-content');
            if (gsic) {
                gsic.setAttribute('data-sa-widget', '1');
                // Réduit le conteneur à la hauteur du contenu
                gsic.style.setProperty('height', 'fit-content', 'important');
                gsic.style.setProperty('min-height', '0', 'important');
            }

            var css =
                '.grid-stack-item-content[data-sa-widget="1"]{'
                + 'overflow:visible!important;'
                + '}'
                +
                // Conteneur : flex row
                + '#' + cardId + ' .summary-numbers-container {'
                + 'display:flex!important;'
                + 'flex-direction:row!important;'
                + 'flex-wrap:wrap!important;'
                + 'gap:8px!important;'
                + 'padding:16px 10px 10px 10px!important;'
                + 'align-items:stretch!important;'
                + 'overflow:visible!important;'
                + '}'
                // Tuile : taille fixe uniforme
                + '#' + cardId + ' a.line {'
                + 'flex:1 1 0!important;'
                + 'min-width:110px!important;'
                + 'min-height:70px!important;'
                + 'min-width:130px!important;'
                + 'max-width:220px!important;'
                + 'border-radius:16px!important;'
                + 'transition:filter .18s ease,box-shadow .18s ease!important;'
                + 'overflow:hidden!important;'
                + 'margin:0!important;'
                + 'display:flex!important;'
                + 'flex-direction:column!important;'
                + 'align-items:center!important;'
                + 'justify-content:center!important;'
                + 'padding:8px 6px!important;'
                + 'box-sizing:border-box!important;'
                + 'text-align:center!important;'
                + '}'
                + '#' + cardId + ' a.line:hover {'
                + 'filter:brightness(1.12)!important;'
                + 'box-shadow:0 6px 22px rgba(0,0,0,.30)!important;'
                + 'text-decoration:none!important;'
                + '}'
                + '#' + cardId + ' a.line .content {'
                + 'display:flex!important;'
                + 'flex-direction:column!important;'
                + 'align-items:center!important;'
                + 'justify-content:center!important;'
                + 'gap:6px!important;'
                + 'width:100%!important;'
                + '}'
                + '#' + cardId + ' a.line .icon {'
                + 'display:none!important;'
                + '}'
                + '#' + cardId + ' a.line .number {'
                + 'font-size:2.4rem!important;font-weight:800!important;'
                + 'letter-spacing:-2px!important;line-height:1!important;'
                + '}'
                + '#' + cardId + ' a.line .label {'
                + 'font-size:.0rem!important;' // on masque le texte brut
                + '}'
                + '#' + cardId + ' a.line .label .sa-name {'
                + 'display:block!important;'
                + 'font-size:.72rem!important;font-weight:700!important;'
                + 'text-transform:uppercase!important;letter-spacing:.5px!important;'
                + 'line-height:1.2!important;word-break:break-word!important;'
                + 'opacity:.95!important;'
                + '}';

            alertes.forEach(function (a, i) {
                var bg   = a.couleur;
                var bg2  = darken(bg, 22);
                var txt  = luminance(bg) > 0.55 ? '#1a1a1a' : '#ffffff';
                var nth  = i + 1;
                var sel  = '#' + cardId + ' .line:nth-of-type(' + nth + ')';
                css += sel + '{'
                    + 'background:linear-gradient(145deg,' + bg + ' 0%,' + bg2 + ' 100%)!important;'
                    + 'box-shadow:0 4px 14px rgba(0,0,0,.18)!important;'
                    + '}';
                css += sel + ' .content,' + sel + ' .number,' + sel + ' .suffix,'
                    + sel + ' .icon,' + sel + ' .label,'
                    + sel + ' .label .sa-name,' + sel + ' .label .sa-seuil {'
                    + 'color:' + txt + '!important;}';
            });

            var style = document.createElement('style');
            style.id = 'sa-colors';
            style.textContent = css;
            document.head.appendChild(style);
            return true;
        }

        function applyAll() {
            var done = apply();
            fixLabels();
            return done;
        }

        var t = setInterval(function () { if (applyAll()) clearInterval(t); }, 200);
        setTimeout(function () { clearInterval(t); }, 30000);

        new MutationObserver(function () {
            if (!document.getElementById('sa-colors')) apply();
            fixLabels();
        }).observe(document.body, { childList: true, subtree: true });
    }

    fetch('/plugins/stockalert/front/colors.php', { credentials: 'include' })
        .then(function (r) { return r.json(); })
        .then(injectCSS)
        .catch(function (e) { console.warn('[StockAlert] colors.php error:', e); });
})();
