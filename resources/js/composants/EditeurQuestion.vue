<script setup>
/**
 * Écrire ou corriger une question : énoncé, deux à quatre choix dont on coche
 * le bon, l'explication (stylo vert) et un indice facultatif.
 */
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    action: { type: String, required: true },
    methode: { type: String, default: 'post' },
    question: { type: Object, default: null },
    libelle: { type: String, default: 'Enregistrer à l\'encre' },
});
const emit = defineEmits(['fini']);

const depart = props.question;
const form = useForm({
    enonce: depart?.enonce ?? '',
    choix: depart ? depart.choix.map((c) => c.texte) : ['', '', '', ''],
    juste: depart ? Math.max(0, depart.choix.findIndex((c) => c.juste)) : 0,
    explication: depart?.explication ?? '',
    indice: depart?.indice ?? '',
});

const ajouterChoix = () => form.choix.length < 4 && form.choix.push('');
function retirerChoix(i) {
    if (form.choix.length <= 2) return;
    form.choix.splice(i, 1);
    if (form.juste === i) form.juste = 0;
    else if (form.juste > i) form.juste--;
}
const envoyer = () => form[props.methode](props.action, { preserveScroll: true, onSuccess: () => emit('fini') });
const uid = Math.random().toString(36).slice(2, 8);
const erreurChoix = () => Object.entries(form.errors).find(([cle]) => cle.startsWith('choix') || cle === 'juste')?.[1];
</script>

<template>
    <form class="editeur" novalidate @submit.prevent="envoyer">
        <label class="etiquette-champ" :for="`enonce-${uid}`">La question</label>
        <textarea :id="`enonce-${uid}`" v-model="form.enonce" class="champ zone" rows="2" maxlength="300" required></textarea>
        <p v-if="form.errors.enonce" class="note-rouge" role="alert">{{ form.errors.enonce }}</p>

        <fieldset class="choix">
            <legend class="etiquette-champ">Les choix <span class="discret">(coche la bonne réponse)</span></legend>
            <div v-for="(c, i) in form.choix" :key="i" class="choix-ligne">
                <input :id="`juste-${uid}-${i}`" v-model="form.juste" type="radio" :name="`juste-${uid}`" :value="i" class="choix-radio" />
                <label :for="`juste-${uid}-${i}`" class="visually-hidden">Choix {{ i + 1 }} : la bonne réponse</label>
                <input v-model="form.choix[i]" class="champ" :class="{ 'choix-bon': form.juste === i }" maxlength="120" :aria-label="`Choix ${i + 1}`" required />
                <button v-if="form.choix.length > 2" type="button" class="retirer" :aria-label="`Retirer le choix ${i + 1}`" @click="retirerChoix(i)">×</button>
            </div>
            <button v-if="form.choix.length < 4" type="button" class="lien ajouter" @click="ajouterChoix">+ un choix</button>
            <p v-if="erreurChoix()" class="note-rouge" role="alert">{{ erreurChoix() }}</p>
        </fieldset>

        <label class="etiquette-champ etiquette-verte" :for="`explication-${uid}`">L'explication, au stylo vert</label>
        <textarea :id="`explication-${uid}`" v-model="form.explication" class="champ zone" rows="2" maxlength="600" required></textarea>
        <p v-if="form.errors.explication" class="note-rouge" role="alert">{{ form.errors.explication }}</p>

        <label class="etiquette-champ" :for="`indice-${uid}`">L'indice <span class="discret">(facultatif, sur le post-it)</span></label>
        <input :id="`indice-${uid}`" v-model="form.indice" class="champ" maxlength="200" />

        <div class="actions">
            <button type="submit" class="btn btn-plein" :disabled="form.processing">{{ libelle }}</button>
            <button type="button" class="btn btn-discret" @click="emit('fini')">Annuler</button>
        </div>
    </form>
</template>

<style scoped>
.editeur {
    display: grid;
    gap: 8px;
}
.zone {
    resize: vertical;
    min-height: 60px;
    font-family: var(--font-sans);
}
.choix {
    border: 0;
    padding: 0;
    margin: 6px 0 0;
    display: grid;
    gap: 6px;
}
.choix-ligne {
    display: flex;
    align-items: center;
    gap: 10px;
}
.choix-radio {
    width: 22px;
    height: 22px;
    flex: none;
    accent-color: var(--vert);
}
.choix-bon {
    background: linear-gradient(transparent 55%, var(--surligne) 55%);
}
.retirer {
    width: 36px;
    height: 36px;
    flex: none;
    border: 0;
    background: transparent;
    color: var(--graphite);
    font-size: 22px;
    cursor: pointer;
}
.ajouter {
    justify-self: start;
    background: none;
    border: 0;
    padding: 6px 0;
    font: 700 15px var(--font-sans);
    cursor: pointer;
}
.etiquette-verte {
    color: var(--vert);
}
.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
}
.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
}
</style>
