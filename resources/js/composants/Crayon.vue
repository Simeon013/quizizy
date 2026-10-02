<script setup>
/** Le chrono : un crayon qui s'use. `reste` va de 1 (plein) à 0 (temps écoulé). */
import { computed } from 'vue';

const props = defineProps({
    reste: { type: Number, default: 1 },
    secondes: { type: Number, default: null },
});
const largeur = computed(() => `${Math.max(4, Math.round(props.reste * 100))}%`);
const presse = computed(() => props.reste < 0.25);
</script>

<template>
    <div
        class="crayon"
        :class="{ 'crayon-presse': presse }"
        role="timer"
        :aria-label="secondes !== null ? `${secondes} secondes restantes` : 'Temps restant'"
    >
        <span class="pointe" aria-hidden="true"></span>
        <span class="bois" aria-hidden="true"><span class="corps" :style="{ width: largeur }"></span></span>
        <span v-if="secondes !== null" class="compte chiffres" aria-hidden="true">{{ secondes }}</span>
    </div>
</template>

<style scoped>
.crayon {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}
.pointe {
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-right: 20px solid #e7c69a;
    position: relative;
    flex: none;
}
.pointe::before {
    content: '';
    position: absolute;
    left: 0;
    top: -4px;
    border-top: 4px solid transparent;
    border-bottom: 4px solid transparent;
    border-right: 8px solid #3b3b3b;
}
.bois {
    flex: 1;
    min-width: 0;
    height: 20px;
}
.corps {
    display: block;
    height: 20px;
    background: repeating-linear-gradient(#f2b705 0 6px, #dda200 6px 7px);
    border-right: 10px solid #f59ab5;
    box-shadow: inset -12px 0 0 #b8b8c0;
    border-radius: 0 6px 6px 0;
    transition: width 0.25s linear;
}
.compte {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 24px;
    min-width: 2ch;
    text-align: right;
}
.crayon-presse .compte {
    color: var(--rouge);
}
.crayon-presse .corps {
    animation: trembler 0.35s infinite;
}
@keyframes trembler {
    50% {
        transform: translateX(-1px);
    }
}
</style>
