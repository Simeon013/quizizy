/**
 * Rien n'est identique sur du papier : chaque feuille, post-it ou case de
 * réponse reçoit, à son apparition, une inclinaison et des coins tirés au
 * hasard (propriétés `rotate` et `border-radius` en ligne : elles
 * s'ajoutent aux `transform` des animations sans les gêner).
 *
 * L'angle diminue avec la taille : au plus ~3 px de décalage sur la
 * hauteur (une page haute penchée d'un degré déborderait de l'écran) et ~6 px
 * sur la largeur ; au-delà de 520 px de haut, seuls les coins changent.
 */
const SELECTEUR = '.feuille, .postit, .choix, .tuile, .btn';
const alea = (min, max) => min + Math.random() * (max - min);

function coins(min, max) {
    const r = () => Math.round(alea(min, max));
    return `${r()}px ${r()}px ${r()}px ${r()}px / ${r()}px ${r()}px ${r()}px ${r()}px`;
}

function hasarder(el) {
    if (el.dataset.papier) return;
    el.dataset.papier = '1';
    const h = el.offsetHeight;
    const l = el.offsetWidth;
    if (!h || !l) return;
    const postit = el.classList.contains('postit');
    const grand = Math.max(h, l);
    const deg = (r) => (Math.atan(r) * 180) / Math.PI;
    const maxi = h > 520 ? 0 : Math.min(postit ? 2.2 : 0.9, deg(3 / h), deg(6 / l));
    if (maxi > 0.05) el.style.rotate = `${alea(-maxi, maxi).toFixed(2)}deg`;
    if (!postit) el.style.borderRadius = grand > 520 ? coins(8, 16) : coins(7, 18);
}

function parcourir(racine) {
    if (!(racine instanceof Element)) return;
    if (racine.matches(SELECTEUR)) hasarder(racine);
    racine.querySelectorAll(SELECTEUR).forEach(hasarder);
}

export function papierVivant() {
    let attente = new Set();
    let prevu = false;
    const traiter = () => {
        prevu = false;
        attente.forEach(parcourir);
        attente = new Set();
    };
    new MutationObserver((mutations) => {
        for (const m of mutations) m.addedNodes.forEach((n) => attente.add(n));
        if (!prevu) {
            prevu = true;
            requestAnimationFrame(traiter);
        }
    }).observe(document.body, { childList: true, subtree: true });
    requestAnimationFrame(() => parcourir(document.body));
}
