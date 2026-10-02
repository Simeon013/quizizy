<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    matiere: { type: Object, required: true },
    quiz: { type: Array, required: true },
});

const commencer = (slug) => router.post(`/quiz/${slug}/parties`);
</script>

<template>
    <Head :title="matiere.nom" />
    <section class="page matiere">
        <header class="onglet matiere-tete" :style="{ background: matiere.onglet }">
            <Link href="/matieres" class="retour">← Toutes les matières</Link>
            <h1 class="t-page">{{ matiere.nom }}</h1>
            <p>{{ matiere.description }}</p>
        </header>

        <ul class="quiz-liste">
            <li v-for="(q, i) in quiz" :key="q.slug" v-revele="i * 100" class="feuille quiz-carte">
                <div class="quiz-texte">
                    <h2 class="t-section"><Link :href="`/quiz/${q.slug}`" class="titre-lien">{{ q.titre }}</Link></h2>
                    <p>{{ q.description }}</p>
                    <p class="discret">{{ q.questions }} questions</p>
                </div>
                <div class="quiz-actions">
                    <p v-if="q.meilleure" class="note-rouge chiffres">Meilleure copie : {{ q.meilleure.bonnes }}/{{ q.meilleure.total }}</p>
                    <button type="button" class="btn btn-plein" @click="commencer(q.slug)">{{ q.meilleure ? 'Rejouer' : 'Commencer' }}</button>
                </div>
            </li>
        </ul>
        <p v-if="!quiz.length" class="note-verte">Les quiz de cette matière arrivent bientôt.</p>
    </section>
</template>

<style scoped>
.matiere {
    display: grid;
    gap: 0;
    padding-block: 34px 0;
}
.matiere-tete {
    display: grid;
    gap: 8px;
    padding: 22px clamp(18px, 4vw, 34px) 30px;
    border-bottom: 2px solid var(--encre);
}
.retour {
    color: inherit;
    font-weight: 700;
    font-size: 15px;
}
.quiz-liste {
    list-style: none;
    margin: 0;
    padding: 28px 0 0;
    display: grid;
    gap: 22px;
}
.quiz-carte {
    display: grid;
    gap: 16px;
    padding: clamp(18px, 3vw, 26px);
}
@media (min-width: 720px) {
    .quiz-carte {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
    }
}
.quiz-texte {
    display: grid;
    gap: 6px;
    min-width: 0;
}
.titre-lien {
    color: inherit;
    text-decoration: none;
}
.titre-lien:hover {
    background: linear-gradient(transparent 60%, var(--surligne) 60%);
}
.quiz-actions {
    display: grid;
    gap: 8px;
    justify-items: start;
}
@media (min-width: 720px) {
    .quiz-actions {
        justify-items: end;
    }
}
</style>
