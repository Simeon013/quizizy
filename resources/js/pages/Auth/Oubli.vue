<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Gribouille from '../../composants/Gribouille.vue';

const form = useForm({ email: '' });
const envoye = computed(() => !!usePage().props.flash?.succes);
const envoyer = () => form.post('/mot-de-passe-oublie', { preserveScroll: true });
</script>

<template>
    <Head title="Mot de passe oublié" />
    <section class="page auth">
        <form class="feuille carte" novalidate @submit.prevent="envoyer">
            <Gribouille :pose="envoye ? 'eureka' : 'reflechit'" class="carte-gribouille" />
            <h1 class="t-page">Mot de passe oublié</h1>
            <p>Ça arrive à tout le monde. Donne l'adresse de ton cahier : tu recevras un lien pour choisir un nouveau mot de passe.</p>

            <label class="etiquette-champ" for="email">Adresse email</label>
            <input id="email" v-model="form.email" class="champ" type="email" autocomplete="email" required autofocus />
            <p v-if="form.errors.email" class="note-rouge" role="alert">{{ form.errors.email }}</p>

            <button type="submit" class="btn btn-plein" :disabled="form.processing">Recevoir le lien</button>
            <p class="discret"><Link href="/connexion" class="lien">Retour à la connexion</Link></p>
        </form>
    </section>
</template>

<style scoped>
.auth {
    display: grid;
    justify-items: center;
    padding-block: 40px 0;
}
.carte {
    position: relative;
    width: min(460px, 100%);
    display: grid;
    gap: 10px;
    padding: clamp(22px, 4vw, 34px);
}
.carte-gribouille {
    position: absolute;
    top: -30px;
    right: 14px;
    width: 70px;
    height: 76px;
}
.carte .t-page {
    padding-right: 60px;
}
.etiquette-champ {
    margin-top: 8px;
}
.btn-plein {
    margin-top: 8px;
}
</style>
