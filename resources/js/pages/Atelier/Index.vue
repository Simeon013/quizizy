<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Gribouille from '../../composants/Gribouille.vue';

defineProps({
    quiz: { type: Array, required: true },
    generationDisponible: { type: Boolean, required: true },
    generationsRestantes: { type: Number, default: null },
});
</script>

<template>
    <Head title="Mon atelier" />
    <section class="page atelier">
        <header class="tete">
            <div>
                <p class="surtitre">L'atelier</p>
                <h1 class="t-page">Mes quiz</h1>
                <p class="chapeau">
                    Colle un texte ou donne un thème : Gribouille propose des questions <span class="crayon-mot">au crayon</span>. Tu les relis, tu
                    corriges, tu passes à l'encre. Rien n'est publié sans ta relecture.
                </p>
            </div>
            <Link href="/atelier/nouveau" class="btn btn-plein">Nouveau quiz</Link>
        </header>

        <ul v-if="quiz.length" class="liste">
            <li v-for="(q, i) in quiz" :key="q.slug" v-revele="i * 60">
                <Link :href="`/atelier/${q.slug}`" class="feuille carte">
                    <span class="pastille" :style="{ background: q.matiere.onglet }">{{ q.matiere.nom }}</span>
                    <strong class="t-carte">{{ q.titre }}</strong>
                    <span class="discret chiffres">{{ q.questions }} question{{ q.questions > 1 ? 's' : '' }}</span>
                    <span class="statut">
                        <span v-if="q.auCrayon" class="crayon-mot">{{ q.auCrayon }} au crayon, à relire</span>
                        <span v-else-if="q.publie" class="note-verte">{{ q.auCatalogue ? 'Publié, au catalogue' : 'Publié' }}</span>
                        <span v-else class="discret">Brouillon à l'encre</span>
                    </span>
                </Link>
            </li>
        </ul>
        <div v-else class="vide feuille">
            <Gribouille pose="reflechit" class="vide-gri" />
            <div>
                <p class="note-verte vide-texte">Ton atelier est vide. Et si tu transformais ton cours de la semaine en quiz ?</p>
                <Link href="/atelier/nouveau" class="btn">Créer mon premier quiz</Link>
            </div>
        </div>

        <p v-if="!generationDisponible" class="discret">La proposition de questions par l'IA n'est pas activée sur ce site : tu peux écrire tes questions à la main.</p>
        <p v-else-if="generationsRestantes !== null" class="discret chiffres">
            {{ generationsRestantes }} proposition{{ generationsRestantes > 1 ? 's' : '' }} de questions restante{{ generationsRestantes > 1 ? 's' : '' }} aujourd'hui.
        </p>
    </section>
</template>

<style scoped>
.atelier {
    display: grid;
    gap: 26px;
    padding-block: 34px 0;
}
.tete {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
}
.tete > div {
    display: grid;
    gap: 10px;
}
.crayon-mot {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 1.15em;
    color: var(--graphite);
}
.liste {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 18px;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
}
.carte {
    display: grid;
    gap: 8px;
    padding: 18px;
    color: inherit;
    text-decoration: none;
    height: 100%;
    align-content: start;
    transition: transform 0.2s;
}
.carte:hover {
    transform: translateY(-3px) rotate(-0.5deg);
}
.pastille {
    justify-self: start;
    color: var(--encre-fixe);
    font-weight: 700;
    font-size: 13px;
    padding: 2px 10px;
    border: 2px solid var(--encre);
    border-radius: 999px;
}
.vide {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 20px;
    padding: 24px;
}
.vide-gri {
    width: 100px;
    height: 110px;
}
.vide-texte {
    font-size: 26px;
    margin-bottom: 12px;
    max-width: 30ch;
}
</style>
