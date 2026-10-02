<script setup>
/** Les symboles des réponses en direct, tracés au feutre. Couleur, forme et texte : jamais la couleur seule. */
defineProps({ nom: { type: String, required: true } });

const TRACES = {
    triangle: 'M20 5 L36 34 L4 35 Z',
    rond: 'M20 4 C34 4 37 18 35 25 C32 35 14 38 7 30 C1 22 4 6 22 5',
    carre: 'M5 6 L35 5 L34 35 L6 34 Z',
    etoile: 'M20 3 L25 15 L38 15 L27 23 L31 36 L20 28 L9 36 L13 23 L2 15 L15 15 Z',
};
const NOMS = { triangle: 'triangle', rond: 'rond', carre: 'carré', etoile: 'étoile' };
</script>

<template>
    <svg viewBox="0 0 40 40" class="symbole" role="img" :aria-label="NOMS[nom]">
        <path :d="TRACES[nom]" />
    </svg>
</template>

<style scoped>
.symbole path {
    fill: none;
    stroke: currentColor;
    stroke-width: 3.4;
    stroke-linecap: round;
    stroke-linejoin: round;
}
</style>
