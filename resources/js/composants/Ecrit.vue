<script setup>
/**
 * Un texte qui s'écrit sous les yeux, lettre après lettre, au rythme d'une
 * main : un peu d'hésitation entre les mots, une pause après la ponctuation,
 * et le crayon qu'on entend. La mise en page ne bouge pas (chaque lettre est
 * déjà à sa place, encore invisible). Lecteurs d'écran : le texte entier, d'un coup.
 * Avec « mouvement réduit », le texte est là tout de suite.
 */
import { computed, onMounted } from 'vue';
import { crayon } from '../lib/sons';

const props = defineProps({
    texte: { type: String, required: true },
    balise: { type: String, default: 'p' },
    delai: { type: Number, default: 0 }, // ms
    son: { type: Boolean, default: true },
    duree: { type: Number, default: 1800 }, // au plus, quelle que soit la longueur
});

const lettres = computed(() => {
    const brut = [...props.texte];
    // Une lettre toutes les ~38 ms, plus vite si le texte est long.
    const pas = Math.min(38, props.duree / Math.max(1, brut.length));
    let t = props.delai;
    return brut.map((c, i) => {
        const precedente = brut[i - 1];
        if (precedente === ' ') t += pas * (0.6 + Math.random() * 1.4);
        else if (/[.,;:!?…]/.test(precedente ?? '')) t += pas * 3;
        t += pas * (0.6 + Math.random() * 0.8);
        return { c, t: Math.round(t), y: (Math.random() - 0.5) * 1.2 };
    });
});

onMounted(() => {
    if (!props.son || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
    const fin = lettres.value.at(-1)?.t ?? 0;
    if (fin > props.delai) crayon((fin - props.delai) / 1000, 0.45, props.delai / 1000);
});
</script>

<template>
    <component :is="balise" class="ecrit">
        <span class="visually-hidden">{{ texte }}</span>
        <span aria-hidden="true"><span
            v-for="(l, i) in lettres"
            :key="i"
            class="lettre"
            :style="{ animationDelay: `${l.t}ms`, '--y': `${l.y.toFixed(2)}px` }"
        >{{ l.c }}</span></span>
    </component>
</template>

<style scoped>
.lettre {
    display: inline;
    opacity: 0;
    animation: ecrire 0.14s ease-out forwards;
}
@keyframes ecrire {
    from {
        opacity: 0;
        filter: blur(1.2px);
    }
    to {
        opacity: 1;
        filter: none;
    }
}
.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}
@media (prefers-reduced-motion: reduce) {
    .lettre {
        animation: none;
        opacity: 1;
    }
}
</style>
