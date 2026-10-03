<script setup>
/**
 * L'écran de jeu. Le serveur décide de tout (bonne réponse, chrono, points) :
 * cette page affiche, envoie le choix et dessine la correction.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ChoixReponse from '../composants/ChoixReponse.vue';
import Crayon from '../composants/Crayon.vue';
import Ecrit from '../composants/Ecrit.vue';
import Gribouille from '../composants/Gribouille.vue';
import Regle from '../composants/Regle.vue';
import { envoyer } from '../lib/http';
import { page as tournerPage } from '../lib/sons';

const props = defineProps({
    partie: { type: Object, required: true },
    question: { type: Object, default: null },
});

const question = ref(props.question);
const correction = ref(null);
const choisi = ref(null);
const indice = ref(props.question?.indice ?? null);
const envoi = ref(false);
const erreur = ref('');
const points = ref(props.partie.points);
const serie = ref(props.partie.serie);
const reste = ref(1);
const secondes = ref(props.question?.secondes ?? 0);
const boutonSuivant = ref(null);

const lettres = ['1', '2', '3', '4', '5', '6'];
const url = (action) => `/parties/${props.partie.id}/${action}`;
const hasard = (liste) => liste[Math.floor(Math.random() * liste.length)];

// ---------- Le chrono (affiché ; le serveur fait foi) ----------
let finLe = 0;
let minuteur = 0;
function lancerChrono() {
    clearInterval(minuteur);
    if (!question.value) return;
    finLe = performance.now() + question.value.restantMs;
    const tic = () => {
        const restantMs = Math.max(0, finLe - performance.now());
        reste.value = restantMs / (question.value.secondes * 1000);
        secondes.value = Math.ceil(restantMs / 1000);
        if (restantMs <= 0) {
            clearInterval(minuteur);
            if (!correction.value && !envoi.value) repondre(null);
        }
    };
    tic();
    minuteur = setInterval(tic, 200);
}
onMounted(lancerChrono);
onBeforeUnmount(() => clearInterval(minuteur));

// ---------- Actions ----------
async function repondre(choixId) {
    if (correction.value || envoi.value || !question.value) return;
    envoi.value = true;
    erreur.value = '';
    choisi.value = choixId;
    try {
        const c = await envoyer(url('reponse'), { question_id: question.value.id, choix_id: choixId });
        clearInterval(minuteur);
        correction.value = c;
        choisi.value = c.choixId;
        points.value = c.totalPoints;
        serie.value = c.serie;
        requestAnimationFrame(() => boutonSuivant.value?.focus());
    } catch (e) {
        // Déjà répondu ailleurs (autre onglet, double envoi) : on recharge l'état du serveur.
        if (e.statut === 409) return router.reload();
        choisi.value = null;
        erreur.value = 'La réponse n\'est pas partie. Vérifie ta connexion et réessaie.';
    } finally {
        envoi.value = false;
    }
}

async function suivante() {
    if (!correction.value || envoi.value) return;
    if (correction.value.termine) return router.visit(url('copie'));
    envoi.value = true;
    try {
        const { question: q } = await envoyer(url('question'));
        if (!q) return router.visit(url('copie'));
        tournerPage();
        question.value = q;
        correction.value = null;
        choisi.value = null;
        indice.value = q.indice;
        lancerChrono();
    } catch {
        erreur.value = 'Impossible de tourner la page. Vérifie ta connexion et réessaie.';
    } finally {
        envoi.value = false;
    }
}

async function decollerIndice() {
    if (indice.value || correction.value) return;
    try {
        const { indice: texte } = await envoyer(url('indice'));
        indice.value = texte;
    } catch {
        erreur.value = 'L\'indice ne s\'est pas décollé. Réessaie.';
    }
}

// ---------- Clavier : 1 à 4 pour cocher, Entrée pour continuer ----------
function clavier(e) {
    if (e.target instanceof HTMLInputElement || e.metaKey || e.ctrlKey || e.altKey) return;
    if (!correction.value && question.value) {
        const i = lettres.indexOf(e.key);
        if (i >= 0 && question.value.choix[i]) repondre(question.value.choix[i].id);
    } else if (correction.value && e.key === 'Enter' && document.activeElement === document.body) {
        suivante();
    }
}
onMounted(() => window.addEventListener('keydown', clavier));
onBeforeUnmount(() => window.removeEventListener('keydown', clavier));

// ---------- Ce que la correction dit et montre ----------
const etatChoix = (id) => {
    if (!correction.value) return 'neutre';
    return id === correction.value.choixJusteId ? 'juste' : 'fausse';
};

const pose = computed(() => {
    if (correction.value) return correction.value.juste ? 'eureka' : 'oups';
    if (indice.value) return 'reflechit';
    if (reste.value < 0.25) return 'presse';
    return 'curieux';
});

const bulle = ref('À toi de jouer, prends ton temps.');
watch(
    () => [correction.value, indice.value, question.value?.id],
    () => {
        const c = correction.value;
        if (c?.juste) bulle.value = c.serie >= 3 ? `Eurêka ! ${c.serie} d'affilée !` : hasard(['Eurêka !', 'Bien vu !', 'Exactement !', 'Tu l\'as !']);
        else if (c?.tempsEcoule) bulle.value = 'Le crayon est usé… Tu feras mieux à la prochaine !';
        else if (c) bulle.value = hasard(['Ça arrive à tout le monde.', 'Pas grave, tu la retiendras !', 'Une erreur, c\'est une leçon.']);
        else if (indice.value) bulle.value = 'Un indice coûte la moitié des points.';
        else bulle.value = hasard(['À toi de jouer, prends ton temps.', 'Hmm… réfléchis bien.', 'Coche la bonne case !']);
    },
    { immediate: true },
);

const noteRouge = computed(() => {
    const c = correction.value;
    if (!c || c.juste) return '';
    if (c.tempsEcoule) return 'Temps écoulé !';
    const texte = question.value.choix.find((ch) => ch.id === c.choixId)?.texte;
    return `« ${texte} » : raté.`;
});
const noteVerte = computed(() => {
    const c = correction.value;
    if (!c) return '';
    if (c.juste) return `${c.indice ? 'Bien vu, avec un coup de pouce.' : 'Bien vu !'} ${c.explication}`;
    return `Pas grave ! ${c.explication}`;
});
</script>

<template>
    <Head :title="partie.titre" />
    <section class="page jeu">
        <header class="jeu-tete">
            <div class="jeu-titre">
                <Link v-if="partie.quiz" :href="`/quiz/${partie.quiz}`" class="surtitre lien-sobre">{{ partie.titre }}</Link>
                <span v-else class="surtitre">{{ partie.titre }}</span>
                <p v-if="question" class="jeu-numero">
                    Question <strong class="chiffres">{{ question.numero }}</strong> sur {{ question.total }}
                </p>
            </div>
            <div class="jeu-score" aria-live="polite">
                <span class="chiffres score-points"><strong>{{ points.toLocaleString('fr-FR') }}</strong> points</span>
                <span v-if="serie >= 2" class="note-verte score-serie">série de {{ serie }}</span>
            </div>
        </header>

        <Regle
            v-if="question"
            :valeur="question.numero - 1 + (correction ? 1 : 0)"
            :max="question.total"
            libelle="Progression dans la partie"
        />

        <div v-if="question" class="jeu-corps">
            <!-- La page se tourne : l'ancienne question part à gauche, la nouvelle arrive de droite. -->
            <Transition name="page" mode="out-in">
                <article :key="question.id" class="feuille carte-question">
                    <div class="carte-haut">
                        <span class="matiere-pastille" :style="{ background: question.onglet }">{{ question.matiere }}</span>
                        <Crayon v-if="!correction" :reste="reste" :secondes="secondes" class="carte-crayon" />
                        <span v-else-if="correction.points" class="points-gagnes note-rouge chiffres" aria-hidden="true">+{{ correction.points }}</span>
                    </div>

                    <h1 class="t-question">{{ question.enonce }}</h1>

                    <div class="choix-liste">
                        <ChoixReponse
                            v-for="(c, i) in question.choix"
                            :key="c.id"
                            :texte="c.texte"
                            :touche="lettres[i]"
                            :etat="etatChoix(c.id)"
                            :choisi="choisi === c.id"
                            :desactive="!!correction || envoi"
                            @choisir="repondre(c.id)"
                        />
                    </div>

                    <div class="marge" aria-live="polite">
                        <Ecrit v-if="noteRouge" :key="`r${question.id}`" :texte="noteRouge" class="note-rouge" :delai="500" :duree="700" />
                        <Ecrit v-if="noteVerte" :key="`v${question.id}`" :texte="noteVerte" class="note-verte" :delai="noteRouge ? 1300 : 1000" />
                        <p v-if="erreur" class="note-rouge" role="alert">{{ erreur }}</p>
                    </div>

                    <div class="carte-actions">
                        <button
                            v-if="!correction && question.aUnIndice && !indice"
                            type="button"
                            class="btn btn-discret"
                            @click="decollerIndice"
                        >
                            Décoller l'indice <span class="discret">(moitié des points)</span>
                        </button>
                        <button
                            v-if="correction"
                            ref="boutonSuivant"
                            type="button"
                            class="btn btn-plein"
                            :disabled="envoi"
                            @click="suivante"
                        >
                            {{ correction.termine ? 'Rendre ma copie' : 'Question suivante' }}
                            <svg viewBox="0 0 24 24" width="20" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </button>
                    </div>
                </article>
            </Transition>

            <aside class="jeu-cote">
                <Gribouille :pose="pose" class="jeu-gribouille" />
                <p class="bulle note-main" aria-live="polite">{{ bulle }}</p>
                <Transition name="postit">
                    <p v-if="indice && !correction" class="postit indice">Indice : {{ indice }}</p>
                </Transition>
            </aside>
        </div>
    </section>
</template>

<style scoped>
.jeu {
    display: grid;
    gap: 18px;
    padding-block: 26px 10px;
}
.jeu-tete {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px 20px;
}
.lien-sobre {
    text-decoration: none;
}
.jeu-numero {
    margin-top: 4px;
    font-size: 16px;
}
.jeu-score {
    display: flex;
    align-items: baseline;
    gap: 12px;
}
.score-points strong {
    font-family: var(--font-feutre);
    font-weight: 400;
    font-size: 26px;
}
.jeu-corps {
    display: grid;
    gap: 22px;
    align-items: start;
}
@media (min-width: 960px) {
    .jeu-corps {
        grid-template-columns: minmax(0, 1fr) 240px;
    }
}
.carte-question {
    display: grid;
    gap: 18px;
    padding: clamp(16px, 3vw, 28px);
    min-width: 0;
}
.carte-haut {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 34px;
}
.matiere-pastille {
    color: var(--encre-fixe);
    font-weight: 700;
    font-size: 14px;
    padding: 3px 12px;
    border: 2px solid var(--encre);
    border-radius: 999px;
    flex: none;
}
.carte-crayon {
    flex: 1;
    max-width: 260px;
    margin-left: auto;
}
.points-gagnes {
    margin-left: auto;
    font-size: 34px;
    animation: monter 0.7s cubic-bezier(0.3, 1.6, 0.5, 1) both;
}
@keyframes monter {
    from {
        transform: translateY(14px) scale(0.6);
        opacity: 0;
    }
}
.choix-liste {
    display: grid;
    gap: 12px;
}
@media (min-width: 640px) {
    .choix-liste {
        grid-template-columns: 1fr 1fr;
    }
}
.marge {
    display: grid;
    gap: 6px;
}
.marge p {
    animation: ecrire 0.45s ease-out both;
}
.marge p + p {
    animation-delay: 0.35s;
}
@keyframes ecrire {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
}
.carte-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.carte-actions .btn-plein {
    margin-left: auto;
}
.jeu-cote {
    display: grid;
    grid-template-columns: 76px 1fr;
    align-items: center;
    gap: 6px 14px;
}
.jeu-gribouille {
    width: 76px;
    height: 84px;
}
.bulle {
    font-size: 22px;
}
.indice {
    grid-column: 1 / -1;
    transform: rotate(2.5deg);
}
@media (min-width: 960px) {
    .jeu-cote {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
        position: sticky;
        top: 96px;
    }
    .jeu-gribouille {
        width: 170px;
        height: 184px;
    }
    .bulle {
        font-size: 26px;
    }
}
@media (max-width: 959px) {
    .jeu-cote {
        order: -1;
    }
}
.page-enter-active {
    transition:
        opacity 0.3s ease,
        transform 0.45s cubic-bezier(0.3, 1.3, 0.5, 1);
}
.page-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.25s ease-in;
}
.page-enter-from {
    opacity: 0;
    transform: translateX(48px) rotate(1.2deg);
}
.page-leave-to {
    opacity: 0;
    transform: translateX(-64px) rotate(-2.5deg);
}
.postit-enter-active {
    transition:
        opacity 0.3s,
        transform 0.45s cubic-bezier(0.3, 1.5, 0.5, 1);
}
.postit-enter-from {
    opacity: 0;
    transform: rotate(12deg) translateY(-20px);
}
</style>
