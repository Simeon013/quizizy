<script setup>
/**
 * Un tracé à main levée posé sur son parent (qui doit être positionné) :
 * cercle, rature, barre, coche, soulignement ou surligneur. Dessiné trait par
 * trait, au rythme d'une main, avec le bruit du crayon. Chaque tracé est tiré
 * au hasard (lib/main-levee.js) : jamais deux fois le même.
 *
 * `parLigne` : sur un texte de plusieurs lignes, un tracé par ligne écrite
 * (une rature ne couvre que les mots, pas la case vide d'à côté).
 * Avec « mouvement réduit », le tracé apparaît d'un coup et sans bruit.
 */
import { onBeforeUnmount, onMounted, ref, nextTick } from 'vue';
import { TRACES } from '../lib/main-levee';
import { crayon } from '../lib/sons';

const props = defineProps({
    type: { type: String, required: true },
    couleur: { type: String, default: 'var(--rouge)' },
    delai: { type: Number, default: 0 }, // ms
    son: { type: Boolean, default: true },
    auVisible: { type: Boolean, default: false },
    parLigne: { type: Boolean, default: false },
    anime: { type: Boolean, default: true },
});

// Vitesse de la main, en pixels par milliseconde : un cercle part d'un geste, une coche s'appuie.
const VITESSE = { cercle: 2.6, rature: 1.9, barre: 1.7, coche: 0.75, souligne: 1.7, surligne: 1.3 };

const svg = ref(null);
const traits = ref([]); // { d, x, y, largeur, opacite, remplir }
let observateur = null;
let taille = null;
let animations = [];

const reduit = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/** Les boîtes des lignes de texte du parent, relatives à lui. */
function lignes(parent) {
    const origine = parent.getBoundingClientRect();
    const parcours = document.createTreeWalker(parent, NodeFilter.SHOW_TEXT);
    const boites = [];
    const plage = document.createRange();
    while (parcours.nextNode()) {
        const noeud = parcours.currentNode;
        if (!noeud.textContent.trim() || noeud.parentElement.closest('svg, .visually-hidden')) continue;
        plage.selectNodeContents(noeud);
        for (const r of plage.getClientRects()) {
            if (r.width < 2) continue;
            const meme = boites.find((b) => Math.abs(b.y + b.h / 2 - (r.top - origine.top + r.height / 2)) < r.height / 2);
            if (meme) {
                const droite = Math.max(meme.x + meme.l, r.right - origine.left);
                meme.x = Math.min(meme.x, r.left - origine.left);
                meme.l = droite - meme.x;
            } else boites.push({ x: r.left - origine.left, y: r.top - origine.top, l: r.width, h: r.height });
        }
    }
    return boites;
}

function generer() {
    const parent = svg.value?.parentElement;
    if (!parent) return [];
    taille = [parent.clientWidth, parent.clientHeight];
    const dessiner = TRACES[props.type];
    const zones = props.parLigne ? lignes(parent) : [];
    if (!zones.length) zones.push({ x: 0, y: 0, l: taille[0], h: taille[1] });
    return zones.flatMap((z) => dessiner(z.l, z.h).flatMap((t) => {
        // Un trait Rough.js peut tenir en plusieurs morceaux : on les dessine l'un après l'autre.
        const morceaux = t.remplir ? [t.d] : t.d.split(/(?=M)/).filter((m) => m.trim());
        return morceaux.map((d, i) => ({ ...t, d, x: z.x, y: z.y, suite: i > 0 }));
    }));
}

async function dessiner(animer) {
    animations.forEach((a) => a.cancel());
    animations = [];
    traits.value = generer();
    await nextTick();
    if (!animer || !svg.value) {
        svg.value?.classList.add('pret');
        return;
    }
    const chemins = [...svg.value.querySelectorAll('path')];
    const vitesse = VITESSE[props.type] ?? 1.8;
    let t = props.delai;
    let debutTrait = t;
    let dureeTrait = 0;
    const sonner = () => {
        if (props.son && dureeTrait > 0) crayon(dureeTrait / 1000, props.type === 'surligne' ? 0.55 : 1, debutTrait / 1000);
    };
    chemins.forEach((chemin, i) => {
        const trait = traits.value[i];
        // Un nouveau trait (pas la suite du précédent) : la main se lève un instant.
        if (!trait.suite && i > 0) {
            sonner();
            t += 70 + Math.random() * 90;
            debutTrait = t;
            dureeTrait = 0;
        }
        let duree;
        if (trait.remplir) {
            const largeur = chemin.getBBox().width;
            duree = Math.max(160, largeur / vitesse);
            animations.push(
                chemin.animate([{ clipPath: 'inset(-20% 100% -20% 0)' }, { clipPath: 'inset(-20% -2% -20% 0)' }], {
                    duration: duree, delay: t, fill: 'both', easing: 'cubic-bezier(.4,.1,.6,1)',
                }),
            );
        } else {
            const longueur = chemin.getTotalLength();
            duree = Math.max(22, longueur / vitesse);
            chemin.style.strokeDasharray = `${longueur + 1} ${longueur + 1}`;
            animations.push(
                chemin.animate([{ strokeDashoffset: longueur + 1 }, { strokeDashoffset: 0 }], {
                    duration: duree, delay: t, fill: 'both', easing: props.type === 'cercle' ? 'cubic-bezier(.35,.05,.45,1)' : 'linear',
                }),
            );
        }
        t += duree;
        dureeTrait = t - debutTrait;
    });
    sonner();
    svg.value.classList.add('pret');
}

function demarrer() {
    dessiner(props.anime && !reduit());
}

let redim = null;
onMounted(() => {
    const parent = svg.value.parentElement;
    if (props.auVisible && 'IntersectionObserver' in window && !reduit()) {
        observateur = new IntersectionObserver(
            (entrees) => {
                if (entrees.some((e) => e.isIntersecting)) {
                    observateur.disconnect();
                    observateur = null;
                    demarrer();
                }
            },
            { threshold: 0.6 },
        );
        observateur.observe(parent);
    } else demarrer();

    // Le parent change de taille (rotation du téléphone) : on retrace, sans rejouer le geste.
    redim = new ResizeObserver(() => {
        if (!taille || observateur) return;
        if (Math.abs(parent.clientWidth - taille[0]) > 4 || Math.abs(parent.clientHeight - taille[1]) > 4) dessiner(false);
    });
    redim.observe(parent);
});

onBeforeUnmount(() => {
    observateur?.disconnect();
    redim?.disconnect();
    animations.forEach((a) => a.cancel());
});
</script>

<template>
    <svg ref="svg" class="trace-main" :class="`trace-${type}`" aria-hidden="true" focusable="false">
        <g v-for="(t, i) in traits" :key="i" :transform="`translate(${t.x} ${t.y})`">
            <path
                v-if="t.remplir"
                :d="t.d"
                :style="{ fill: couleur, opacity: t.opacite }"
            />
            <path
                v-else
                :d="t.d"
                :style="{ stroke: couleur, strokeWidth: t.largeur, opacity: t.opacite }"
            />
        </g>
    </svg>
</template>

<style scoped>
.trace-main {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: visible;
    visibility: hidden;
}
.trace-main.pret {
    visibility: visible;
}
.trace-main path {
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
}
/* Le surligneur passe sous le texte, et l'encre du texte reste lisible à travers. */
.trace-surligne {
    z-index: -1;
    mix-blend-mode: multiply;
}
:root[data-theme='sombre'] .trace-surligne {
    mix-blend-mode: normal;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme='clair']) .trace-surligne {
        mix-blend-mode: normal;
    }
}
</style>
