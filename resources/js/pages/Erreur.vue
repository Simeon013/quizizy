<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Gribouille from '../composants/Gribouille.vue';

const props = defineProps({ statut: { type: Number, required: true } });

const textes = {
    403: ['Page réservée', 'Cette page n\'est pas dans ton cahier.'],
    404: ['Page introuvable', 'Cette page a dû être arrachée du cahier. Elle n\'existe pas, ou plus.'],
    429: ['Doucement !', 'Trop de demandes d\'un coup. Respire, puis réessaie dans une minute.'],
    500: ['Tache d\'encre', 'Quelque chose s\'est mal passé de notre côté. Réessaie dans un instant.'],
    503: ['Cahier fermé', 'Eurêka fait une petite pause pour se mettre à jour. Reviens dans quelques minutes.'],
};
const texte = computed(() => textes[props.statut] ?? textes[500]);
const pose = computed(() => (props.statut === 503 ? 'dodo' : props.statut === 404 ? 'curieux' : 'oups'));
</script>

<template>
    <Head :title="texte[0]" />
    <section class="page erreur">
        <Gribouille :pose="pose" class="erreur-gribouille" />
        <div class="erreur-texte">
            <p class="surtitre chiffres">Erreur {{ statut }}</p>
            <h1 class="t-page">{{ texte[0] }}</h1>
            <p class="chapeau">{{ texte[1] }}</p>
            <Link href="/" class="btn btn-plein">Retour à la couverture</Link>
        </div>
    </section>
</template>

<style scoped>
.erreur {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 30px;
    padding-block: 60px 0;
}
.erreur-gribouille {
    width: 150px;
    height: 162px;
}
.erreur-texte {
    display: grid;
    gap: 14px;
    justify-items: start;
    flex: 1;
    min-width: 260px;
}
</style>
