<script setup>
/**
 * Un choix de réponse, corrigé au stylo : la bonne réponse est surlignée et
 * entourée en rouge, la réponse fausse choisie est raturée (sans cacher ce
 * qu'on avait écrit), la case cochée à la main. Tracés tirés au hasard : pas
 * deux corrections pareilles. L'état ne repose jamais sur la couleur seule.
 */
import Trace from './Trace.vue';

defineProps({
    texte: { type: String, required: true },
    touche: { type: String, default: '' },
    etat: { type: String, default: 'neutre' }, // neutre | juste | fausse
    choisi: { type: Boolean, default: false },
    desactive: { type: Boolean, default: false },
    son: { type: Boolean, default: true },
    auVisible: { type: Boolean, default: false }, // corrigé : le stylo passe quand on arrive dessus
});
defineEmits(['choisir']);
</script>

<template>
    <button
        type="button"
        class="choix"
        :class="[`choix-${etat}`, { 'choix-choisi': choisi }]"
        :disabled="desactive"
        :aria-pressed="choisi"
        @click="$emit('choisir')"
    >
        <span class="case" aria-hidden="true">
            <Trace v-if="choisi" type="coche" couleur="var(--encre)" :son="son" :au-visible="auVisible" />
        </span>
        <span class="texte">
            {{ texte }}
            <Trace v-if="etat === 'fausse' && choisi" type="rature" par-ligne :son="son" :au-visible="auVisible" :delai="150" />
            <Trace v-if="etat === 'juste'" type="surligne" couleur="var(--surligne)" par-ligne :son="son" :au-visible="auVisible" :delai="choisi ? 200 : 650" />
        </span>
        <span v-if="touche && etat === 'neutre'" class="touche" aria-hidden="true">{{ touche }}</span>
        <span v-if="etat === 'juste'" class="visually-hidden"> (bonne réponse)</span>
        <span v-else-if="etat === 'fausse' && choisi" class="visually-hidden"> (ta réponse, fausse)</span>
        <Trace v-if="etat === 'juste'" type="cercle" :son="son" :au-visible="auVisible" :delai="choisi ? 550 : 1000" />
    </button>
</template>

<style scoped>
.choix {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    min-height: 56px;
    padding: 10px 14px;
    text-align: left;
    font: 700 17px/1.3 var(--font-sans);
    color: var(--encre);
    background: var(--carte);
    border: 2px solid var(--encre);
    border-radius: 12px 16px 10px 14px / 14px 10px 16px 12px;
    box-shadow: 3px 3px 0 var(--ombre);
    cursor: pointer;
    transition:
        transform 0.15s,
        box-shadow 0.15s,
        opacity 0.3s;
}
.choix:not([disabled]):hover {
    box-shadow: 5px 5px 0 var(--ombre);
    transform: translate(-1px, -1px);
}
.choix[disabled] {
    cursor: default;
}
.case {
    position: relative;
    width: 26px;
    height: 26px;
    flex: none;
    border: 2px solid var(--encre);
    border-radius: 4px;
}
.texte {
    position: relative;
    isolation: isolate;
    flex: 1;
    min-width: 0;
    overflow-wrap: anywhere;
}
.touche {
    font: 400 13px var(--font-sans);
    color: var(--graphite);
    border: 1.5px solid var(--quadrille);
    border-radius: 5px;
    padding: 1px 7px;
}
@media (hover: none) {
    .touche {
        display: none;
    }
}
.choix-juste {
    opacity: 1;
}
.choix-fausse {
    opacity: 0.55;
}
.choix-fausse.choix-choisi {
    opacity: 1;
}
.choix-fausse.choix-choisi .texte {
    color: var(--graphite);
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
