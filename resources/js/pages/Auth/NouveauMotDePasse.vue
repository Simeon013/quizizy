<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Gribouille from '../../composants/Gribouille.vue';

const props = defineProps({
    jeton: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({ jeton: props.jeton, email: props.email, password: '', password_confirmation: '' });
const envoyer = () => form.post('/mot-de-passe', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Nouveau mot de passe" />
    <section class="page auth">
        <form class="feuille carte" novalidate @submit.prevent="envoyer">
            <Gribouille pose="curieux" class="carte-gribouille" />
            <h1 class="t-page">Nouveau mot de passe</h1>

            <label class="etiquette-champ" for="email">Adresse email</label>
            <input id="email" v-model="form.email" class="champ" type="email" autocomplete="email" required />
            <template v-if="form.errors.email">
                <p class="note-rouge" role="alert">{{ form.errors.email }}</p>
                <p class="note-verte">Pas de souci : <Link href="/mot-de-passe-oublie" class="lien">demande un nouveau lien</Link>, il arrive en une minute.</p>
            </template>

            <label class="etiquette-champ" for="password">Nouveau mot de passe <span class="discret">(8 caractères au moins)</span></label>
            <input id="password" v-model="form.password" class="champ" type="password" autocomplete="new-password" required autofocus />
            <p v-if="form.errors.password" class="note-rouge" role="alert">{{ form.errors.password }}</p>

            <label class="etiquette-champ" for="password_confirmation">Le même, une seconde fois</label>
            <input id="password_confirmation" v-model="form.password_confirmation" class="champ" type="password" autocomplete="new-password" required />

            <button type="submit" class="btn btn-plein" :disabled="form.processing">Enregistrer et ouvrir mon cahier</button>
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
