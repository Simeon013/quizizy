<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Gribouille from '../../composants/Gribouille.vue';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '', site_web: '' });
const envoyer = () => form.post('/inscription', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Créer mon cahier" />
    <section class="page auth">
        <form class="feuille auth-feuille" novalidate @submit.prevent="envoyer">
            <Gribouille pose="eureka" class="auth-gribouille" />
            <h1 class="t-page">Un cahier rien qu'à toi</h1>
            <p class="note-verte">Tes copies, tes erreurs à revoir et tes gommettes y sont rangées. Si tu viens de jouer, ta copie y sera aussi.</p>

            <label class="etiquette-champ" for="name">Ton prénom ou pseudo</label>
            <input id="name" v-model="form.name" class="champ" type="text" autocomplete="nickname" maxlength="40" required autofocus />
            <p v-if="form.errors.name" class="note-rouge" role="alert">{{ form.errors.name }}</p>

            <label class="etiquette-champ" for="email">Adresse email</label>
            <input id="email" v-model="form.email" class="champ" type="email" autocomplete="email" required />
            <p v-if="form.errors.email" class="note-rouge" role="alert">{{ form.errors.email }}</p>

            <label class="etiquette-champ" for="password">Mot de passe <span class="discret">(8 caractères au moins)</span></label>
            <input id="password" v-model="form.password" class="champ" type="password" autocomplete="new-password" required />
            <p v-if="form.errors.password" class="note-rouge" role="alert">{{ form.errors.password }}</p>

            <label class="etiquette-champ" for="password_confirmation">Le même, une seconde fois</label>
            <input id="password_confirmation" v-model="form.password_confirmation" class="champ" type="password" autocomplete="new-password" required />

            <!-- Champ piège : invisible, seuls les robots le remplissent. -->
            <div class="piege" aria-hidden="true">
                <label for="site_web">Site web</label>
                <input id="site_web" v-model="form.site_web" type="text" tabindex="-1" autocomplete="off" />
            </div>

            <button type="submit" class="btn btn-plein" :disabled="form.processing">Créer mon cahier</button>
            <p class="discret">Déjà un cahier ? <Link href="/connexion" class="lien">Se connecter</Link></p>
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
    width: min(480px, 100%);
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
    padding-right: 60px;
}
.etiquette-champ {
    margin-top: 8px;
}
.piege {
    position: absolute;
    left: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
}
.btn-plein {
    margin-top: 10px;
}
</style>
