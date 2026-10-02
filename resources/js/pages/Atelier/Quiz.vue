<script setup>
/**
 * La table de travail d'un quiz : les questions au crayon (proposées, pas encore
 * relues) et à l'encre (relues), leur correction, et la publication.
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AttenteRedaction from '../../composants/AttenteRedaction.vue';
import EditeurQuestion from '../../composants/EditeurQuestion.vue';

const props = defineProps({
    quiz: { type: Object, required: true },
    questions: { type: Array, required: true },
    matieres: { type: Array, required: true },
    minimum: { type: Number, required: true },
    sourceMax: { type: Number, required: true },
    admin: { type: Boolean, default: false },
    generationDisponible: { type: Boolean, required: true },
    generationsRestantes: { type: Number, default: null },
    errors: { type: Object, default: () => ({}) },
});

const base = computed(() => `/atelier/${props.quiz.slug}`);
const enEdition = ref(null); // id d'une question, ou 'nouvelle'
const aGommer = ref(null);
const couvertureOuverte = ref(false);
const propositionOuverte = ref(props.questions.length === 0 && props.generationDisponible);
const copie = ref(false);

const auCrayon = computed(() => props.questions.filter((q) => !q.aLEncre).length);
const manque = computed(() => Math.max(0, props.minimum - props.questions.length));
const publiable = computed(() => manque.value === 0 && auCrayon.value === 0);
const peutGenerer = computed(() => props.generationDisponible && props.generationsRestantes !== 0);

const couverture = useForm({ titre: props.quiz.titre, matiere_id: props.quiz.matiereId, description: props.quiz.description ?? '' });
const enregistrerCouverture = () => couverture.patch(base.value, { preserveScroll: true, onSuccess: () => (couvertureOuverte.value = false) });

const proposition = useForm({ mode: 'texte', source: '', theme: '', nombre: 5 });
const proposer = () => proposition.post(`${base.value}/propositions`, { preserveScroll: true, onSuccess: () => (propositionOuverte.value = false) });

const options = { preserveScroll: true };
const encrer = (q) => router.post(`${base.value}/questions/${q.id}/encre`, {}, options);
const encrerTout = () => router.post(`${base.value}/encre`, {}, options);
const gommer = (q) => router.delete(`${base.value}/questions/${q.id}`, { ...options, onFinish: () => (aGommer.value = null) });
const publier = () => router.post(`${base.value}/publier`, {}, options);
const depublier = () => router.post(`${base.value}/depublier`, {}, options);
const basculerCatalogue = () => router.post(`${base.value}/catalogue`, {}, options);
const animer = () => router.post(`/quiz/${props.quiz.slug}/direct`);

async function copierLien() {
    try {
        await navigator.clipboard.writeText(props.quiz.lien);
        copie.value = true;
        setTimeout(() => (copie.value = false), 2000);
    } catch {
        copie.value = false;
    }
}
</script>

<template>
    <Head :title="`Atelier · ${quiz.titre}`" />
    <AttenteRedaction v-if="proposition.processing" />

    <section class="page table">
        <Link href="/atelier" class="lien retour">← Mon atelier</Link>

        <!-- La couverture -->
        <header class="feuille couverture">
            <template v-if="!couvertureOuverte">
                <span class="pastille" :style="{ background: quiz.matiere.onglet }">{{ quiz.matiere.nom }}</span>
                <h1 class="t-page">{{ quiz.titre }}</h1>
                <p v-if="quiz.description">{{ quiz.description }}</p>
                <button type="button" class="lien bouton-lien" @click="couvertureOuverte = true">Modifier la couverture</button>
            </template>
            <form v-else class="couverture-form" @submit.prevent="enregistrerCouverture">
                <label class="etiquette-champ" for="c-titre">Titre</label>
                <input id="c-titre" v-model="couverture.titre" class="champ" maxlength="120" />
                <p v-if="couverture.errors.titre" class="note-rouge">{{ couverture.errors.titre }}</p>
                <label class="etiquette-champ" for="c-matiere">Matière</label>
                <select id="c-matiere" v-model="couverture.matiere_id" class="champ">
                    <option v-for="m in matieres" :key="m.id" :value="m.id">{{ m.nom }}</option>
                </select>
                <label class="etiquette-champ" for="c-desc">Présentation</label>
                <input id="c-desc" v-model="couverture.description" class="champ" maxlength="255" />
                <div class="rang-boutons">
                    <button type="submit" class="btn btn-plein" :disabled="couverture.processing">Enregistrer</button>
                    <button type="button" class="btn btn-discret" @click="couvertureOuverte = false">Annuler</button>
                </div>
            </form>
        </header>

        <!-- L'état et la publication -->
        <div class="etat" :class="{ 'etat-publie': quiz.publie }">
            <div class="etat-texte">
                <template v-if="quiz.publie">
                    <p class="note-verte">Publié{{ quiz.auCatalogue ? ', au catalogue public' : '' }}.</p>
                    <p class="discret">{{ quiz.auCatalogue ? 'Il apparaît dans les intercalaires.' : 'Il se joue par son lien, ou en direct.' }}</p>
                </template>
                <template v-else>
                    <p class="t-carte">Brouillon</p>
                    <p v-if="manque" class="discret">Encore {{ manque }} question{{ manque > 1 ? 's' : '' }} pour pouvoir publier ({{ minimum }} au moins).</p>
                    <p v-else-if="auCrayon" class="crayon-mot">{{ auCrayon }} question{{ auCrayon > 1 ? 's' : '' }} au crayon à relire avant de publier.</p>
                    <p v-else class="note-verte">Tout est à l'encre : prêt à publier !</p>
                </template>
                <p v-if="errors.publier" class="note-rouge" role="alert">{{ errors.publier }}</p>
                <p v-if="errors.gommer" class="note-rouge" role="alert">{{ errors.gommer }}</p>
            </div>
            <div class="etat-actions">
                <template v-if="quiz.publie">
                    <Link :href="`/quiz/${quiz.slug}`" class="btn">Jouer</Link>
                    <button type="button" class="btn" @click="animer">Animer en direct</button>
                    <button type="button" class="btn btn-discret" @click="copierLien">{{ copie ? 'Lien copié !' : 'Copier le lien' }}</button>
                    <button v-if="admin" type="button" class="btn btn-discret" @click="basculerCatalogue">
                        {{ quiz.auCatalogue ? 'Retirer du catalogue' : 'Mettre au catalogue' }}
                    </button>
                    <button type="button" class="btn btn-discret" @click="depublier">Dépublier</button>
                </template>
                <template v-else>
                    <Link v-if="questions.length" :href="`/quiz/${quiz.slug}`" class="btn btn-discret">Essayer</Link>
                    <button type="button" class="btn btn-plein" :disabled="!publiable" @click="publier">Publier</button>
                </template>
            </div>
        </div>

        <!-- Les questions -->
        <section class="questions" aria-labelledby="t-questions">
            <div class="questions-tete">
                <h2 id="t-questions" class="t-section">Les questions <span class="discret chiffres">({{ questions.length }})</span></h2>
                <button v-if="auCrayon > 1" type="button" class="btn btn-discret" @click="encrerTout">Tout passer à l'encre</button>
            </div>

            <ol v-if="questions.length" class="liste">
                <li v-for="(q, i) in questions" :key="q.id" class="feuille question" :class="q.aLEncre ? 'encre' : 'crayon'">
                    <div class="question-tete">
                        <span class="numero chiffres">{{ i + 1 }}</span>
                        <span v-if="!q.aLEncre" class="tampon-crayon">au crayon · à relire</span>
                        <span v-if="q.jouee" class="discret">déjà jouée</span>
                    </div>

                    <EditeurQuestion
                        v-if="enEdition === q.id"
                        :action="`${base}/questions/${q.id}`"
                        methode="put"
                        :question="q"
                        @fini="enEdition = null"
                    />
                    <template v-else>
                        <p class="enonce">{{ q.enonce }}</p>
                        <ul class="choix">
                            <li v-for="(c, j) in q.choix" :key="j" :class="{ bon: c.juste }">
                                <span class="case" aria-hidden="true">{{ c.juste ? '✓' : '' }}</span>
                                {{ c.texte }}<span v-if="c.juste" class="visually-hidden"> (bonne réponse)</span>
                            </li>
                        </ul>
                        <p class="explication">{{ q.explication }}</p>
                        <p v-if="q.indice" class="indice">Indice : {{ q.indice }}</p>
                        <div class="question-actions">
                            <button v-if="!q.aLEncre" type="button" class="btn btn-plein petit" @click="encrer(q)">Passer à l'encre</button>
                            <button type="button" class="btn petit" @click="enEdition = q.id">Corriger</button>
                            <template v-if="!q.jouee">
                                <button v-if="aGommer !== q.id" type="button" class="btn btn-discret petit" @click="aGommer = q.id">Gommer</button>
                                <button v-else type="button" class="btn petit gommer-confirmer" @click="gommer(q)">Gommer pour de bon ?</button>
                            </template>
                        </div>
                    </template>
                </li>
            </ol>
            <p v-else class="note-verte">Pas encore de question. Fais-en proposer, ou écris la première.</p>

            <div class="feuille ajout">
                <EditeurQuestion v-if="enEdition === 'nouvelle'" :action="`${base}/questions`" @fini="enEdition = null" />
                <div v-else class="ajout-boutons">
                    <button type="button" class="btn" @click="enEdition = 'nouvelle'">Écrire une question</button>
                    <button v-if="peutGenerer" type="button" class="btn" @click="propositionOuverte = !propositionOuverte">
                        {{ questions.length ? 'Proposer d\'autres questions' : 'Faire proposer des questions' }}
                    </button>
                    <span v-if="generationsRestantes !== null && generationDisponible" class="discret chiffres">
                        {{ generationsRestantes }} proposition{{ generationsRestantes > 1 ? 's' : '' }} restante{{ generationsRestantes > 1 ? 's' : '' }} aujourd'hui
                    </span>
                </div>

                <form v-if="propositionOuverte && peutGenerer && enEdition !== 'nouvelle'" class="proposition" novalidate @submit.prevent="proposer">
                    <div class="bascule" role="radiogroup" aria-label="Source des questions">
                        <label :class="{ actif: proposition.mode === 'texte' }"><input v-model="proposition.mode" type="radio" value="texte" /> D'un texte</label>
                        <label :class="{ actif: proposition.mode === 'theme' }"><input v-model="proposition.mode" type="radio" value="theme" /> D'un thème</label>
                    </div>
                    <template v-if="proposition.mode === 'texte'">
                        <label class="visually-hidden" for="p-source">Texte source</label>
                        <textarea id="p-source" v-model="proposition.source" class="champ zone" rows="7" :maxlength="sourceMax" placeholder="Colle ici le texte dont tirer les questions…"></textarea>
                        <p class="discret chiffres">{{ proposition.source.length.toLocaleString('fr-FR') }} / {{ sourceMax.toLocaleString('fr-FR') }} caractères · au moins 200</p>
                        <p v-if="proposition.errors.source" class="note-rouge" role="alert">{{ proposition.errors.source }}</p>
                    </template>
                    <template v-else>
                        <label class="visually-hidden" for="p-theme">Thème</label>
                        <input id="p-theme" v-model="proposition.theme" class="champ" maxlength="200" placeholder="Le thème des questions" />
                        <p v-if="proposition.errors.theme" class="note-rouge" role="alert">{{ proposition.errors.theme }}</p>
                    </template>
                    <div class="nombre">
                        <label for="p-nombre">Combien ?</label>
                        <input id="p-nombre" v-model.number="proposition.nombre" type="range" min="1" max="10" />
                        <strong class="chiffres">{{ proposition.nombre }}</strong>
                    </div>
                    <button type="submit" class="btn btn-plein" :disabled="proposition.processing">Proposer au crayon</button>
                </form>
            </div>
        </section>
    </section>
</template>

<style scoped>
.table {
    display: grid;
    gap: 22px;
    padding-block: 30px 0;
}
.retour {
    justify-self: start;
}
.couverture {
    display: grid;
    gap: 8px;
    justify-items: start;
    padding: clamp(18px, 3vw, 28px);
}
.couverture-form {
    display: grid;
    gap: 6px;
    width: 100%;
}
.pastille {
    color: var(--encre-fixe);
    font-weight: 700;
    font-size: 13px;
    padding: 2px 10px;
    border: 2px solid var(--encre);
    border-radius: 999px;
}
.bouton-lien {
    background: none;
    border: 0;
    padding: 4px 0;
    font: 700 15px var(--font-sans);
    color: var(--encre);
    cursor: pointer;
}
.rang-boutons,
.question-actions,
.ajout-boutons {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.etat {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 18px;
    border: 2px dashed var(--graphite);
    border-radius: 10px;
}
.etat-publie {
    border-style: solid;
    border-color: var(--vert);
}
.etat-texte {
    display: grid;
    gap: 2px;
}
.etat-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.crayon-mot {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 21px;
    color: var(--graphite);
}
.questions {
    display: grid;
    gap: 16px;
}
.questions-tete {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
}
.liste {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 18px;
}
.question {
    display: grid;
    gap: 10px;
    padding: clamp(16px, 3vw, 24px);
    transition:
        color 0.5s,
        border-color 0.5s,
        box-shadow 0.5s;
}
/* Au crayon : trait gris en pointillé, écriture à la main, pas d'ombre. */
.question.crayon {
    color: var(--graphite);
    border-style: dashed;
    border-color: var(--graphite);
    box-shadow: none;
    background: transparent;
}
.question.crayon .enonce {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 25px;
}
.question.crayon .explication {
    color: var(--graphite);
}
.question-tete {
    display: flex;
    align-items: center;
    gap: 12px;
}
.numero {
    font-family: var(--font-feutre);
    font-size: 26px;
    color: var(--rouge);
}
.crayon .numero {
    color: var(--graphite);
}
.tampon-crayon {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    border: 1.5px dashed currentColor;
    border-radius: 4px;
    padding: 1px 8px;
}
.enonce {
    font-weight: 700;
    font-size: 19px;
    line-height: 1.3;
}
.choix {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 6px;
}
@media (min-width: 640px) {
    .choix {
        grid-template-columns: 1fr 1fr;
    }
}
.choix li {
    display: flex;
    align-items: center;
    gap: 10px;
}
.case {
    display: grid;
    place-items: center;
    width: 22px;
    height: 22px;
    flex: none;
    border: 2px solid currentColor;
    border-radius: 4px;
    font-weight: 700;
    color: var(--vert);
}
.choix li:not(.bon) .case {
    color: inherit;
}
.choix .bon {
    font-weight: 700;
    background: linear-gradient(transparent 55%, var(--surligne) 55%);
}
.explication {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 21px;
    line-height: 1.15;
    color: var(--vert);
}
.indice {
    justify-self: start;
    background: var(--postit);
    color: var(--encre-fixe);
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 19px;
    padding: 4px 12px;
    transform: rotate(-1deg);
}
.petit {
    min-height: 44px;
    padding: 6px 14px;
    font-size: 15px;
}
.gommer-confirmer {
    color: #fff;
    background: var(--rouge);
    border-color: var(--rouge);
}
.ajout {
    display: grid;
    gap: 14px;
    padding: clamp(16px, 3vw, 24px);
}
.proposition {
    display: grid;
    gap: 10px;
    border-top: 2px dashed var(--quadrille);
    padding-top: 14px;
}
.bascule {
    display: flex;
    gap: 8px;
}
.bascule label {
    padding: 8px 14px;
    border: 2px solid var(--quadrille);
    border-radius: 999px;
    font-weight: 700;
    cursor: pointer;
}
.bascule .actif {
    border-color: var(--encre);
    background: var(--surligne);
    color: var(--encre);
}
.bascule input {
    accent-color: var(--encre);
}
.zone {
    resize: vertical;
    font-family: var(--font-sans);
    font-size: 16px;
    border: 2px dashed var(--graphite);
    border-radius: 8px;
    padding: 12px;
}
.nombre {
    display: flex;
    align-items: center;
    gap: 12px;
}
.nombre input {
    flex: 1;
    max-width: 260px;
    accent-color: var(--rouge);
}
.nombre strong {
    font-family: var(--font-feutre);
    font-weight: 400;
    font-size: 26px;
}
.proposition .btn-plein {
    justify-self: start;
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
