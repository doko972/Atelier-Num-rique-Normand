/**
 * Bandeau de consentement.
 *
 * Il n'est rendu par le serveur que si un traceur non essentiel est réellement
 * configuré (mesure d'audience). Aucun traceur n'est déposé avant un accord
 * explicite ; le refus est aussi accessible que l'acceptation.
 */

const STORAGE_KEY = 'cn.cookies';

const readChoice = () => {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
};

const writeChoice = (value) => {
    try {
        localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Ignoré : sans stockage, le bandeau réapparaîtra, ce qui reste correct.
    }
};

/**
 * Charge la mesure d'audience après acceptation.
 * @param {HTMLElement} banner
 */
const loadAnalytics = (banner) => {
    const url = banner.dataset.analyticsUrl;
    const siteId = banner.dataset.analyticsSiteId;

    if (!url || !siteId || document.getElementById('matomo-script')) {
        return;
    }

    window._paq = window._paq || [];
    window._paq.push(['setTrackerUrl', `${url}matomo.php`]);
    window._paq.push(['setSiteId', siteId]);
    window._paq.push(['setSecureCookie', true]);
    window._paq.push(['trackPageView']);
    window._paq.push(['enableLinkTracking']);

    const script = document.createElement('script');
    script.id = 'matomo-script';
    script.async = true;
    script.src = `${url}matomo.js`;
    document.head.appendChild(script);
};

export const initCookieBanner = () => {
    const banner = document.querySelector('[data-cookie-banner]');

    if (!banner) {
        return;
    }

    const choice = readChoice();

    if (choice === 'accepted') {
        loadAnalytics(banner);

        return;
    }

    if (choice === 'refused') {
        return;
    }

    banner.hidden = false;

    banner.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
        writeChoice('accepted');
        banner.hidden = true;
        loadAnalytics(banner);
    });

    banner.querySelector('[data-cookie-refuse]')?.addEventListener('click', () => {
        writeChoice('refused');
        banner.hidden = true;
    });
};

/**
 * Permet de retirer son consentement depuis la page « Gestion des cookies ».
 */
export const resetCookieChoice = () => {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Sans stockage il n'y a de toute façon aucun consentement enregistré.
    }
};
