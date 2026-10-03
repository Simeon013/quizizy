<script setup>
/** Couper ou remettre les bruits du cahier (crayon, page, tampon). Réglage gardé : `eureka:son`. */
import { onMounted, ref } from 'vue';
import { reglerSon, sonActif, tapote } from '../lib/sons';

const actif = ref(true);
onMounted(() => (actif.value = sonActif()));

function basculer() {
    actif.value = !actif.value;
    reglerSon(actif.value);
    tapote();
}
</script>

<template>
    <button
        type="button"
        class="bouton-son"
        :aria-pressed="actif"
        :aria-label="actif ? 'Couper les bruits du cahier' : 'Remettre les bruits du cahier'"
        :title="actif ? 'Couper le son' : 'Remettre le son'"
        @click="basculer"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 9.5h3.5L12 5.5v13l-4.5-4H4z" />
            <template v-if="actif">
                <path d="M15.5 9c1 1 1 5 0 6" />
                <path d="M18.3 6.5c2.4 2.6 2.4 8.4 0 11" />
            </template>
            <path v-else d="M16 9.5l5 5M21 9.5l-5 5" />
        </svg>
    </button>
</template>

<style scoped>
.bouton-son {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border: 2px solid var(--encre);
    border-radius: 48% 52% 46% 50%;
    background: var(--carte);
    color: var(--encre);
    cursor: pointer;
}
.bouton-son svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
</style>
