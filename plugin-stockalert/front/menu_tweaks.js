/**
 * Personnalisation du menu GLPI : renommages et masquage d'entrées Assets.
 * Le texte des entrées est dans un <span class="text-wrap"> à l'intérieur de a.dropdown-item.
 */
(function () {
    'use strict';

    var RENAMES = {
        'Téléphones': 'Smartphones',
        'Téléphone':  'Smartphone',
        'Moniteurs':  'Écrans',
        'Moniteur':   'Écran',
    };

    var HIDDEN = ['Matériels réseau', 'Actifs non gérés', 'Global', 'Logiciels', 'Logiciel'];

    function applyTweaks() {
        document.querySelectorAll('a.dropdown-item span.text-wrap').forEach(function (span) {
            var txt = span.textContent.trim();

            if (HIDDEN.indexOf(txt) !== -1) {
                span.closest('a.dropdown-item').style.display = 'none';
                return;
            }

            if (RENAMES[txt]) {
                span.textContent = RENAMES[txt];
            }
        });
    }

    function init() {
        applyTweaks();
        new MutationObserver(applyTweaks).observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
