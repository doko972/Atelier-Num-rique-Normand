/**
 * Confort de saisie des formulaires publics.
 *
 * Aucune validation n'est déléguée au navigateur : le serveur reste seul juge.
 * On se contente d'aider la personne à repérer son erreur et d'éviter les
 * doubles envois, fréquents lorsqu'on n'est pas sûr d'avoir cliqué.
 */

export const initFormGuards = () => {
    // Place le focus sur le premier champ en erreur après un rechargement.
    const firstInvalid = document.querySelector('.field--invalid .field__control');

    if (firstInvalid && !document.querySelector('[data-no-autofocus]')) {
        firstInvalid.focus({ preventScroll: false });
    }

    document.querySelectorAll('form[data-guard-submit]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');

            if (!button || button.dataset.submitting === 'true') {
                return;
            }

            button.dataset.submitting = 'true';
            button.dataset.originalLabel = button.textContent.trim();
            button.textContent = button.dataset.submittingLabel || 'Envoi en cours…';

            // Le bouton reste actif pour ne pas bloquer un renvoi si la page
            // ne se recharge pas ; seul le libellé change.
            window.setTimeout(() => {
                button.dataset.submitting = 'false';
                button.textContent = button.dataset.originalLabel;
            }, 10000);
        });
    });

    // Compteur de caractères pour les zones de texte limitées.
    document.querySelectorAll('textarea[maxlength][data-counter]').forEach((textarea) => {
        const counter = document.getElementById(textarea.dataset.counter);

        if (!counter) {
            return;
        }

        const update = () => {
            const remaining = textarea.maxLength - textarea.value.length;
            counter.textContent = `${remaining} caractères restants`;
        };

        textarea.addEventListener('input', update);
        update();
    });
};
