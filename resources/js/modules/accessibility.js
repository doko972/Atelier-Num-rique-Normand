/**
 * Préférences d'affichage : contraste renforcé, taille du texte, animations.
 *
 * Les préférences sont conservées dans localStorage. Il s'agit d'un stockage
 * strictement nécessaire au fonctionnement du service demandé par la personne :
 * aucun consentement préalable n'est requis et aucune donnée n'est transmise.
 */

const STORAGE_KEY = 'cn.a11y';

const DEFAULTS = {
    contrast: 'normal', // 'normal' | 'high'
    textSize: 'normal', // 'normal' | 'large' | 'x-large'
    motion: 'auto', // 'auto' | 'full' | 'reduced'
};

const TEXT_SIZES = ['normal', 'large', 'x-large'];

const read = () => {
    try {
        return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
    } catch {
        return { ...DEFAULTS };
    }
};

const write = (prefs) => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch {
        // Stockage indisponible (navigation privée) : la préférence reste
        // active pour la page courante, ce qui est acceptable.
    }
};

/**
 * Applique les préférences au document.
 * @param {object} prefs
 */
export const applyPreferences = (prefs) => {
    const root = document.documentElement;

    if (prefs.contrast === 'high') {
        root.setAttribute('data-contrast', 'high');
    } else {
        root.removeAttribute('data-contrast');
    }

    if (prefs.textSize && prefs.textSize !== 'normal') {
        root.setAttribute('data-text-size', prefs.textSize);
    } else {
        root.removeAttribute('data-text-size');
    }

    if (prefs.motion === 'auto') {
        root.removeAttribute('data-motion');
    } else {
        root.setAttribute('data-motion', prefs.motion);
    }
};

/**
 * Annonce un changement aux lecteurs d'écran.
 * @param {string} message
 */
const announce = (message) => {
    const region = document.getElementById('a11y-announcer');

    if (region) {
        region.textContent = message;
    }
};

export const initAccessibilityPreferences = () => {
    const prefs = read();

    applyPreferences(prefs);

    const contrastButton = document.querySelector('[data-a11y="contrast"]');
    const textUpButton = document.querySelector('[data-a11y="text-up"]');
    const textDownButton = document.querySelector('[data-a11y="text-down"]');
    const motionButton = document.querySelector('[data-a11y="motion"]');

    const syncButtons = () => {
        if (contrastButton) {
            contrastButton.setAttribute('aria-pressed', String(prefs.contrast === 'high'));
        }

        if (motionButton) {
            motionButton.setAttribute('aria-pressed', String(prefs.motion === 'reduced'));
        }

        if (textUpButton) {
            textUpButton.disabled = prefs.textSize === TEXT_SIZES[TEXT_SIZES.length - 1];
        }

        if (textDownButton) {
            textDownButton.disabled = prefs.textSize === TEXT_SIZES[0];
        }
    };

    const update = (changes, message) => {
        Object.assign(prefs, changes);
        applyPreferences(prefs);
        write(prefs);
        syncButtons();
        announce(message);
    };

    contrastButton?.addEventListener('click', () => {
        const next = prefs.contrast === 'high' ? 'normal' : 'high';
        update(
            { contrast: next },
            next === 'high' ? 'Contraste renforcé activé.' : 'Contraste normal rétabli.'
        );
    });

    motionButton?.addEventListener('click', () => {
        const next = prefs.motion === 'reduced' ? 'auto' : 'reduced';
        update(
            { motion: next },
            next === 'reduced' ? 'Animations désactivées.' : 'Animations rétablies.'
        );
    });

    const shiftTextSize = (direction) => {
        const index = TEXT_SIZES.indexOf(prefs.textSize);
        const nextIndex = Math.min(Math.max(index + direction, 0), TEXT_SIZES.length - 1);

        if (nextIndex === index) {
            return;
        }

        const labels = {
            normal: 'Taille du texte normale.',
            large: 'Texte agrandi.',
            'x-large': 'Texte très agrandi.',
        };

        update({ textSize: TEXT_SIZES[nextIndex] }, labels[TEXT_SIZES[nextIndex]]);
    };

    textUpButton?.addEventListener('click', () => shiftTextSize(1));
    textDownButton?.addEventListener('click', () => shiftTextSize(-1));

    syncButtons();
};
