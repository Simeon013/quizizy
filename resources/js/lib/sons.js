/**
 * Les bruits du cahier, fabriqués dans le navigateur (Web Audio) : aucun
 * fichier à télécharger, aucun droit à régler. Le crayon qui gratte pendant
 * un tracé, la page qui tourne, le tampon, la gomme.
 *
 * Coupés si le joueur l'a choisi (bouton dans l'en-tête, `eureka:son`).
 */
const CLE = 'eureka:son';
let contexte = null;
let bruitBlanc = null;

export function sonActif() {
    try {
        return localStorage.getItem(CLE) !== 'non';
    } catch {
        return true;
    }
}

export function reglerSon(actif) {
    try {
        localStorage.setItem(CLE, actif ? 'oui' : 'non');
    } catch {
        // Stockage refusé : le réglage vaut pour la page seulement.
    }
}

function ctx() {
    if (!sonActif() || typeof window === 'undefined' || document.hidden) return null;
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    if (!contexte) {
        contexte = new Ctx();
        // Deux secondes de bruit blanc, réutilisées par tous les sons.
        bruitBlanc = contexte.createBuffer(1, contexte.sampleRate * 2, contexte.sampleRate);
        const donnees = bruitBlanc.getChannelData(0);
        for (let i = 0; i < donnees.length; i++) donnees[i] = Math.random() * 2 - 1;
    }
    if (contexte.state === 'suspended') contexte.resume();
    return contexte;
}

function bruit(c, debut, duree) {
    const source = c.createBufferSource();
    source.buffer = bruitBlanc;
    source.loop = true;
    source.start(debut, Math.random() * 1.5);
    source.stop(debut + duree + 0.05);
    return source;
}

/**
 * Le crayon qui gratte le papier pendant `duree` secondes. L'appui varie sans
 * cesse : jamais deux fois le même son, comme jamais deux fois le même trait.
 */
export function crayon(duree = 0.5, force = 1, decalage = 0) {
    const c = ctx();
    if (!c) return;
    const t0 = c.currentTime + decalage;
    const filtre = c.createBiquadFilter();
    filtre.type = 'bandpass';
    filtre.frequency.value = 2600 + Math.random() * 2200;
    filtre.Q.value = 0.7 + Math.random() * 0.6;
    const aigus = c.createBiquadFilter();
    aigus.type = 'highpass';
    aigus.frequency.value = 900;
    const gain = c.createGain();
    const volume = 0.09 * force;
    gain.gain.setValueAtTime(0, t0);
    // L'appui de la main : de petites variations toutes les 25 à 45 ms.
    let t = t0 + 0.015;
    while (t < t0 + duree) {
        gain.gain.linearRampToValueAtTime(volume * (0.35 + Math.random() * 0.75), t);
        filtre.frequency.linearRampToValueAtTime(2400 + Math.random() * 2600, t);
        t += 0.025 + Math.random() * 0.02;
    }
    gain.gain.linearRampToValueAtTime(0, t0 + duree + 0.04);
    bruit(c, t0, duree).connect(filtre).connect(aigus).connect(gain).connect(c.destination);
}

/** La page qu'on tourne : un souffle de papier qui monte puis retombe. */
export function page() {
    const c = ctx();
    if (!c) return;
    const t0 = c.currentTime;
    const duree = 0.32 + Math.random() * 0.12;
    const filtre = c.createBiquadFilter();
    filtre.type = 'lowpass';
    filtre.Q.value = 0.6;
    filtre.frequency.setValueAtTime(500, t0);
    filtre.frequency.exponentialRampToValueAtTime(4200 + Math.random() * 1500, t0 + duree * 0.45);
    filtre.frequency.exponentialRampToValueAtTime(700, t0 + duree);
    const gain = c.createGain();
    gain.gain.setValueAtTime(0, t0);
    gain.gain.linearRampToValueAtTime(0.16, t0 + duree * 0.35);
    gain.gain.exponentialRampToValueAtTime(0.001, t0 + duree);
    bruit(c, t0, duree).connect(filtre).connect(gain).connect(c.destination);
}

/** Le tampon : un coup sourd et un petit claquement. */
export function tampon(decalage = 0) {
    const c = ctx();
    if (!c) return;
    const t0 = c.currentTime + decalage;
    const osc = c.createOscillator();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(140, t0);
    osc.frequency.exponentialRampToValueAtTime(55, t0 + 0.16);
    const gain = c.createGain();
    gain.gain.setValueAtTime(0.5, t0);
    gain.gain.exponentialRampToValueAtTime(0.001, t0 + 0.22);
    osc.connect(gain).connect(c.destination);
    osc.start(t0);
    osc.stop(t0 + 0.25);

    const filtre = c.createBiquadFilter();
    filtre.type = 'lowpass';
    filtre.frequency.value = 1800;
    const claque = c.createGain();
    claque.gain.setValueAtTime(0.25, t0);
    claque.gain.exponentialRampToValueAtTime(0.001, t0 + 0.06);
    bruit(c, t0, 0.07).connect(filtre).connect(claque).connect(c.destination);
}

/** Un petit tapotement de crayon (une case cochée, un bouton). */
export function tapote() {
    const c = ctx();
    if (!c) return;
    const t0 = c.currentTime;
    const filtre = c.createBiquadFilter();
    filtre.type = 'bandpass';
    filtre.frequency.value = 1800 + Math.random() * 800;
    const gain = c.createGain();
    gain.gain.setValueAtTime(0.22, t0);
    gain.gain.exponentialRampToValueAtTime(0.001, t0 + 0.05);
    bruit(c, t0, 0.06).connect(filtre).connect(gain).connect(c.destination);
}

/** La gomme : quelques allers-retours sourds sur le papier. */
export function gomme() {
    const c = ctx();
    if (!c) return;
    const t0 = c.currentTime;
    const allers = 3 + Math.floor(Math.random() * 2);
    const filtre = c.createBiquadFilter();
    filtre.type = 'lowpass';
    filtre.frequency.value = 1100 + Math.random() * 400;
    const gain = c.createGain();
    gain.gain.setValueAtTime(0, t0);
    let t = t0;
    for (let i = 0; i < allers; i++) {
        const d = 0.07 + Math.random() * 0.04;
        gain.gain.linearRampToValueAtTime(0.22 + Math.random() * 0.1, t + d * 0.4);
        gain.gain.linearRampToValueAtTime(0.02, t + d);
        t += d + 0.015;
    }
    gain.gain.linearRampToValueAtTime(0, t + 0.02);
    bruit(c, t0, t - t0).connect(filtre).connect(gain).connect(c.destination);
}
