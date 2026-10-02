/**
 * Géométrie de Gribouille (repère 120 × 130). Le corps du « ? » et celui du « ! »
 * ont la même suite de commandes : on passe de l'un à l'autre en interpolant
 * les nombres (t = 0 : question, t = 1 : exclamation).
 */
export const FORMES = {
    q: [34, 44, 30, 14, 90, 8, 88, 42, 87, 62, 62, 62, 61, 86],
    e: [61, 12, 61, 30, 61, 30, 61, 46, 61, 62, 61, 62, 61, 86],
};
export const YEUX = {
    q: [
        [52, 31],
        [70, 30],
    ],
    e: [
        [54, 30],
        [68, 30],
    ],
};

export const mix = (a, b, t) => a.map((v, i) => v + (b[i] - v) * t);

export function corps(t) {
    const p = mix(FORMES.q, FORMES.e, t);
    return `M${p[0]} ${p[1]} C${p[2]} ${p[3]} ${p[4]} ${p[5]} ${p[6]} ${p[7]} C${p[8]} ${p[9]} ${p[10]} ${p[11]} ${p[12]} ${p[13]}`;
}

export function yeux(t) {
    return [mix(YEUX.q[0], YEUX.e[0], t), mix(YEUX.q[1], YEUX.e[1], t)];
}

/** Les poses : forme (q ou e), yeux, regard, inclinaison, accessoires. */
export const POSES = {
    curieux: { forme: 'q', yeux: 'ouverts', regard: [1, -2] },
    eureka: { forme: 'e', yeux: 'rieurs', extra: ['eclat'] },
    reflechit: { forme: 'q', yeux: 'ouverts', regard: [-2, -2], penche: -8, extra: ['crayon'] },
    oups: { forme: 'q', yeux: 'inquiets', regard: [0, 2], penche: 8, extra: ['vert'] },
    bravo: { forme: 'e', yeux: 'grands', regard: [0, 0], extra: ['confettis', 'surligne'] },
    dodo: { forme: 'q', yeux: 'fermes', penche: 14, extra: ['dodo'] },
    presse: { forme: 'q', yeux: 'grands', regard: [2, 0], penche: -4, extra: ['goutte'] },
};
