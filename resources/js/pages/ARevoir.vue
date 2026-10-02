<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Gribouille from '../composants/Gribouille.vue';

defineProps({
    questions: { type: Array, required: true },
    total: { type: Number, required: true },
});

const reviser = () => router.post('/a-revoir/parties');
</script>

<template>
    <Head title="À revoir" />
    <section class="page revoir">
        <header class="tete">
            <p class="surtitre">Mon cahier</p>
            <h1 class="t-page">À revoir</h1>
            <p class="chapeau">Les questions que tu as ratées la dernière fois. Réussis-les une fois et elles quittent la liste.</p>
        </header>

        <template v-if="total">
            <div class="lancer">
                <button type="button" class="btn btn-plein" @click="reviser">
                    Réviser {{ Math.min(total, 10) }} question{{ total > 1 ? 's' : '' }}
                </button>
                <span class="discret chiffres">{{ total }} en tout</span>
            </div>
            <ul class="feuille liste">
                <li v-for="(q, i) in questions" :key="i">
                    <span class="puce" :style="{ background: q.onglet }" :title="q.matiere"></span>
                    <span class="enonce">{{ q.enonce }}</span>
                    <span class="discret">{{ q.matiere }}</span>
                </li>
            </ul>
        </template>
        <div v-else class="vide feuille">
            <Gribouille pose="bravo" class="vide-gribouille" />
            <div>
                <p class="note-verte vide-texte">Rien à revoir. Toutes tes erreurs sont réparées !</p>
                <Link href="/matieres" class="btn">Nouvelle copie</Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
.revoir {
    display: grid;
    gap: 24px;
    padding-block: 34px 0;
}
.tete {
    display: grid;
    gap: 10px;
}
.lancer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}
.liste {
    list-style: none;
    margin: 0;
    padding: 8px clamp(14px, 3vw, 24px);
}
.liste li {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding-block: 12px;
    border-bottom: 1px dashed var(--quadrille);
}
.liste li:last-child {
    border-bottom: 0;
}
.puce {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid var(--encre);
}
.enonce {
    font-weight: 700;
}
@media (max-width: 560px) {
    .liste li {
        grid-template-columns: auto minmax(0, 1fr);
    }
    .liste li .discret {
        display: none;
    }
}
.vide {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 20px;
    padding: 24px;
}
.vide-gribouille {
    width: 110px;
    height: 120px;
}
.vide-texte {
    font-size: 28px;
    margin-bottom: 12px;
}
</style>
