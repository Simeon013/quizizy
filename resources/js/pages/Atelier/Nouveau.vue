<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AttenteRedaction from '../../composants/AttenteRedaction.vue';

const props = defineProps({
    matieres: { type: Array, required: true },
    sourceMax: { type: Number, required: true },
    generationDisponible: { type: Boolean, required: true },
    generationsRestantes: { type: Number, default: null },
});

const peutGenerer = computed(() => props.generationDisponible && props.generationsRestantes !== 0);
const form = useForm({
    titre: '',
    matiere_id: props.matieres[0]?.id ?? null,
    description: '',
    mode: peutGenerer.value ? 'texte' : 'main',
    source: '',
    theme: '',
    nombre: 6,
});
const modes = computed(() => [
    { cle: 'texte', titre: 'D\'un texte', texte: 'Colle un cours, un article, une fiche : les questions en sont tirées.', actif: peutGenerer.value },
    { cle: 'theme', titre: 'D\'un thème', texte: 'Donne un sujet : Gribouille pose des questions sur des faits établis.', actif: peutGenerer.value },
    { cle: 'main', titre: 'À la main', texte: 'Tu écris toi-même chaque question.', actif: true },
]);
const envoyer = () => form.post('/atelier');
</script>

<template>
    <Head title="Nouveau quiz" />
    <AttenteRedaction v-if="form.processing && form.mode !== 'main'" />
    <section class="page nouveau">
        <Link href="/atelier" class="lien retour">← Mon atelier</Link>
        <form class="feuille carte" novalidate @submit.prevent="envoyer">
            <p class="surtitre">L'atelier</p>
            <h1 class="t-page">Un nouveau quiz</h1>

            <label class="etiquette-champ" for="titre">Son titre</label>
            <input id="titre" v-model="form.titre" class="champ" maxlength="120" required autofocus />
            <p v-if="form.errors.titre" class="note-rouge" role="alert">{{ form.errors.titre }}</p>

            <fieldset class="matieres">
                <legend class="etiquette-champ">Sa matière</legend>
                <label v-for="m in matieres" :key="m.id" class="onglet-choix" :style="{ '--onglet': m.onglet }">
                    <input v-model="form.matiere_id" type="radio" name="matiere" :value="m.id" />
                    <span>{{ m.nom }}</span>
                </label>
            </fieldset>

            <label class="etiquette-champ" for="description">Une phrase pour le présenter <span class="discret">(facultatif)</span></label>
            <input id="description" v-model="form.description" class="champ" maxlength="255" />

            <fieldset class="modes">
                <legend class="etiquette-champ">D'où viennent les questions ?</legend>
                <label v-for="m in modes" :key="m.cle" class="mode" :class="{ 'mode-choisi': form.mode === m.cle, 'mode-inactif': !m.actif }">
                    <input v-model="form.mode" type="radio" name="mode" :value="m.cle" :disabled="!m.actif" />
                    <strong>{{ m.titre }}</strong>
                    <span class="discret">{{ m.texte }}</span>
                </label>
            </fieldset>
            <p v-if="!generationDisponible" class="discret">La proposition par l'IA n'est pas activée sur ce site.</p>
            <p v-else-if="generationsRestantes === 0" class="note-rouge">Plus de proposition possible aujourd'hui : écris tes questions à la main, ou reviens demain.</p>

            <template v-if="form.mode === 'texte'">
                <label class="etiquette-champ" for="source">Le texte</label>
                <textarea id="source" v-model="form.source" class="champ zone" rows="9" :maxlength="sourceMax" placeholder="Colle ici ton cours, ta fiche de révision, un article…"></textarea>
                <p class="discret chiffres">{{ form.source.length.toLocaleString('fr-FR') }} / {{ sourceMax.toLocaleString('fr-FR') }} caractères · au moins 200</p>
                <p v-if="form.errors.source" class="note-rouge" role="alert">{{ form.errors.source }}</p>
            </template>
            <template v-else-if="form.mode === 'theme'">
                <label class="etiquette-champ" for="theme">Le thème</label>
                <input id="theme" v-model="form.theme" class="champ" maxlength="200" placeholder="Par exemple : le système solaire, la Révolution française…" />
                <p v-if="form.errors.theme" class="note-rouge" role="alert">{{ form.errors.theme }}</p>
            </template>

            <template v-if="form.mode !== 'main'">
                <label class="etiquette-champ" for="nombre">Combien de questions ?</label>
                <div class="nombre">
                    <input id="nombre" v-model.number="form.nombre" type="range" min="3" max="10" />
                    <strong class="chiffres">{{ form.nombre }}</strong>
                </div>
            </template>

            <button type="submit" class="btn btn-plein" :disabled="form.processing">
                {{ form.mode === 'main' ? 'Créer le quiz' : 'Proposer les questions' }}
            </button>
            <p v-if="form.mode !== 'main'" class="note-verte">Les questions arrivent au crayon : tu les relis avant qu'elles comptent.</p>
        </form>
    </section>
</template>

<style scoped>
.nouveau {
    display: grid;
    gap: 16px;
    padding-block: 30px 0;
    justify-items: center;
}
.retour {
    justify-self: start;
}
.carte {
    width: min(720px, 100%);
    display: grid;
    gap: 10px;
    padding: clamp(20px, 4vw, 34px);
}
.etiquette-champ {
    margin-top: 10px;
}
.zone {
    resize: vertical;
    font-family: var(--font-sans);
    font-size: 16px;
    line-height: 1.5;
    border: 2px dashed var(--graphite);
    border-radius: 8px;
    padding: 12px;
}
.matieres,
.modes {
    border: 0;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.matieres legend,
.modes legend {
    width: 100%;
    margin-bottom: 6px;
}
.onglet-choix {
    position: relative;
    cursor: pointer;
}
.onglet-choix input {
    position: absolute;
    opacity: 0;
}
.onglet-choix span {
    display: block;
    padding: 8px 14px;
    border: 2px solid var(--encre);
    border-radius: 12px 12px 0 0;
    background: var(--onglet);
    color: var(--encre-fixe);
    font-weight: 700;
    font-size: 15px;
    opacity: 0.55;
    transition:
        opacity 0.2s,
        transform 0.2s;
}
.onglet-choix input:checked + span {
    opacity: 1;
    transform: translateY(-4px);
}
.onglet-choix input:focus-visible + span {
    outline: 3px dashed var(--rouge);
    outline-offset: 2px;
}
.modes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}
.mode {
    display: grid;
    gap: 4px;
    padding: 14px;
    border: 2px solid var(--quadrille);
    border-radius: 10px;
    cursor: pointer;
}
.mode input {
    justify-self: start;
    margin: 0;
    accent-color: var(--encre);
}
.mode-choisi {
    border-color: var(--encre);
    box-shadow: 3px 3px 0 var(--ombre);
}
.mode-inactif {
    opacity: 0.5;
    cursor: not-allowed;
}
.nombre {
    display: flex;
    align-items: center;
    gap: 14px;
}
.nombre input {
    flex: 1;
    accent-color: var(--rouge);
}
.nombre strong {
    font-family: var(--font-feutre);
    font-weight: 400;
    font-size: 30px;
    min-width: 2ch;
}
.btn-plein {
    justify-self: start;
    margin-top: 12px;
}
</style>
