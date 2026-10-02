<script setup>
/** La copie rendue : la note au stylo rouge, le tampon, le mot vert, le corrigé. */
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import ChoixReponse from '../composants/ChoixReponse.vue';
import Gommette from '../composants/Gommette.vue';
import Gribouille from '../composants/Gribouille.vue';
import Tampon from '../composants/Tampon.vue';

const props = defineProps({
    partie: { type: Object, required: true },
    questions: { type: Array, required: true },
    gommettes: { type: Array, default: () => [] },
    invite: { type: Boolean, default: false },
});

const ratio = computed(() => props.partie.bonnes / Math.max(1, props.partie.total));
const mot = computed(() => {
    const r = ratio.value;
    let texte;
    if (r === 1) texte = 'Sans faute ! Rien à redire.';
    else if (r >= 0.8) texte = 'Très bien !';
    else if (r >= 0.5) texte = 'Bien, continue comme ça.';
    else texte = 'Chaque erreur est une leçon : relis le corrigé ci-dessous.';
    const p = props.partie.progres;
    if (p > 0) texte += ` +${p} depuis la dernière fois.`;
    return texte;
});
const pose = computed(() => (ratio.value >= 0.8 ? 'bravo' : ratio.value >= 0.5 ? 'eureka' : 'oups'));
const erreurs = computed(() => props.questions.filter((q) => !q.juste).length);

const rejouer = () => router.post(`/quiz/${props.partie.quiz}/parties`);

onMounted(async () => {
    if (ratio.value < 0.8 || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
    const { default: confettis } = await import('canvas-confetti');
    confettis({
        particleCount: 90,
        spread: 70,
        origin: { y: 0.35 },
        colors: ['#F3FF3D', '#FF9AD5', '#8CC8FF', '#8CFFB9', '#F2B705'],
        disableForReducedMotion: true,
    });
});

const etat = (c) => (c.juste ? 'juste' : 'fausse');
</script>

<template>
    <Head title="Copie rendue" />
    <section class="page copie-page">
        <div class="copie-haut">
            <article class="feuille copie">
                <Tampon class="copie-tampon" />
                <p class="surtitre">Copie rendue · {{ partie.titre }}</p>
                <p class="copie-note chiffres">
                    {{ partie.bonnes }}<small>/{{ partie.total }}</small>
                </p>
                <p class="note-verte copie-mot">{{ mot }}</p>
                <dl class="copie-stats">
                    <div><dt>Points</dt><dd class="chiffres">{{ partie.points.toLocaleString('fr-FR') }}</dd></div>
                    <div><dt>Série la plus longue</dt><dd class="chiffres">{{ partie.serieMax }}</dd></div>
                    <div><dt>Note sur 20</dt><dd class="chiffres">{{ partie.surVingt.toLocaleString('fr-FR') }}</dd></div>
                </dl>
                <div v-if="gommettes.length" class="copie-gommettes">
                    <p class="note-main">Nouvelle{{ gommettes.length > 1 ? 's' : '' }} gommette{{ gommettes.length > 1 ? 's' : '' }} !</p>
                    <ul>
                        <li v-for="(g, i) in gommettes" :key="g.cle" class="gommette-gagnee" :style="{ animationDelay: `${0.9 + i * 0.2}s` }">
                            <Gommette :symbole="g.symbole" :couleur="g.couleur" :taille="56" />
                            <span><strong>{{ g.nom }}</strong><br /><span class="discret">{{ g.description }}</span></span>
                        </li>
                    </ul>
                </div>
            </article>

            <aside class="copie-cote">
                <Gribouille :pose="pose" class="copie-gribouille" />
                <div v-if="invite" class="postit invitation">
                    Crée ton cahier pour garder cette copie, retrouver tes erreurs et gagner des gommettes.
                    <Link href="/inscription" class="btn btn-plein invitation-btn">Créer mon cahier</Link>
                </div>
                <div class="copie-actions">
                    <button v-if="partie.quiz" type="button" class="btn btn-plein" @click="rejouer">Refaire ce quiz</button>
                    <Link v-if="partie.matiere" :href="`/matieres/${partie.matiere.slug}`" class="btn">Autres quiz de {{ partie.matiere.nom }}</Link>
                    <Link v-if="!invite" href="/cahier" class="btn btn-discret">Mon cahier</Link>
                    <Link v-else href="/matieres" class="btn btn-discret">Toutes les matières</Link>
                </div>
            </aside>
        </div>

        <section class="corrige" aria-labelledby="titre-corrige">
            <h2 id="titre-corrige" class="t-section">Le corrigé</h2>
            <p class="discret">
                {{ erreurs === 0 ? 'Tout est juste. Relis quand même les explications : on retient mieux.' : `${erreurs} erreur${erreurs > 1 ? 's' : ''}${invite ? '' : ', à retrouver dans « À revoir »'}.` }}
            </p>
            <ol class="corrige-liste">
                <li v-for="(q, i) in questions" :key="i" v-revele class="feuille corrige-item">
                    <div class="corrige-tete">
                        <span class="corrige-num chiffres" :style="{ background: q.onglet }">{{ i + 1 }}</span>
                        <h3 class="t-carte">{{ q.enonce }}</h3>
                        <span class="corrige-verdict" :class="q.juste ? 'note-verte' : 'note-rouge'">
                            {{ q.juste ? (q.indice ? 'Juste, avec l\'indice' : 'Juste') : q.tempsEcoule ? 'Temps écoulé' : 'Faux' }}
                        </span>
                    </div>
                    <div class="corrige-choix">
                        <ChoixReponse v-for="(c, j) in q.choix" :key="j" :texte="c.texte" :etat="etat(c)" :choisi="c.choisi" desactive />
                    </div>
                    <p class="note-verte">{{ q.explication }}</p>
                </li>
            </ol>
        </section>
    </section>
</template>

<style scoped>
.copie-page {
    display: grid;
    gap: 48px;
    padding-block: 30px 0;
}
.copie-haut {
    display: grid;
    gap: 28px;
    align-items: start;
}
@media (min-width: 900px) {
    .copie-haut {
        grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
    }
}
.copie {
    position: relative;
    display: grid;
    gap: 12px;
    padding: clamp(20px, 4vw, 34px);
    transform: rotate(-1deg);
}
.copie > .surtitre {
    padding-right: clamp(84px, 15vw, 116px);
}
.copie-tampon {
    position: absolute;
    top: 16px;
    right: 16px;
    width: clamp(78px, 14vw, 110px);
}
.copie-note {
    font-family: var(--font-feutre);
    color: var(--rouge);
    font-size: clamp(72px, 13vw, 120px);
    line-height: 0.9;
    animation: noter 0.6s 0.1s ease-out both;
}
.copie-note small {
    font-size: 0.45em;
}
@keyframes noter {
    from {
        opacity: 0;
        transform: rotate(-6deg) scale(0.8);
    }
}
.copie-mot {
    font-size: 28px;
}
.copie-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 28px;
    margin: 6px 0 0;
}
.copie-stats div {
    display: grid;
}
.copie-stats dt {
    font-size: 14px;
    color: var(--graphite);
}
.copie-stats dd {
    margin: 0;
    font-weight: 700;
    font-size: 22px;
}
.copie-gommettes {
    border-top: 2px dashed var(--quadrille);
    padding-top: 14px;
}
.copie-gommettes ul {
    list-style: none;
    margin: 8px 0 0;
    padding: 0;
    display: grid;
    gap: 12px;
}
.gommette-gagnee {
    display: flex;
    align-items: center;
    gap: 14px;
    animation: coller 0.5s cubic-bezier(0.3, 1.6, 0.5, 1) both;
}
@keyframes coller {
    from {
        opacity: 0;
        transform: scale(0.3) rotate(-30deg);
    }
}
.copie-cote {
    display: grid;
    gap: 22px;
    justify-items: center;
}
.copie-gribouille {
    width: 150px;
    height: 162px;
}
.invitation {
    display: grid;
    gap: 12px;
    max-width: 330px;
    transform: rotate(2deg);
}
.invitation-btn {
    font-size: 15px;
}
.copie-actions {
    display: grid;
    gap: 12px;
    width: 100%;
    max-width: 360px;
}
.corrige {
    display: grid;
    gap: 14px;
}
.corrige-liste {
    list-style: none;
    margin: 10px 0 0;
    padding: 0;
    display: grid;
    gap: 22px;
}
.corrige-item {
    display: grid;
    gap: 16px;
    padding: clamp(16px, 3vw, 24px);
}
.corrige-tete {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 6px 14px;
    align-items: start;
}
.corrige-num {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border: 2px solid var(--encre);
    border-radius: 50%;
    font-weight: 700;
    color: var(--encre-fixe);
}
.corrige-verdict {
    grid-column: 2;
}
.corrige-choix {
    display: grid;
    gap: 10px;
}
@media (min-width: 640px) {
    .corrige-choix {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
