<script setup>
import { onMounted, ref } from 'vue';
import { appliquerTheme, themeEffectif } from '../lib/theme';

const theme = ref('clair');
onMounted(() => (theme.value = themeEffectif()));

function basculer() {
    theme.value = theme.value === 'sombre' ? 'clair' : 'sombre';
    appliquerTheme(theme.value);
}
</script>

<template>
    <button
        type="button"
        class="bouton-theme"
        :aria-label="theme === 'sombre' ? 'Passer au cahier clair' : 'Passer au cahier de nuit'"
        :title="theme === 'sombre' ? 'Cahier clair' : 'Cahier de nuit'"
        @click="basculer"
    >
        <svg v-if="theme === 'sombre'" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="4.5" />
            <path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8" />
        </svg>
        <svg v-else viewBox="0 0 24 24" aria-hidden="true">
            <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z" />
        </svg>
    </button>
</template>

<style scoped>
.bouton-theme {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border: 2px solid var(--encre);
    border-radius: 50% 46% 52% 48%;
    background: var(--carte);
    color: var(--encre);
    cursor: pointer;
}
.bouton-theme svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
</style>
