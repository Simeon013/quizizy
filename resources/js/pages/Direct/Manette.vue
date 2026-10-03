<script setup>
/**
 * Le téléphone d'un joueur en direct : la question, quatre grandes tuiles, puis
 * son verdict et son rang. L'état vient du serveur (lib/direct.js).
 */
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BoutonSon from '../../composants/BoutonSon.vue';
import BoutonTheme from '../../composants/BoutonTheme.vue';
import Crayon from '../../composants/Crayon.vue';
import Gribouille from '../../composants/Gribouille.vue';
import Logo from '../../composants/Logo.vue';
import Tuile from '../../composants/Tuile.vue';
import { compteARebours, suivreEtat } from '../../lib/direct';
import { envoyer } from '../../lib/http';

defineOptions({ layout: null });

const props = defineProps({
    salle: { type: String, required: true },
    etat: { type: Object, required: true },
});

const etat = ref(props.etat);
const choixEnvoye = ref(null);
const envoi = ref(false);
const message = ref('');
const horsLigne = ref(false);
const resteMs = ref(0);

let suivi;
let arreterRebours = () => {};

onMounted(() => {
    suivi = suivreEtat(`/direct/${props.salle}/etat`, {
        version: etat.value.version,
        surEtat: (e) => (etat.value = e),
        surErreur: (e) => (horsLigne.value = !!e),
    });
});
onBeforeUnmount(() => {
    suivi?.arreter();
    arreterRebours();
});

// Le crayon se recale sur le temps restant envoyé par le serveur.
watch(
    () => [etat.value.etat, etat.value.question?.id, etat.value.question?.restantMs],
    () => {
        arreterRebours();
        const q = etat.value.question;
        if (etat.value.etat !== 'question' || !q) return;
        arreterRebours = compteARebours(q.restantMs, (ms) => {
            resteMs.value = ms;
            if (ms <= 0) suivi?.maintenant();
        });
    },
    { immediate: true },
);
watch(
    () => etat.value.question?.id,
    () => {
        choixEnvoye.value = null;
        message.value = '';
    },
);

const monChoix = computed(() => etat.value.monChoix ?? choixEnvoye.value);
const q = computed(() => etat.value.question);
const c = computed(() => etat.value.correction);

async function choisir(choixId) {
    if (monChoix.value || envoi.value) return;
    envoi.value = true;
    choixEnvoye.value = choixId;
    try {
        await envoyer(`/direct/${props.salle}/reponse`, { question_id: q.value.id, choix_id: choixId });
        navigator.vibrate?.(30);
        suivi?.maintenant();
    } catch (e) {
        choixEnvoye.value = null;
        message.value = e.statut === 409 ? e.message : 'Ta réponse n\'est pas partie. Réessaie.';
        suivi?.maintenant();
    } finally {
        envoi.value = false;
    }
}

const etatTuile = (id) => (!c.value ? 'neutre' : id === c.value.choixJusteId ? 'juste' : 'fausse');
const texteJuste = computed(() => q.value?.choix.find((ch) => ch.id === c.value?.choixJusteId)?.texte);
const rangTexte = (rang) => (rang === 1 ? '1er' : `${rang}e`);

const pose = computed(() => {
    const e = etat.value.etat;
    if (e === 'attente') return 'curieux';
    if (e === 'question') return monChoix.value ? 'reflechit' : resteMs.value < 5000 ? 'presse' : 'curieux';
    if (e === 'correction') return c.value?.juste ? 'eureka' : 'oups';
    return etat.value.moi.rang && etat.value.moi.rang <= 3 ? 'bravo' : 'eureka';
});
</script>

<template>
    <Head :title="`En direct · ${etat.titre}`" />
    <div class="manette">
        <header class="barre">
            <Logo petit />
            <div class="barre-moi">
                <strong>{{ etat.moi.pseudo }}</strong>
                <span v-if="etat.moi.points !== null" class="chiffres">{{ etat.moi.points.toLocaleString('fr-FR') }} pts</span>
            </div>
            <BoutonSon />
            <BoutonTheme />
        </header>

        <p v-if="horsLigne" class="postit alerte" role="status">Connexion perdue… on réessaie.</p>

        <main class="scene">
            <!-- Retiré par l'animateur -->
            <section v-if="etat.moi.retire" class="centre">
                <Gribouille pose="oups" class="gros" />
                <h1 class="t-section">Tu as quitté la partie</h1>
                <p>L'animateur t'a retiré de cette partie. Tu peux en rejoindre une autre.</p>
                <Link href="/rejoindre" class="btn btn-plein">Rejoindre une partie</Link>
            </section>

            <!-- En attente du lancement -->
            <section v-else-if="etat.etat === 'attente'" class="centre">
                <Gribouille pose="curieux" class="gros" />
                <h1 class="t-section">Tu es dans la partie, {{ etat.moi.pseudo }} !</h1>
                <p class="note-verte">Regarde le tableau : ça commence bientôt.</p>
                <p class="discret chiffres">{{ etat.joueurs }} joueur{{ etat.joueurs > 1 ? 's' : '' }} · code {{ etat.code }}</p>
            </section>

            <!-- La question -->
            <section v-else-if="etat.etat === 'question' && q" class="question">
                <div class="question-tete">
                    <span class="surtitre chiffres">Question {{ q.numero }}/{{ etat.total }}</span>
                    <Crayon :reste="resteMs / (q.secondes * 1000)" :secondes="Math.ceil(resteMs / 1000)" class="question-crayon" />
                </div>
                <h1 class="t-carte enonce">{{ q.enonce }}</h1>
                <template v-if="!monChoix">
                    <div class="tuiles">
                        <Tuile
                            v-for="ch in q.choix"
                            :key="ch.id"
                            :symbole="ch.symbole"
                            :texte="ch.texte"
                            bouton
                            :desactive="envoi || resteMs <= 0"
                            @choisir="choisir(ch.id)"
                        />
                    </div>
                    <p v-if="message" class="note-rouge" role="alert">{{ message }}</p>
                </template>
                <div v-else class="centre attente-autres">
                    <Gribouille pose="reflechit" class="moyen" />
                    <p class="t-section">C'est noté !</p>
                    <Tuile
                        v-for="ch in q.choix.filter((x) => x.id === monChoix)"
                        :key="ch.id"
                        :symbole="ch.symbole"
                        :texte="ch.texte"
                        choisie
                    />
                    <p class="note-verte">On attend les autres…</p>
                </div>
            </section>

            <!-- La correction -->
            <section v-else-if="etat.etat === 'correction' && c" class="centre verdict" aria-live="polite">
                <Gribouille :pose="pose" class="moyen" />
                <template v-if="c.juste">
                    <p class="verdict-mot note-verte">Juste !</p>
                    <p class="verdict-points note-rouge chiffres">+{{ c.points }}</p>
                </template>
                <template v-else>
                    <p class="verdict-mot note-rouge">{{ c.repondu ? 'Raté' : 'Pas de réponse' }}</p>
                    <p class="note-verte">Pas grave ! La bonne réponse : <span class="fluo">{{ texteJuste }}</span>.</p>
                </template>
                <p class="note-verte explication">{{ c.explication }}</p>
                <p v-if="etat.moi.rang" class="rang">
                    Tu es <strong class="chiffres">{{ rangTexte(etat.moi.rang) }}</strong> sur {{ etat.joueurs }}
                </p>
                <div class="tuiles petites">
                    <Tuile
                        v-for="ch in q.choix"
                        :key="ch.id"
                        :symbole="ch.symbole"
                        :texte="ch.texte"
                        :etat="etatTuile(ch.id)"
                        :choisie="ch.id === monChoix"
                    />
                </div>
            </section>

            <!-- Fin -->
            <section v-else-if="etat.etat === 'terminee'" class="centre">
                <Gribouille :pose="pose" class="gros" />
                <p class="surtitre">Partie terminée</p>
                <h1 class="classement-final chiffres">{{ etat.moi.rang ? rangTexte(etat.moi.rang) : '—' }}<small v-if="etat.moi.rang"> sur {{ etat.joueurs }}</small></h1>
                <p class="note-verte">{{ etat.moi.points.toLocaleString('fr-FR') }} points. Merci d'avoir joué !</p>
                <ol class="podium-liste">
                    <li v-for="(p, i) in etat.podium" :key="p.id">
                        <span class="podium-place">{{ i + 1 }}</span>
                        <span>{{ p.pseudo }}</span>
                        <span class="chiffres discret">{{ p.points.toLocaleString('fr-FR') }}</span>
                    </li>
                </ol>
                <Link href="/" class="btn">Découvrir Eurêka</Link>
            </section>
        </main>
    </div>
</template>

<style scoped>
.manette {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
}
.barre {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border-bottom: 2px solid var(--encre);
    background: var(--papier);
}
.barre-moi {
    margin-left: auto;
    display: grid;
    text-align: right;
    line-height: 1.15;
    font-size: 15px;
    min-width: 0;
}
.barre-moi strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.alerte {
    margin: 10px 16px 0;
    font-size: 19px;
}
.scene {
    flex: 1;
    display: grid;
    padding: 18px 16px 28px;
    width: min(640px, 100%);
    margin-inline: auto;
}
.centre {
    display: grid;
    gap: 12px;
    justify-items: center;
    align-content: center;
    text-align: center;
}
.gros {
    width: 130px;
    height: 140px;
}
.moyen {
    width: 90px;
    height: 98px;
}
.question {
    display: grid;
    gap: 14px;
    align-content: start;
}
.question-tete {
    display: flex;
    align-items: center;
    gap: 14px;
}
.question-crayon {
    flex: 1;
}
.enonce {
    font-size: 21px;
}
.tuiles {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    font-size: 17px;
}
.tuiles :deep(.tuile) {
    grid-template-columns: 1fr;
    justify-items: start;
}
.tuiles.petites {
    font-size: 14px;
    width: 100%;
    margin-top: 8px;
}
.tuiles.petites :deep(.tuile) {
    min-height: 0;
    padding: 10px 12px;
}
.attente-autres :deep(.tuile) {
    width: min(280px, 100%);
}
.verdict-mot {
    font-size: 52px;
}
.verdict-points {
    font-size: 40px;
    animation: monter 0.6s cubic-bezier(0.3, 1.6, 0.5, 1) both;
}
@keyframes monter {
    from {
        transform: translateY(14px) scale(0.6);
        opacity: 0;
    }
}
.explication {
    font-size: 20px;
}
.rang {
    font-size: 20px;
}
.rang strong {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 34px;
    color: var(--rouge);
}
.classement-final {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 64px;
    line-height: 1;
    color: var(--rouge);
}
.classement-final small {
    font-size: 28px;
    color: var(--encre);
}
.podium-liste {
    list-style: none;
    margin: 4px 0 8px;
    padding: 0;
    display: grid;
    gap: 6px;
    width: min(320px, 100%);
}
.podium-liste li {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 10px;
    align-items: center;
    text-align: left;
    font-weight: 700;
    border-bottom: 1px dashed var(--quadrille);
    padding-bottom: 4px;
}
.podium-place {
    font-family: var(--font-feutre);
    color: var(--rouge);
    font-size: 22px;
}
</style>
