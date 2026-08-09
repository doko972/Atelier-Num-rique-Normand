/**
 * Conseiller Numérique — JavaScript d'amélioration progressive.
 *
 * Le site reste entièrement utilisable sans JavaScript : la navigation, les
 * formulaires et les questions fréquentes fonctionnent nativement. Ce fichier
 * n'ajoute que du confort (menu repliable, préférences d'affichage,
 * confirmations de suppression).
 */

import { initAccessibilityPreferences } from './modules/accessibility.js';
import { initMobileMenu } from './modules/menu.js';
import { initConfirmations } from './modules/confirm.js';
import { initCookieBanner } from './modules/cookies.js';
import { initFormGuards } from './modules/forms.js';

const boot = () => {
    initAccessibilityPreferences();
    initMobileMenu();
    initConfirmations();
    initCookieBanner();
    initFormGuards();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
