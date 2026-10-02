/**
 * Thème : « clair » (le cahier), « sombre » (le cahier de nuit) ou celui de
 * l'appareil (aucune valeur). Le choix est posé avant le premier rendu par un
 * script dans app.blade.php, pour éviter un éclair blanc.
 */
const CLE = 'eureka:theme';

export function themeEnregistre() {
    try {
        return localStorage.getItem(CLE);
    } catch {
        return null;
    }
}

export function appliquerTheme(valeur) {
    const racine = document.documentElement;
    if (valeur) racine.dataset.theme = valeur;
    else delete racine.dataset.theme;
    try {
        if (valeur) localStorage.setItem(CLE, valeur);
        else localStorage.removeItem(CLE);
    } catch {
        // Stockage refusé (navigation privée) : le choix vaut pour la page seulement.
    }
}

export function themeEffectif() {
    const choisi = document.documentElement.dataset.theme;
    if (choisi) return choisi;
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'sombre' : 'clair';
}
