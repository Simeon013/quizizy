/**
 * Tracés « à main levée » : chaque appel tire un nouveau hasard, si bien que
 * deux cercles, deux ratures ou deux coches ne sont jamais identiques, comme
 * sur un vrai cahier. Coordonnées en pixels de la boîte à couvrir.
 *
 * Chaque tracé renvoie une liste de traits { d, largeur, opacite, remplir? }
 * dessinés dans l'ordre : le composant Trace les anime l'un après l'autre.
 */
import { RoughGenerator } from 'roughjs/bin/generator';

const generateur = new RoughGenerator();
const alea = (min, max) => min + Math.random() * (max - min);
const graine = () => Math.floor(Math.random() * 2 ** 31);

/** Une courbe lisse qui passe par des points (Catmull-Rom → Bézier). */
function lisse(points) {
    let d = `M${points[0][0].toFixed(1)} ${points[0][1].toFixed(1)}`;
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i - 1] ?? points[i];
        const p1 = points[i];
        const p2 = points[i + 1];
        const p3 = points[i + 2] ?? p2;
        const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6];
        const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6];
        d += ` C${c1[0].toFixed(1)} ${c1[1].toFixed(1)} ${c2[0].toFixed(1)} ${c2[1].toFixed(1)} ${p2[0].toFixed(1)} ${p2[1].toFixed(1)}`;
    }
    return d;
}

/** Les chemins d'un dessin Rough.js (une à deux passes de crayon). */
function rough(dessin) {
    return generateur.toPaths(dessin).map((p) => p.d);
}

/**
 * Entourer : une boucle qui ne se referme jamais pile, déborde un peu et
 * repasse sur son début, comme le geste du correcteur.
 */
export function cercle(l, h) {
    const cx = l / 2 + alea(-l * 0.02, l * 0.02);
    const cy = h / 2 + alea(-h * 0.05, h * 0.05);
    const rx = l / 2 + alea(3, 10);
    const ry = h / 2 + alea(8, 13);
    const depart = alea(-Math.PI * 0.9, -Math.PI * 0.6);
    const tour = Math.PI * 2 + alea(0.25, 0.6);
    const pas = 18;
    const points = [];
    for (let i = 0; i <= pas; i++) {
        const a = depart + (tour * i) / pas;
        // Le rayon se resserre un peu au fil du geste.
        const k = 1 - (i / pas) * alea(0.02, 0.06) + alea(-0.025, 0.025);
        points.push([cx + Math.cos(a) * rx * k, cy + Math.sin(a) * ry * k]);
    }
    return [{ d: lisse(points), largeur: alea(2.6, 3.4), opacite: 0.92 }];
}

/**
 * Raturer : un gribouillis en zigzag sur la réponse fausse, dans une bande
 * étroite autour de la ligne du texte, assez léger pour qu'on le lise encore.
 */
export function rature(l, h) {
    const milieu = h / 2 + alea(-1.5, 1.5);
    const amplitude = Math.min(h * 0.4, 9) * alea(0.85, 1.1);
    // Des allers-retours serrés et arrondis, comme la pointe qui ne se lève pas.
    const allers = Math.max(6, Math.round(l / alea(8, 11)));
    const pente = alea(-2.5, 2.5);
    const points = [[alea(-6, 0), milieu + alea(-2, 2)]];
    for (let i = 1; i <= allers; i++) {
        const x = (l * i) / allers + alea(-4, 4);
        const haut = i % 2 === 1;
        points.push([Math.min(l + 6, x), milieu + (pente * i) / allers + (haut ? -amplitude : amplitude) * alea(0.7, 1.15)]);
    }
    // Un trait de rature en travers, posé plus vite.
    const travers = rough(
        generateur.line(alea(-4, 4), milieu + alea(-1, 3), l + alea(-2, 6), milieu + alea(-4, 1), { roughness: 1.1, bowing: 2, seed: graine(), disableMultiStroke: true }),
    );
    return [
        { d: lisse(points), largeur: alea(1.5, 1.9), opacite: 0.62 },
        { d: travers.join(' '), largeur: alea(1.8, 2.3), opacite: 0.7 },
    ];
}

/** Barrer d'un seul trait (moins appuyé qu'une rature). */
export function barre(l, h) {
    const y = h / 2 + alea(-3, 3);
    return [
        {
            d: rough(generateur.line(alea(-8, 0), y + alea(-3, 3), l + alea(0, 8), y + alea(-3, 3), { roughness: 1, bowing: 2.5, seed: graine() })).join(' '),
            largeur: alea(2.2, 2.8),
            opacite: 0.8,
        },
    ];
}

/** Cocher : petit appui, puis le grand trait qui part vers le haut et déborde de la case. */
export function coche(l, h) {
    const points = [
        [l * alea(0.12, 0.22), h * alea(0.5, 0.6)],
        [l * alea(0.38, 0.46), h * alea(0.78, 0.9)],
        [l * alea(0.95, 1.15), h * alea(-0.2, 0.05)],
    ];
    return [{ d: rough(generateur.linearPath(points, { roughness: 0.6, bowing: 0.5, seed: graine(), disableMultiStroke: true })).join(' '), largeur: alea(3, 3.8), opacite: 0.95 }];
}

/** Souligner, d'un trait qui n'est jamais tout à fait droit. */
export function souligne(l, h) {
    const y = h - alea(1, 4);
    return [
        {
            d: rough(generateur.line(alea(-4, 2), y + alea(-2, 2), l + alea(-2, 6), y + alea(-3, 2), { roughness: 1.2, bowing: 3, seed: graine() })).join(' '),
            largeur: alea(2.2, 3),
            opacite: 0.85,
        },
    ];
}

/**
 * Surligner : une bande aux bords irréguliers, un peu de travers, qui
 * déborde au début et s'arrête net à la fin, comme le feutre fluo.
 */
export function surligne(l, h) {
    const haut = h * alea(0.12, 0.2);
    const bas = h * alea(0.84, 0.92);
    const pente = alea(-3, 3);
    const debut = alea(-8, -2);
    const fin = l + alea(-2, 6);
    const pas = 6;
    const dessus = [];
    const dessous = [];
    for (let i = 0; i <= pas; i++) {
        const x = debut + ((fin - debut) * i) / pas;
        const decalage = (pente * i) / pas;
        dessus.push([x, haut + decalage + alea(-1.5, 1.5)]);
        dessous.push([x, bas + decalage + alea(-1.5, 1.5)]);
    }
    dessous.reverse();
    const d = lisse(dessus) + ' L' + dessous.map((p) => `${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' L') + ' Z';
    return [{ d, remplir: true, opacite: 0.9 }];
}

export const TRACES = { cercle, rature, barre, coche, souligne, surligne };
