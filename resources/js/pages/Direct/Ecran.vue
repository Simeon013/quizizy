<script setup>
/**
 * Le grand écran de l'animateur, à projeter : l'appel des joueurs avec le code
 * et un QR code, la question, la répartition des réponses, le palmarès, le podium.
 */
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BoutonSon from '../../composants/BoutonSon.vue';
import BoutonTheme from '../../composants/BoutonTheme.vue';
import Crayon from '../../composants/Crayon.vue';
import Gribouille from '../../composants/Gribouille.vue';
import Logo from '../../composants/Logo.vue';
import Regle from '../../composants/Regle.vue';
import Tuile from '../../composants/Tuile.vue';
import { compteARebours, suivreEtat } from '../../lib/direct';
import { envoyer } from '../../lib/http';

defineOptions({ layout: null });

const props = defineProps({
    salle: { type: String, required: true },
    lienRejoindre: { type: String, required: true },
    etat: { type: Object, required: true },
});

const etat = ref(props.etat);
const envoi = ref(false);
const erreur = ref('');
const resteMs = ref(0);
const qr = ref('');
const aRetirer = ref(null);

let suivi;
let arreterRebours = () => {};

onMounted(async () => {
    suivi = suivreEtat(`/animer/${props.salle}/etat`, {
        version: etat.value.version,
        surEtat: (e) => (etat.value = e),
        surErreur: (e) => (erreur.value = e ? 'Connexion perdue… on réessaie.' : ''),
    });
    // QR code chargé à la demande : seul le grand écran en a besoin.
    const { renderSVG } = await import('uqr');
    qr.value = renderSVG(`${props.lienRejoindre}?code=${etat.value.code}`, { border: 1, pixelSize: 1 });
});
onBeforeUnmount(() => {
    suivi?.arreter();
    arreterRebours();
});

watch(
    () => [etat.value.etat, etat.value.question?.id, etat.value.question?.restantMs],
    () => {
        arreterRebours();
        const q = etat.value.question;
        if (etat.value.etat !== 'question' || !q) return;
        arreterRebours = compteARebours(q.restantMs, (ms) => {
            resteMs.value = ms;
            if (ms <= 0) suivi?.maintenant();
        });
    },
    { immediate: true },
);

async function commande(action) {
    if (envoi.value) return;
    envoi.value = true;
    erreur.value = '';
    try {
        const nouvel = await envoyer(`/animer/${props.salle}/${action}`);
        etat.value = nouvel;
        suivi?.connue(nouvel.version);
    } catch (e) {
        erreur.value = e.message;
        suivi?.maintenant();
    } finally {
        envoi.value = false;
    }
}

async function retirer(joueur) {
    try {
        const nouvel = await envoyer(`/animer/${props.salle}/participants/${joueur.id}`, {}, 'DELETE');
        etat.value = nouvel;
        suivi?.connue(nouvel.version);
    } catch {
        erreur.value = 'Le joueur n\'a pas pu être retiré. Réessaie.';
    } finally {
        aRetirer.value = null;
    }
}

const pleinEcran = () => document.documentElement.requestFullscreen?.().catch(() => {});

const q = computed(() => etat.value.question);
const c = computed(() => etat.value.correction);
const nbJoueurs = computed(() => etat.value.joueurs.length);
const adresse = computed(() => props.lienRejoindre.replace(/^https?:\/\//, ''));
const etatTuile = (id) => (!c.value ? 'neutre' : id === c.value.choixJusteId ? 'juste' : 'fausse');

// Le palmarès se réordonne en glissant : chaque ligne garde sa clé, seule sa position change.
const HAUTEUR = 3.1;
const palmaresMax = computed(() => Math.max(1, ...etat.value.palmares.map((p) => p.points)));
const podium = computed(() => [etat.value.palmares[1], etat.value.palmares[0], etat.value.palmares[2]]);

watch(
    () => etat.value.etat,
    async (e) => {
        if (e !== 'terminee' || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
        const { default: confettis } = await import('canvas-confetti');
        confettis({ particleCount: 160, spread: 90, origin: { y: 0.4 }, colors: ['#F3FF3D', '#FF9AD5', '#8CC8FF', '#8CFFB9', '#F2B705'] });
    },
);
</script>

<template>
    <Head :title="`Animer · ${etat.titre}`" />
    <div class="ecran">
        <header class="barre">
            <Logo petit />
            <p class="barre-titre">{{ etat.titre }}</p>
            <p v-if="etat.etat !== 'attente' && etat.etat !== 'terminee'" class="barre-code chiffres">
                Code <strong>{{ etat.code }}</strong>
            </p>
            <div class="barre-outils">
                <button type="button" class="btn btn-discret petit" @click="pleinEcran">Plein écran</button>
                <button
                    v-if="etat.etat !== 'terminee' && etat.etat !== 'attente'"
                    type="button"
                    class="btn btn-discret petit"
                    :disabled="envoi"
                    @click="commande('terminer')"
                >
                    Arrêter la partie
                </button>
                <BoutonSon />
                <BoutonTheme />
            </div>
        </header>

        <p v-if="erreur" class="postit alerte" role="alert">{{ erreur }}</p>

        <!-- L'appel -->
        <main v-if="etat.etat === 'attente'" class="scene appel">
            <section class="appel-entree">
                <p class="surtitre">Pour jouer, sur ton téléphone</p>
                <p class="appel-adresse">{{ adresse }}</p>
                <p class="discret">puis tape le code</p>
                <p class="appel-code chiffres" aria-label="Code de la partie">{{ etat.code.slice(0, 3) }} {{ etat.code.slice(3) }}</p>
                <div v-if="qr" class="qr feuille" aria-label="QR code pour rejoindre la partie" role="img" v-html="qr"></div>
            </section>
            <section class="appel-liste feuille" aria-labelledby="t-appel">
                <div class="appel-tete">
                    <h1 id="t-appel" class="t-section">L'appel</h1>
                    <span class="note-rouge chiffres">{{ nbJoueurs }} présent{{ nbJoueurs > 1 ? 's' : '' }}</span>
                </div>
                <ul v-if="nbJoueurs" class="pseudos">
                    <li v-for="j in etat.joueurs" :key="j.id" class="pseudo">
                        <span>{{ j.pseudo }}</span>
                        <button
                            v-if="aRetirer !== j.id"
                            type="button"
                            class="pseudo-retirer"
                            :aria-label="`Retirer ${j.pseudo}`"
                            title="Retirer ce joueur"
                            @click="aRetirer = j.id"
                        >
                            ×
                        </button>
                        <button v-else type="button" class="pseudo-confirmer" @click="retirer(j)">Retirer ?</button>
                    </li>
                </ul>
                <div v-else class="appel-vide">
                    <Gribouille pose="dodo" class="appel-gribouille" />
                    <p class="note-verte">On attend les premiers joueurs…</p>
                </div>
                <button type="button" class="btn btn-plein lancer" :disabled="envoi || !nbJoueurs" @click="commande('suivante')">
                    Lancer la première question
                </button>
            </section>
        </main>

        <!-- La question et sa correction -->
        <main v-else-if="(etat.etat === 'question' || etat.etat === 'correction') && q" class="scene jeu">
            <div class="jeu-tete">
                <span class="surtitre chiffres">Question {{ q.numero }} sur {{ etat.total }}</span>
                <Regle :valeur="q.numero - (etat.etat === 'question' ? 1 : 0)" :max="etat.total" libelle="Progression" class="jeu-regle" />
            </div>
            <div class="jeu-corps" :class="{ 'avec-palmares': c }">
                <section class="jeu-question">
                    <h1 class="t-page enonce">{{ q.enonce }}</h1>
                    <div class="tuiles">
                        <Tuile
                            v-for="ch in q.choix"
                            :key="ch.id"
                            :symbole="ch.symbole"
                            :texte="ch.texte"
                            :etat="etatTuile(ch.id)"
                            :compte="c ? c.repartition[ch.id] ?? 0 : null"
                            :sur="etat.nbReponses"
                        />
                    </div>
                    <p v-if="c" class="note-verte explication">{{ c.explication }}</p>
                </section>

                <aside v-if="c" class="palmares feuille" aria-labelledby="t-palmares">
                    <h2 id="t-palmares" class="t-section">Le palmarès</h2>
                    <ol class="lignes" :style="{ height: `${Math.min(5, etat.palmares.length) * HAUTEUR}em` }">
                        <li
                            v-for="(p, i) in etat.palmares"
                            v-show="i < 5"
                            :key="p.id"
                            class="ligne"
                            :style="{ top: `${i * HAUTEUR}em` }"
                        >
                            <span class="rang chiffres">{{ i + 1 }}</span>
                            <span class="barre-pal" :style="{ width: `${Math.max(30, Math.round((p.points / palmaresMax) * 100))}%` }">{{ p.pseudo }}</span>
                            <span class="points chiffres">{{ p.points.toLocaleString('fr-FR') }}</span>
                        </li>
                    </ol>
                </aside>
            </div>

            <footer class="jeu-pied">
                <template v-if="etat.etat === 'question'">
                    <Crayon :reste="resteMs / (q.secondes * 1000)" :secondes="Math.ceil(resteMs / 1000)" class="jeu-crayon" />
                    <p class="compteur chiffres"><strong>{{ etat.nbReponses }}</strong> / {{ nbJoueurs }} ont répondu</p>
                    <button type="button" class="btn" :disabled="envoi" @click="commande('corriger')">Corriger maintenant</button>
                </template>
                <template v-else>
                    <Gribouille pose="eureka" class="pied-gribouille" />
                    <p class="compteur chiffres"><strong>{{ c.repartition[c.choixJusteId] ?? 0 }}</strong> / {{ nbJoueurs }} ont trouvé</p>
                    <button type="button" class="btn btn-plein" :disabled="envoi" @click="commande('suivante')">
                        {{ q.numero >= etat.total ? 'Voir le podium' : 'Question suivante' }}
                    </button>
                </template>
            </footer>
        </main>

        <!-- Le podium -->
        <main v-else-if="etat.etat === 'terminee'" class="scene fin">
            <h1 class="t-page">Le podium</h1>
            <div v-if="etat.palmares.length" class="podium">
                <div v-for="(p, i) in podium" :key="i" class="marche" :class="`marche-${[2, 1, 3][i]}`">
                    <template v-if="p">
                        <Gribouille v-if="i === 1" pose="bravo" class="podium-gribouille" />
                        <span class="marche-pseudo">{{ p.pseudo }}</span>
                        <span class="marche-points chiffres">{{ p.points.toLocaleString('fr-FR') }} pts</span>
                        <span class="marche-bloc">{{ [2, 1, 3][i] }}</span>
                    </template>
                </div>
            </div>
            <p v-else class="note-verte">Personne n'a joué cette fois.</p>
            <ol v-if="etat.palmares.length > 3" class="suite chiffres" start="4">
                <li v-for="p in etat.palmares.slice(3)" :key="p.id">
                    <span>{{ p.pseudo }}</span><span class="discret">{{ p.points.toLocaleString('fr-FR') }}</span>
                </li>
            </ol>
            <Link href="/matieres" class="btn btn-plein">Animer une autre partie</Link>
        </main>
    </div>
</template>

<style scoped>
.ecran {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    font-size: clamp(15px, 1.35vw, 22px);
}
.barre {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 18px;
    padding: 10px clamp(16px, 3vw, 40px);
    border-bottom: 2px solid var(--encre);
    background: var(--papier);
    font-size: 16px;
}
.barre-titre {
    font-weight: 700;
}
.barre-code strong {
    font-family: var(--font-feutre);
    font-weight: 400;
    font-size: 26px;
    letter-spacing: 0.08em;
}
.barre-outils {
    display: flex;
    gap: 8px;
    margin-left: auto;
}
.petit {
    min-height: 44px;
    padding: 6px 14px;
    font-size: 14px;
}
.alerte {
    margin: 12px auto 0;
    width: min(520px, 90%);
}
.scene {
    flex: 1;
    display: grid;
    gap: 1.2em;
    padding: 1.4em clamp(16px, 3vw, 48px);
}

/* L'appel */
.appel {
    align-items: center;
}
@media (min-width: 900px) {
    .appel {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
        gap: 3em;
    }
}
.appel-entree {
    display: grid;
    gap: 0.5em;
    justify-items: center;
    text-align: center;
}
.appel-adresse {
    font-weight: 700;
    font-size: 1.7em;
    background: linear-gradient(transparent 55%, var(--surligne) 55%, var(--surligne) 92%, transparent 92%);
    overflow-wrap: anywhere;
}
.appel-code {
    font-family: var(--font-feutre);
    font-size: clamp(64px, 9vw, 150px);
    line-height: 1;
    letter-spacing: 0.06em;
    color: var(--rouge);
}
.qr {
    width: clamp(150px, 15vw, 230px);
    padding: 10px;
    background: #fff;
    color: #1e2b5c;
    transform: rotate(-2deg);
}
.qr :deep(svg) {
    display: block;
    width: 100%;
    height: auto;
}
.appel-liste {
    display: grid;
    gap: 1em;
    padding: 1.4em;
    align-content: start;
    min-width: 0;
}
.appel-tete {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
}
.pseudos {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5em;
    max-height: 48vh;
    overflow-y: auto;
}
.pseudo {
    display: inline-flex;
    align-items: center;
    gap: 0.3em;
    padding: 0.25em 0.3em 0.25em 0.8em;
    border: 2px solid var(--encre);
    border-radius: 999px;
    background: var(--carte);
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 1.35em;
    animation: arrive 0.45s cubic-bezier(0.3, 1.6, 0.5, 1) both;
}
@keyframes arrive {
    from {
        transform: scale(0.4) rotate(-10deg);
        opacity: 0;
    }
}
.pseudo-retirer,
.pseudo-confirmer {
    min-width: 32px;
    min-height: 32px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--graphite);
    font-size: 0.8em;
    cursor: pointer;
}
.pseudo-confirmer {
    color: #fff;
    background: var(--rouge);
    font-family: var(--font-sans);
    font-size: 0.55em;
    padding: 0 10px;
}
.appel-vide {
    display: flex;
    align-items: center;
    gap: 1em;
}
.appel-gribouille {
    width: 80px;
    height: 88px;
}
.lancer {
    justify-self: start;
    font-size: 1em;
}

/* La question */
.jeu {
    grid-template-rows: auto 1fr auto;
}
.jeu-tete {
    display: flex;
    align-items: center;
    gap: 1.2em;
}
.jeu-regle {
    flex: 1;
}
.jeu-corps {
    display: grid;
    gap: 1.6em;
    align-items: start;
}
@media (min-width: 1000px) {
    .jeu-corps.avec-palmares {
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
    }
}
.jeu-question {
    display: grid;
    gap: 1.1em;
    min-width: 0;
}
.enonce {
    font-size: clamp(30px, 3.6vw, 64px);
}
.tuiles {
    display: grid;
    gap: 1em;
    font-size: 1.25em;
}
@media (min-width: 700px) {
    .tuiles {
        grid-template-columns: 1fr 1fr;
    }
}
.explication {
    font-size: 1.4em;
}
.jeu-pied {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1em 1.6em;
    border-top: 2px dashed var(--quadrille);
    padding-top: 1em;
}
.jeu-crayon {
    flex: 1;
    min-width: 200px;
}
.compteur {
    font-size: 1.15em;
}
.compteur strong {
    font-family: var(--font-feutre);
    font-weight: 400;
    font-size: 1.6em;
    color: var(--rouge);
}
.jeu-pied .btn {
    margin-left: auto;
    font-size: 1em;
}
.pied-gribouille {
    width: 56px;
    height: 60px;
}

/* Le palmarès */
.palmares {
    display: grid;
    gap: 0.8em;
    padding: 1.2em;
    min-width: 0;
}
.lignes {
    position: relative;
    list-style: none;
    margin: 0;
    padding: 0;
}
.ligne {
    position: absolute;
    left: 0;
    right: 0;
    height: 2.6em;
    display: grid;
    grid-template-columns: 1.6em minmax(0, 1fr) auto;
    gap: 0.6em;
    align-items: center;
    transition: top 0.8s cubic-bezier(0.4, 1.5, 0.5, 1);
}
.rang {
    font-family: var(--font-feutre);
    text-align: right;
    color: var(--rouge);
}
.barre-pal {
    height: 2.1em;
    display: flex;
    align-items: center;
    padding-left: 0.6em;
    border: 2px solid var(--encre);
    border-radius: 6px;
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 1.1em;
    white-space: nowrap;
    overflow: hidden;
    background: repeating-linear-gradient(-45deg, var(--quadrille) 0 2px, transparent 2px 7px), var(--carte);
    transition: width 0.8s cubic-bezier(0.3, 1.3, 0.5, 1);
}
.ligne:first-child .barre-pal {
    background: repeating-linear-gradient(-45deg, #f2b705 0 2px, transparent 2px 7px), var(--carte);
}
.points {
    font-weight: 700;
}

/* Le podium */
.fin {
    justify-items: center;
    align-content: start;
    gap: 0.8em;
    text-align: center;
}
.podium {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: end;
    gap: 0.8em;
    width: min(760px, 100%);
}
.marche {
    display: grid;
    justify-items: center;
    gap: 0.3em;
    min-width: 0;
}
.marche-pseudo {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 1.6em;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.marche-points {
    font-size: 0.9em;
    color: var(--graphite);
}
.marche-bloc {
    width: 100%;
    display: grid;
    place-items: center;
    font-family: var(--font-feutre);
    font-size: 2.6em;
    color: var(--encre-fixe);
    border: 2.5px solid var(--encre);
    border-radius: 12px 12px 4px 4px;
    animation: pousser 0.9s cubic-bezier(0.3, 1.4, 0.5, 1) both;
    transform-origin: bottom;
}
@keyframes pousser {
    from {
        transform: scaleY(0);
    }
}
.marche-1 .marche-bloc {
    height: 5.6em;
    background: repeating-linear-gradient(-45deg, #1e2b5c33 0 2px, transparent 2px 8px), #ffe27a;
    animation-delay: 0.6s;
}
.marche-2 .marche-bloc {
    height: 4em;
    background: repeating-linear-gradient(-45deg, #1e2b5c33 0 2px, transparent 2px 8px), #8cc8ff;
    animation-delay: 0.3s;
}
.marche-3 .marche-bloc {
    height: 2.8em;
    background: repeating-linear-gradient(-45deg, #1e2b5c33 0 2px, transparent 2px 8px), #ffb4a2;
}
.podium-gribouille {
    width: 70px;
    height: 76px;
}
.suite {
    width: min(460px, 100%);
    margin: 0;
    padding-left: 1.6em;
    text-align: left;
    display: grid;
    gap: 0.3em;
}
.suite li span:first-child {
    font-weight: 700;
    margin-right: 0.6em;
}
</style>
