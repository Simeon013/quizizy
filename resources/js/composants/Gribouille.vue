<script setup>
/**
 * Gribouille, la mascotte : un point d'interrogation tracé au feutre. Quand la
 * pose passe d'un « ? » à un « ! » (eurêka, bravo), le corps se redresse en
 * animation. Couleurs tirées des jetons du thème : lisible en clair et en sombre.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { POSES, corps, yeux } from '../lib/gribouille';

const props = defineProps({
    pose: { type: String, default: 'curieux' },
    vivant: { type: Boolean, default: true },
    titre: { type: String, default: '' },
});

const fiche = computed(() => POSES[props.pose] ?? POSES.curieux);
const t = ref(fiche.value.forme === 'e' ? 1 : 0);
const yeuxVisibles = ref(fiche.value.yeux);
let animation = 0;

const reduit = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

watch(fiche, (nouvelle) => {
    const cible = nouvelle.forme === 'e' ? 1 : 0;
    cancelAnimationFrame(animation);
    if (reduit || cible === t.value) {
        t.value = cible;
        yeuxVisibles.value = nouvelle.yeux;
        return;
    }
    const depart = t.value;
    const debut = performance.now();
    yeuxVisibles.value = 'ouverts';
    const pas = (maintenant) => {
        const k = Math.min(1, (maintenant - debut) / 750);
        const e = k < 0.5 ? 4 * k ** 3 : 1 - (-2 * k + 2) ** 3 / 2;
        // Petit dépassement à la fin : il se redresse d'un coup, puis se pose.
        const rebond = k > 0.85 && k < 1 ? Math.sin(((k - 0.85) / 0.15) * Math.PI) * 0.06 * (cible ? 1 : -1) : 0;
        t.value = depart + (cible - depart) * e + rebond;
        if (k > 0.7) yeuxVisibles.value = nouvelle.yeux;
        if (k < 1) animation = requestAnimationFrame(pas);
    };
    animation = requestAnimationFrame(pas);
});
onBeforeUnmount(() => cancelAnimationFrame(animation));

const chemin = computed(() => corps(t.value));
const positionsYeux = computed(() => yeux(Math.max(0, Math.min(1, t.value))));
const regard = computed(() => fiche.value.regard ?? [1, 1]);
const extras = computed(() => new Set(fiche.value.extra ?? []));
</script>

<template>
    <svg
        viewBox="0 0 120 130"
        :role="titre ? 'img' : undefined"
        :aria-label="titre || undefined"
        :aria-hidden="titre ? undefined : 'true'"
        class="gribouille"
        :class="{ 'gribouille-vivant': vivant }"
    >
        <path v-if="extras.has('surligne')" d="M30 112 L92 106 L94 120 L32 124 Z" fill="var(--surligne)" />
        <g :transform="`rotate(${fiche.penche ?? 0} 60 70)`">
            <path :d="chemin" class="g-trait" stroke-width="13" />
            <circle cx="61" cy="110" r="9" fill="var(--encre)" />
            <g class="g-yeux">
                <!-- Chaque œil a toujours son blanc : un trait d'encre posé sur le corps d'encre ne se voyait pas. -->
                <template v-for="(p, i) in positionsYeux" :key="i">
                    <circle :cx="p[0]" :cy="p[1]" :r="yeuxVisibles === 'grands' ? 7.5 : 6.6" fill="var(--carte)" stroke="var(--encre)" stroke-width="2.6" />
                    <path
                        v-if="yeuxVisibles === 'rieurs'"
                        :d="`M${p[0] - 3.6} ${p[1] + 1.6} Q${p[0]} ${p[1] - 3.6} ${p[0] + 3.6} ${p[1] + 1.6}`"
                        class="g-trait"
                        stroke-width="2.4"
                    />
                    <path
                        v-else-if="yeuxVisibles === 'fermes'"
                        :d="`M${p[0] - 3.8} ${p[1]} Q${p[0]} ${p[1] + 3} ${p[0] + 3.8} ${p[1]}`"
                        class="g-trait"
                        stroke-width="2.2"
                    />
                    <circle
                        v-else
                        :cx="p[0] + regard[0]"
                        :cy="p[1] + regard[1]"
                        :r="yeuxVisibles === 'grands' ? 2.4 : 2.7"
                        fill="var(--encre)"
                    />
                </template>
                <path
                    v-if="yeuxVisibles === 'inquiets'"
                    :d="`M${positionsYeux[0][0] - 6} ${positionsYeux[0][1] - 11} L${positionsYeux[0][0] + 4} ${positionsYeux[0][1] - 8} M${positionsYeux[1][0] + 6} ${positionsYeux[1][1] - 11} L${positionsYeux[1][0] - 4} ${positionsYeux[1][1] - 8}`"
                    class="g-trait"
                    stroke-width="2.6"
                />
            </g>
            <!-- Sourire tracé en blanc sur le trait du « ! ». -->
            <path v-if="fiche.bouche === 'sourire' && t > 0.8" d="M56 40 Q61 46 66 40" fill="none" stroke="var(--carte)" stroke-width="2.6" stroke-linecap="round" />
        </g>
        <path v-if="extras.has('eclat')" d="M96 18 l10 -8 M100 32 h12 M22 18 l-10 -8 M18 32 h-12" stroke="var(--rouge)" stroke-width="3" stroke-linecap="round" fill="none" />
        <g v-if="extras.has('crayon')">
            <g transform="rotate(-35 98 70)">
                <rect x="90" y="40" width="10" height="44" rx="2" fill="#F2B705" stroke="var(--encre)" stroke-width="2" />
                <path d="M90 84 L95 96 L100 84 Z" fill="#E7C69A" stroke="var(--encre)" stroke-width="2" />
            </g>
            <path d="M18 40 q6 -6 12 0" stroke="var(--graphite)" stroke-width="2.5" fill="none" stroke-linecap="round" />
        </g>
        <g v-if="extras.has('vert')" stroke="var(--vert)" fill="none" stroke-linecap="round">
            <path d="M90 96 q6 8 16 -6" stroke-width="3.5" />
            <path d="M14 64 q4 -10 10 -2" stroke-width="2.5" />
        </g>
        <g v-if="extras.has('confettis')">
            <rect x="14" y="20" width="6" height="10" rx="2" fill="#FF9AD5" transform="rotate(-20 17 25)" />
            <rect x="98" y="16" width="6" height="10" rx="2" fill="#8CFFB9" transform="rotate(25 101 21)" />
            <circle cx="104" cy="54" r="4" fill="#F2B705" />
            <circle cx="16" cy="58" r="4" fill="#8CC8FF" />
            <rect x="24" y="96" width="6" height="10" rx="2" fill="#F2B705" transform="rotate(40 27 101)" />
        </g>
        <g v-if="extras.has('dodo')" fill="var(--graphite)" font-family="Caveat, cursive" font-weight="700">
            <text x="86" y="26" font-size="20">z</text>
            <text x="98" y="14" font-size="15">z</text>
        </g>
        <path v-if="extras.has('goutte')" d="M96 30 q5 8 0 11 q-5 -3 0 -11 z" fill="#8CC8FF" stroke="var(--encre)" stroke-width="1.5" />
    </svg>
</template>

<style scoped>
.g-trait {
    stroke: var(--encre);
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.gribouille-vivant {
    animation: flotter 3.6s ease-in-out infinite;
}
.gribouille-vivant .g-yeux {
    transform-box: fill-box;
    transform-origin: center;
    animation: cligner 4.6s infinite;
}
@keyframes cligner {
    0%,
    93%,
    100% {
        transform: scaleY(1);
    }
    96% {
        transform: scaleY(0.12);
    }
}
@media (prefers-reduced-motion: reduce) {
    .gribouille-vivant,
    .gribouille-vivant .g-yeux {
        animation: none;
    }
}
</style>
