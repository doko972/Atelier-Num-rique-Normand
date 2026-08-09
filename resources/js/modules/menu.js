/**
 * Tiroir de navigation mobile.
 *
 * Amélioration progressive : sans ce script, le serveur a déjà rendu la
 * navigation dépliée et la feuille de style masque le bouton d'ouverture.
 * Personne ne se retrouve donc devant un menu injoignable ou un bouton inerte.
 *
 * Le comportement de tiroir n'est activé qu'après avoir posé `data-enhanced`,
 * ce qui garantit qu'aucun style de superposition ne s'applique tant que le
 * script n'a pas pris la main.
 */

const DESKTOP_QUERY = '(min-width: 62rem)';

/** Éléments pouvant recevoir le focus à l'intérieur du panneau. */
const FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export const initMobileMenu = () => {
    const toggle = document.querySelector('[data-menu-toggle]');
    const drawer = document.querySelector('[data-drawer]');

    if (!toggle || !drawer) {
        return;
    }

    const panel = drawer.querySelector('[data-drawer-panel]');
    const backdrop = drawer.querySelector('[data-drawer-backdrop]');
    const closeButton = drawer.querySelector('[data-drawer-close]');
    const toggleLabel = toggle.querySelector('[data-menu-toggle-label]');

    const desktop = window.matchMedia(DESKTOP_QUERY);

    // À partir d'ici seulement, la feuille de style applique le tiroir.
    drawer.dataset.enhanced = 'true';

    let isOpen = false;

    const labels = {
        open: toggle.dataset.labelOpen || 'Menu',
        close: toggle.dataset.labelClose || 'Fermer',
    };

    /**
     * Retourne les éléments focusables actuellement visibles du panneau.
     * @returns {HTMLElement[]}
     */
    const focusableItems = () =>
        Array.from(panel.querySelectorAll(FOCUSABLE)).filter(
            (element) => element.offsetParent !== null
        );

    const setState = (open) => {
        isOpen = open;

        drawer.dataset.open = String(open);
        toggle.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('has-drawer-open', open);

        if (toggleLabel) {
            toggleLabel.textContent = open ? labels.close : labels.open;
        }
    };

    const open = () => {
        if (isOpen) {
            return;
        }

        setState(true);

        // Le focus part sur le bouton de fermeture : la première chose
        // annoncée est donc le moyen de faire marche arrière.
        window.requestAnimationFrame(() => {
            (closeButton || focusableItems()[0])?.focus();
        });
    };

    const close = ({ restoreFocus = true } = {}) => {
        if (!isOpen) {
            return;
        }

        setState(false);

        if (restoreFocus) {
            toggle.focus();
        }
    };

    // -- Ouverture et fermeture ----------------------------------------------

    toggle.addEventListener('click', () => (isOpen ? close() : open()));

    closeButton?.addEventListener('click', () => close());

    // Cliquer à côté du panneau referme : c'est le geste attendu, et il évite
    // de devoir viser précisément le bouton de fermeture.
    backdrop?.addEventListener('click', () => close());

    // Suivre un lien referme le tiroir. Utile pour les ancres internes, qui
    // ne rechargent pas la page.
    panel.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) {
            close({ restoreFocus: false });
        }
    });

    // -- Clavier --------------------------------------------------------------

    document.addEventListener('keydown', (event) => {
        if (!isOpen) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            close();

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        // Piège de focus : tant que le tiroir est ouvert, la tabulation
        // tourne à l'intérieur. Sans cela, on se retrouverait à parcourir une
        // page masquée, sans comprendre où l'on est.
        const items = focusableItems();

        if (items.length === 0) {
            return;
        }

        const first = items[0];
        const last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    // -- Passage en grand écran ----------------------------------------------

    const handleViewportChange = (event) => {
        if (event.matches && isOpen) {
            // La navigation redevient une barre horizontale : le tiroir n'a
            // plus lieu d'être, et le défilement doit être rendu à la page.
            close({ restoreFocus: false });
        }
    };

    desktop.addEventListener('change', handleViewportChange);

    setState(false);
};
