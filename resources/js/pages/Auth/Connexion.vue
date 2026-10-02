<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Gribouille from '../../composants/Gribouille.vue';

const form = useForm({ email: '', password: '', souvenir: true });
const envoyer = () => form.post('/connexion', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Se connecter" />
    <section class="page auth">
        <form class="feuille auth-feuille" novalidate @submit.prevent="envoyer">
            <Gribouille pose="curieux" class="auth-gribouille" />
            <h1 class="t-page">Ouvrir mon cahier</h1>

            <label class="etiquette-champ" for="email">Adresse email</label>
            <input id="email" v-model="form.email" class="champ" type="email" autocomplete="email" required autofocus />
            <p v-if="form.errors.email" class="note-rouge" role="alert">{{ form.errors.email }}</p>

            <label class="etiquette-champ" for="password">Mot de passe</label>
            <input id="password" v-model="form.password" class="champ" type="password" autocomplete="current-password" required />
            <p v-if="form.errors.password" class="note-rouge" role="alert">{{ form.errors.password }}</p>

            <label class="souvenir"><input v-model="form.souvenir" type="checkbox" /> Rester connecté sur cet appareil</label>

            <button type="submit" class="btn btn-plein" :disabled="form.processing">Se connecter</button>
            <p class="discret">Pas encore de cahier ? <Link href="/inscription" class="lien">En créer un</Link>, c'est gratuit.</p>
        </form>
    </section>
</template>

<style scoped>
.auth {
    display: grid;
    justify-items: center;
    padding-block: 40px 0;
}
.auth-feuille {
    position: relative;
    width: min(460px, 100%);
    display: grid;
    gap: 10px;
    padding: clamp(22px, 4vw, 34px);
}
.auth-gribouille {
    position: absolute;
    top: -30px;
    right: 14px;
    width: 70px;
    height: 76px;
}
.auth-feuille .t-page {
    margin-bottom: 10px;
    padding-right: 60px;
}
.etiquette-champ {
    margin-top: 8px;
}
.souvenir {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 44px;
    font-size: 15px;
}
.souvenir input {
    width: 20px;
    height: 20px;
    accent-color: var(--encre);
}
.btn-plein {
    margin-top: 6px;
}
</style>
