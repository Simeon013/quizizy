<script setup>
/** Pendant que l'IA rédige (souvent plusieurs dizaines de secondes) : Gribouille écrit au crayon. */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Gribouille from './Gribouille.vue';

const phrases = [
    'Gribouille lit ton texte…',
    'Gribouille cherche les points importants…',
    'Gribouille écrit les questions au crayon…',
    'Gribouille invente des leurres crédibles…',
    'Gribouille vérifie ses explications…',
];
const i = ref(0);
let minuteur;
onMounted(() => (minuteur = setInterval(() => (i.value = (i.value + 1) % phrases.length), 4500)));
onBeforeUnmount(() => clearInterval(minuteur));
</script>

<template>
    <div class="attente" role="status" aria-live="polite">
        <div class="feuille carte">
            <Gribouille pose="reflechit" class="gri" />
            <p class="note-main phrase">{{ phrases[i] }}</p>
            <div class="ligne" aria-hidden="true"><span class="mine"></span></div>
            <p class="discret">Cela prend souvent entre 20 secondes et une minute. Ne ferme pas la page.</p>
        </div>
    </div>
</template>

<style scoped>
.attente {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: grid;
    place-items: center;
    padding: 16px;
    background: color-mix(in srgb, var(--papier) 82%, transparent);
    backdrop-filter: blur(3px);
}
.carte {
    display: grid;
    justify-items: center;
    gap: 12px;
    width: min(420px, 100%);
    padding: 28px 24px;
    text-align: center;
}
.gri {
    width: 120px;
    height: 130px;
}
.phrase {
    font-size: 26px;
    min-height: 2.3em;
}
.ligne {
    position: relative;
    width: 100%;
    height: 3px;
    background: var(--quadrille);
    overflow: hidden;
}
.mine {
    position: absolute;
    inset-block: 0;
    left: 0;
    width: 40%;
    background: var(--graphite);
    animation: ecrire 1.8s ease-in-out infinite;
}
@keyframes ecrire {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(260%);
    }
}
</style>
