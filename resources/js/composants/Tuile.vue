<script setup>
/**
 * Une réponse en direct : symbole + couleur + texte. Au moment de la
 * correction, la bonne est surlignée et entourée, les autres s'effacent ;
 * sur le grand écran, une barre hachurée montre combien l'ont choisie.
 */
import Symbole from './Symbole.vue';

defineProps({
    symbole: { type: String, required: true },
    texte: { type: String, required: true },
    etat: { type: String, default: 'neutre' }, // neutre | juste | fausse
    choisie: { type: Boolean, default: false },
    compte: { type: Number, default: null },
    sur: { type: Number, default: 0 },
    bouton: { type: Boolean, default: false },
    desactive: { type: Boolean, default: false },
});
defineEmits(['choisir']);

const COULEURS = { triangle: '#8CC8FF', rond: '#FFB4A2', carre: '#B9F5C9', etoile: '#FFE27A' };
</script>

<template>
    <component
        :is="bouton ? 'button' : 'div'"
        :type="bouton ? 'button' : undefined"
        :disabled="bouton ? desactive : undefined"
        class="tuile"
        :class="[`tuile-${etat}`, { 'tuile-choisie': choisie, 'tuile-bouton': bouton }]"
        :style="{ '--fond': COULEURS[symbole] }"
        @click="bouton && $emit('choisir')"
    >
        <Symbole :nom="symbole" class="tuile-symbole" />
        <span class="tuile-texte">{{ texte }}</span>
        <span v-if="choisie" class="tuile-moi">{{ etat === 'neutre' ? 'ton choix' : etat === 'juste' ? 'ton choix, juste !' : 'ton choix' }}</span>
        <span v-if="etat === 'juste'" class="visually-hidden"> (bonne réponse)</span>
        <span v-if="compte !== null" class="tuile-compte">
            <span class="tuile-barre" :style="{ width: `${sur ? Math.round((compte / sur) * 100) : 0}%` }"></span>
            <span class="tuile-nombre chiffres">{{ compte }}</span>
        </span>
        <svg v-if="etat === 'juste'" class="tuile-cercle" viewBox="0 0 200 60" preserveAspectRatio="none" aria-hidden="true">
            <path class="trace" style="--longueur: 520" d="M34 6 C-12 12 0 58 64 55 C150 59 210 46 194 16 C182 -2 92 0 30 12" />
        </svg>
    </component>
</template>

<style scoped>
.tuile {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: center;
    gap: 6px 14px;
    padding: 14px 16px;
    min-height: 72px;
    text-align: left;
    font: 700 1em/1.25 var(--font-sans);
    color: var(--encre-fixe);
    background: var(--fond);
    border: 2.5px solid var(--encre);
    border-radius: 14px 18px 12px 16px / 16px 12px 18px 14px;
    box-shadow: 4px 4px 0 var(--ombre);
    transition:
        opacity 0.35s,
        transform 0.2s,
        box-shadow 0.2s;
}
.tuile-bouton {
    cursor: pointer;
    min-height: 88px;
}
.tuile-bouton:not([disabled]):active {
    transform: translate(3px, 3px);
    box-shadow: 1px 1px 0 var(--ombre);
}
.tuile-symbole {
    width: 2.1em;
    height: 2.1em;
}
.tuile-texte {
    overflow-wrap: anywhere;
}
.tuile-moi {
    grid-column: 1 / -1;
    font-family: var(--font-main);
    font-size: 1.15em;
    line-height: 1;
}
.tuile-compte {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 10px;
}
.tuile-barre {
    height: 0.9em;
    min-width: 4px;
    border: 2px solid var(--encre-fixe);
    border-radius: 4px;
    background: repeating-linear-gradient(-45deg, var(--encre-fixe) 0 2px, transparent 2px 7px);
    transition: width 0.8s cubic-bezier(0.3, 1.3, 0.5, 1);
}
.tuile-nombre {
    font-family: var(--font-feutre);
    font-size: 1.2em;
}
.tuile-fausse {
    opacity: 0.4;
}
.tuile-fausse.tuile-choisie {
    opacity: 0.8;
}
.tuile-juste {
    background: #f3ff3d;
    transform: scale(1.03);
}
.tuile-choisie.tuile-neutre {
    outline: 4px dashed var(--encre);
    outline-offset: 3px;
}
.tuile-cercle {
    position: absolute;
    inset: -10px -12px;
    width: calc(100% + 24px);
    height: calc(100% + 20px);
    pointer-events: none;
    overflow: visible;
}
.tuile-cercle path {
    fill: none;
    stroke: #c92a25;
    stroke-width: 3.5;
    stroke-linecap: round;
}
.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}
</style>
