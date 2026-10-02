<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Gribouille from '../../composants/Gribouille.vue';

const props = defineProps({ code: { type: String, default: '' } });

const form = useForm({ code: props.code, pseudo: '' });
const envoyer = () => form.post('/rejoindre');
const nettoyer = () => (form.code = form.code.replace(/\D/g, '').slice(0, 6));
</script>

<template>
    <Head title="Rejoindre une partie" />
    <section class="page rejoindre">
        <form class="feuille carte" novalidate @submit.prevent="envoyer">
            <Gribouille pose="curieux" class="carte-gribouille" />
            <p class="surtitre">Partie en direct</p>
            <h1 class="t-page">Rejoindre la classe</h1>
            <p>Tape le code affiché au tableau, choisis un pseudo, et ton téléphone devient ta manette.</p>

            <label class="etiquette-champ" for="code">Code de la partie</label>
            <input
                id="code"
                v-model="form.code"
                class="champ champ-code chiffres"
                inputmode="numeric"
                autocomplete="off"
                maxlength="6"
                placeholder="000000"
                required
                :autofocus="!code"
                @input="nettoyer"
            />
            <p v-if="form.errors.code" class="note-rouge" role="alert">{{ form.errors.code }}</p>

            <label class="etiquette-champ" for="pseudo">Ton pseudo</label>
            <input id="pseudo" v-model="form.pseudo" class="champ" maxlength="20" autocomplete="nickname" required :autofocus="!!code" />
            <p class="discret">Il s'affiche au tableau : choisis un pseudo que tout le monde peut lire.</p>
            <p v-if="form.errors.pseudo" class="note-rouge" role="alert">{{ form.errors.pseudo }}</p>

            <button type="submit" class="btn btn-plein" :disabled="form.processing || form.code.length !== 6 || form.pseudo.trim().length < 2">
                Entrer dans la partie
            </button>
        </form>
    </section>
</template>

<style scoped>
.rejoindre {
    display: grid;
    justify-items: center;
    padding-block: 40px 0;
}
.carte {
    position: relative;
    width: min(480px, 100%);
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
    margin-top: 10px;
}
.champ-code {
    font-family: var(--font-feutre);
    font-size: 40px;
    letter-spacing: 0.3em;
    text-align: center;
}
.champ-code::placeholder {
    color: var(--quadrille);
}
.btn-plein {
    margin-top: 10px;
}
</style>
