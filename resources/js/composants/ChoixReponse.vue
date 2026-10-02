<script setup>
/**
 * Un choix de réponse, corrigé au stylo : la bonne réponse est surlignée et
 * entourée en rouge, la réponse fausse choisie est barrée, la case cochée.
 * L'état ne repose jamais sur la couleur seule.
 */
defineProps({
    texte: { type: String, required: true },
    touche: { type: String, default: '' },
    etat: { type: String, default: 'neutre' }, // neutre | juste | fausse
    choisi: { type: Boolean, default: false },
    desactive: { type: Boolean, default: false },
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
            <svg v-if="choisi" viewBox="0 0 34 34">
                <path class="trace" style="--longueur: 60" d="M6 18 L14 26 L30 4" />
            </svg>
        </span>
        <span class="texte">{{ texte }}</span>
        <span v-if="touche && etat === 'neutre'" class="touche" aria-hidden="true">{{ touche }}</span>
        <span v-if="etat === 'juste'" class="visually-hidden"> (bonne réponse)</span>
        <span v-else-if="etat === 'fausse' && choisi" class="visually-hidden"> (ta réponse, fausse)</span>
        <svg v-if="etat === 'juste'" class="marque" viewBox="0 0 200 60" preserveAspectRatio="none" aria-hidden="true">
            <path class="trace" style="--longueur: 520" d="M34 6 C-12 12 0 58 64 55 C150 59 210 46 194 16 C182 -2 92 0 30 12" />
        </svg>
        <svg v-else-if="etat === 'fausse' && choisi" class="marque" viewBox="0 0 200 60" preserveAspectRatio="none" aria-hidden="true">
            <path class="trace" style="--longueur: 200" d="M8 36 Q100 20 192 30" />
        </svg>
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
.choix:nth-child(2) {
    transform: rotate(0.5deg);
}
.choix:nth-child(3) {
    transform: rotate(-0.4deg);
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
.case svg {
    position: absolute;
    inset: -9px -7px -4px -4px;
    width: 36px;
    height: 36px;
    overflow: visible;
}
.case path {
    fill: none;
    stroke: var(--rouge);
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.choix-juste .case path {
    stroke: var(--vert);
}
.texte {
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
.marque {
    position: absolute;
    inset: -8px -10px;
    width: calc(100% + 20px);
    height: calc(100% + 16px);
    pointer-events: none;
    overflow: visible;
}
.marque path {
    fill: none;
    stroke: var(--rouge);
    stroke-width: 3;
    stroke-linecap: round;
}
.choix-juste {
    background:
        linear-gradient(transparent 14%, var(--surligne) 14%, var(--surligne) 88%, transparent 88%),
        var(--carte);
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
