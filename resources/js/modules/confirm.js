/**
 * Confirmation avant une action destructrice.
 *
 * La sécurité ne repose jamais sur ce contrôle : le serveur revalide toujours
 * l'autorisation et la méthode HTTP. Il s'agit uniquement d'éviter un clic
 * malheureux, ce qui compte particulièrement pour un public peu à l'aise.
 */

export const initConfirmations = () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
};
