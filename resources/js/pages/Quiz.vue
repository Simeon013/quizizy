<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Gribouille from '../composants/Gribouille.vue';

const props = defineProps({
    quiz: { type: Object, required: true },
    matiere: { type: Object, required: true },
    meilleure: { type: Object, default: null },
    enCours: { type: String, default: null },
});

const envoi = ref(false);
const connecte = computed(() => !!usePage().props.utilisateur);
const animer = () => router.post(`/quiz/${props.quiz.slug}/direct`);
const commencer = () => router.post(`/quiz/${props.quiz.slug}/parties`, {}, { onStart: () => (envoi.value = true), onFinish: () => (envoi.value = false) });
</script>

<template>
    <Head :title="quiz.titre" />
    <section class="page garde">
        <article class="feuille garde-feuille">
            <Link :href="`/matieres/${matiere.slug}`" class="matiere-pastille" :style="{ background: matiere.onglet }">{{ matiere.nom }}</Link>
            <h1 class="t-page">{{ quiz.titre }}</h1>
            <p v-if="quiz.brouillon" class="postit brouillon">
                Brouillon : toi seul peux le voir. <Link :href="`/atelier/${quiz.slug}`" class="lien">Retour à l'atelier</Link>
            </p>
            <p v-else-if="quiz.monQuiz" class="discret"><Link :href="`/atelier/${quiz.slug}`" class="lien">Modifier dans l'atelier</Link></p>
            <p class="chapeau">{{ quiz.description }}</p>
            <ul class="regles">
                <li><strong class="chiffres">{{ quiz.questions }}</strong> questions</li>
                <li><strong class="chiffres">{{ quiz.secondes }}</strong> secondes par question, avant que le crayon soit usé</li>
                <li>un indice à décoller si tu bloques, pour la moitié des points</li>
                <li>chaque erreur est expliquée au stylo vert</li>
            </ul>
            <p v-if="meilleure" class="note-rouge chiffres">Ta meilleure copie : {{ meilleure.bonnes }}/{{ meilleure.total }}</p>
            <div class="garde-actions">
                <Link v-if="enCours" :href="`/parties/${enCours}`" class="btn btn-plein">Reprendre la page cornée</Link>
                <button type="button" class="btn" :class="{ 'btn-plein': !enCours }" :disabled="envoi" @click="commencer">
                    {{ enCours ? 'Nouvelle copie' : 'Commencer la copie' }}
                </button>
            </div>
            <div class="direct">
                <p class="note-main">En classe ou entre amis ?</p>
                <p v-if="connecte">
                    Projette ce quiz au tableau : chacun répond depuis son téléphone avec un code.
                    <button type="button" class="btn btn-discret direct-btn" @click="animer">Animer en direct</button>
                </p>
                <p v-else class="discret">
                    <Link href="/connexion" class="lien">Connecte-toi</Link> pour animer ce quiz en direct sur grand écran.
                </p>
            </div>
        </article>
        <Gribouille pose="curieux" class="garde-gribouille" />
    </section>
</template>

<style scoped>
.garde {
    display: grid;
    gap: 24px;
    align-items: center;
    padding-block: 40px 0;
}
@media (min-width: 860px) {
    .garde {
        grid-template-columns: minmax(0, 1fr) 220px;
    }
}
.garde-feuille {
    display: grid;
    gap: 16px;
    justify-items: start;
    padding: clamp(20px, 4vw, 36px);
    transform: rotate(-0.6deg);
}
.matiere-pastille {
    color: var(--encre-fixe);
    font-weight: 700;
    font-size: 14px;
    padding: 4px 14px;
    border: 2px solid var(--encre);
    border-radius: 999px;
    text-decoration: none;
}
.brouillon {
    font-size: 20px;
    transform: rotate(-1deg);
}
.regles {
    margin: 0;
    padding-left: 1.2em;
    display: grid;
    gap: 4px;
}
.regles li::marker {
    color: var(--rouge);
}
.garde-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 6px;
}
.direct {
    border-top: 2px dashed var(--quadrille);
    padding-top: 14px;
    width: 100%;
    display: grid;
    gap: 6px;
}
.direct-btn {
    display: flex;
    margin-top: 10px;
}
.garde-gribouille {
    width: 180px;
    height: 195px;
    justify-self: center;
}
@media (max-width: 859px) {
    .direct {
    border-top: 2px dashed var(--quadrille);
    padding-top: 14px;
    width: 100%;
    display: grid;
    gap: 6px;
}
.direct-btn {
    display: flex;
    margin-top: 10px;
}
.garde-gribouille {
        display: none;
    }
}
</style>
