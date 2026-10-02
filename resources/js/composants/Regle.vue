<script setup>
/** La progression : une règle graduée, surlignée jusqu'à la position. */
import { computed } from 'vue';

const props = defineProps({ valeur: { type: Number, required: true }, max: { type: Number, required: true }, libelle: String });
const pourcent = computed(() => `${props.max ? Math.min(100, (props.valeur / props.max) * 100) : 0}%`);
</script>

<template>
    <div class="regle" role="progressbar" :aria-valuenow="valeur" aria-valuemin="0" :aria-valuemax="max" :aria-label="libelle">
        <span class="rempli" :style="{ width: pourcent }"></span>
    </div>
</template>

<style scoped>
.regle {
    position: relative;
    height: 22px;
    border: 2px solid var(--encre);
    border-radius: 4px;
    background: #fdf6d8;
    overflow: hidden;
}
.regle::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
        repeating-linear-gradient(90deg, #1e2b5c 0 1.5px, transparent 1.5px 10px) top / 100% 6px no-repeat,
        repeating-linear-gradient(90deg, #1e2b5c 0 1.5px, transparent 1.5px 50px) top / 100% 11px no-repeat;
    opacity: 0.75;
}
.rempli {
    position: absolute;
    inset-block: 0;
    left: 0;
    background: rgb(243 255 61 / 0.85);
    transition: width 0.6s cubic-bezier(0.3, 1.3, 0.5, 1);
}
</style>
