<script setup>
/** Une gommette (badge). Non obtenue : un emplacement en pointillé. */
defineProps({
    symbole: { type: String, required: true },
    couleur: { type: String, default: '#F2B705' },
    obtenue: { type: Boolean, default: true },
    taille: { type: Number, default: 64 },
});

const DESSINS = {
    etoile: 'M12 2.5l2.9 6.4 7 .7-5.3 4.7 1.5 6.9L12 17.6l-6.1 3.6 1.5-6.9L2.1 9.6l7-.7z',
    coche: 'M5 12.5l4.5 4.5L19 7',
    eclair: 'M13.5 2L5 13.5h6L9.5 22 19 9.5h-6z',
    plume: 'M19 4c-6 0-11 5-12 12l-2 4M19 4c0 6-4 10-10 11M11 10l3 1',
    boussole: 'M12 3a9 9 0 1 0 .01 0zM15.5 8.5l-2 5-5 2 2-5z',
    boucle: 'M4 12a8 8 0 0 1 14-5.3M20 12a8 8 0 0 1-14 5.3M18 3v4h-4M6 21v-4h4',
    calendrier: 'M4 6h16v14H4zM4 10h16M8 3v5M16 3v5M8 14h3',
};
</script>

<template>
    <span
        class="gommette"
        :class="{ 'gommette-vide': !obtenue }"
        :style="{ '--fond': couleur, width: `${taille}px`, height: `${taille}px` }"
    >
        <span v-if="symbole === 'cinq'" class="chiffre" aria-hidden="true">5</span>
        <svg v-else viewBox="0 0 24 24" aria-hidden="true">
            <path :d="DESSINS[symbole]" :class="{ plein: symbole === 'etoile' || symbole === 'eclair' }" />
        </svg>
    </span>
</template>

<style scoped>
.gommette {
    display: inline-grid;
    place-items: center;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, color-mix(in srgb, var(--fond) 55%, white), var(--fond));
    border: 2px solid var(--encre-fixe);
    box-shadow: 2px 3px 0 rgb(0 0 0 / 0.18);
    position: relative;
    flex: none;
    color: var(--encre-fixe);
}
.gommette::after {
    content: '';
    position: absolute;
    right: 4%;
    bottom: 10%;
    width: 24%;
    height: 24%;
    background: linear-gradient(135deg, transparent 50%, rgb(255 255 255 / 0.85) 50%);
    border-radius: 0 0 50% 0;
}
.gommette svg {
    width: 52%;
    height: 52%;
}
.gommette path {
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.gommette path.plein {
    fill: #fff;
}
.chiffre {
    font-family: var(--font-feutre);
    font-size: 1.6em;
    line-height: 1;
}
.gommette-vide {
    background: transparent;
    border: 2px dashed var(--graphite);
    box-shadow: none;
    color: var(--graphite);
    opacity: 0.6;
}
.gommette-vide::after {
    display: none;
}
</style>
