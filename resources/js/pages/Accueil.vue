<script setup>
/** La couverture du cahier : ce qu'est Eurêka, et une question à essayer tout de suite. */
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import ChoixReponse from '../composants/ChoixReponse.vue';
import Gommette from '../composants/Gommette.vue';
import Gribouille from '../composants/Gribouille.vue';
import Regle from '../composants/Regle.vue';

const props = defineProps({
    matieres: { type: Array, required: true },
    questionsEnTout: { type: Number, required: true },
});
const connecte = computed(() => !!usePage().props.utilisateur);

// Le déclic du logo : « ? » puis « ! ».
const poseLogo = ref('curieux');
onMounted(() => setTimeout(() => (poseLogo.value = 'eureka'), 900));

// Une question d'essai, corrigée sur place (rien n'est envoyé au serveur).
const essai = {
    enonce: 'Quel animal fabrique le miel ?',
    choix: ['La fourmi', 'L\'abeille', 'La guêpe', 'Le papillon'],
    juste: 1,
    explication: 'L\'abeille butine le nectar des fleurs et le transforme en miel dans la ruche.',
};
const choisi = ref(null);
const etat = (i) => (choisi.value === null ? 'neutre' : i === essai.juste ? 'juste' : 'fausse');
const poseEssai = computed(() => (choisi.value === null ? 'curieux' : choisi.value === essai.juste ? 'eureka' : 'oups'));
const recommencer = () => (choisi.value = null);

const etapes = [
    { titre: 'Choisis une matière', texte: 'Sciences, histoire, géographie… chaque matière a son intercalaire et ses quiz.' },
    { titre: 'Coche ta réponse', texte: 'Le crayon s\'use à mesure que le temps passe. Un indice est collé dessous si tu bloques.' },
    { titre: 'Lis la correction', texte: 'Le stylo rouge corrige, le stylo vert explique. Tes erreurs reviennent plus tard, pour de bon.' },
];
</script>

<template>
    <Head title="Le quiz qui corrige au stylo vert" />

    <section class="page hero">
        <div class="hero-texte">
            <p class="surtitre">Le quiz du cahier curieux</p>
            <h1 class="hero-titre">
                <Gribouille :pose="poseLogo" class="hero-gribouille" titre="Gribouille, la mascotte d'Eurêka" />
                <span class="t-geant surligne-anime">Eurêka</span>
            </h1>
            <p class="hero-devise note-verte">Ici, se tromper fait partie du jeu.</p>
            <p class="chapeau">
                Des quiz qui ressemblent à un cahier. Tu coches, tu te trompes, le stylo rouge corrige et le stylo vert t'explique pourquoi. Puis tu
                retiens.
            </p>
            <div class="hero-actions">
                <Link href="/matieres" class="btn btn-plein">Choisir une matière</Link>
                <Link v-if="connecte" href="/cahier" class="btn">Ouvrir mon cahier</Link>
                <Link v-else href="/inscription" class="btn">Créer mon cahier</Link>
            </div>
            <p class="discret">Sans compte pour essayer. {{ questionsEnTout }} questions, et le cahier se remplit.</p>
        </div>

        <div class="essai feuille" aria-labelledby="essai-titre">
            <div class="essai-tete">
                <span class="note-main">À essayer tout de suite</span>
                <Gribouille :pose="poseEssai" class="essai-gribouille" />
            </div>
            <h2 id="essai-titre" class="t-question">{{ essai.enonce }}</h2>
            <div class="essai-choix">
                <ChoixReponse
                    v-for="(c, i) in essai.choix"
                    :key="c"
                    :texte="c"
                    :etat="etat(i)"
                    :choisi="choisi === i"
                    :desactive="choisi !== null"
                    @choisir="choisi = i"
                />
            </div>
            <div v-if="choisi !== null" class="essai-notes" aria-live="polite">
                <p v-if="choisi !== essai.juste" class="note-rouge">« {{ essai.choix[choisi] }} » : raté.</p>
                <p class="note-verte">{{ choisi === essai.juste ? 'Bien vu !' : 'Pas grave !' }} {{ essai.explication }}</p>
                <button type="button" class="btn btn-discret" @click="recommencer">Gommer et recommencer</button>
            </div>
        </div>
    </section>

    <section class="page bloc" aria-labelledby="titre-marche">
        <h2 id="titre-marche" v-revele class="t-section">Comment ça marche</h2>
        <ol class="etapes">
            <li v-for="(e, i) in etapes" :key="e.titre" v-revele="i * 120" class="feuille etape">
                <span class="etape-num" aria-hidden="true">{{ i + 1 }}</span>
                <h3 class="t-carte">{{ e.titre }}</h3>
                <p>{{ e.texte }}</p>
            </li>
        </ol>
    </section>

    <section class="page bloc" aria-labelledby="titre-matieres">
        <div class="bloc-tete">
            <h2 id="titre-matieres" v-revele class="t-section">Les intercalaires</h2>
            <Link href="/matieres" class="lien">Toutes les matières</Link>
        </div>
        <ul class="intercalaires">
            <li v-for="(m, i) in matieres" :key="m.slug" v-revele="i * 80">
                <Link :href="`/matieres/${m.slug}`" class="onglet intercalaire" :style="{ background: m.onglet }">
                    <strong>{{ m.nom }}</strong>
                    <span>{{ m.quiz }} quiz</span>
                </Link>
            </li>
        </ul>
    </section>

    <section class="page bloc cahier-garde" aria-labelledby="titre-garde">
        <div v-revele class="garde-texte">
            <h2 id="titre-garde" class="t-section">Ce que garde ton cahier</h2>
            <p class="chapeau">Avec un cahier (gratuit), chaque copie est rangée. Tes erreurs reviennent dans « À revoir » jusqu'à ce que tu les réussisses.</p>
            <Link v-if="!connecte" href="/inscription" class="btn btn-plein">Créer mon cahier</Link>
        </div>
        <ul class="garde-objets">
            <li v-revele class="feuille objet">
                <span class="objet-note chiffres">8<small>/10</small></span>
                <p><strong>Tes copies</strong><br />Chaque partie, notée et corrigée.</p>
            </li>
            <li v-revele="100" class="feuille objet">
                <span class="objet-revoir"><s>Waterloo, 1812</s><span class="note-verte">→ 1815</span></span>
                <p><strong>À revoir</strong><br />Tes erreurs, à rejouer jusqu'à les réussir.</p>
            </li>
            <li v-revele="200" class="feuille objet">
                <Regle :valeur="7" :max="10" libelle="Exemple de progression" class="objet-regle" />
                <p><strong>Le bulletin</strong><br />Ta moyenne sur 20 dans chaque matière.</p>
            </li>
            <li v-revele="300" class="feuille objet">
                <span class="objet-gommettes">
                    <Gommette symbole="etoile" couleur="#F2B705" :taille="46" />
                    <Gommette symbole="cinq" couleur="#8CC8FF" :taille="46" />
                    <Gommette symbole="eclair" couleur="#FFB4A2" :taille="46" />
                </span>
                <p><strong>Des gommettes</strong><br />Sans faute, série de 5, éclair…</p>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.hero {
    display: grid;
    gap: 36px;
    align-items: center;
    padding-block: 40px 20px;
}
@media (min-width: 940px) {
    .hero {
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
        padding-block: 64px 30px;
    }
}
.hero-texte {
    display: grid;
    gap: 18px;
    min-width: 0;
}
.hero-titre {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0;
    font-weight: 400;
}
.hero-gribouille {
    width: clamp(64px, 10vw, 108px);
    height: auto;
    flex: none;
}
.hero-devise {
    font-size: clamp(28px, 3.6vw, 38px);
}
.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.essai {
    display: grid;
    gap: 16px;
    padding: clamp(18px, 3vw, 28px);
    transform: rotate(0.8deg);
    min-width: 0;
}
.essai-tete {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.essai-gribouille {
    width: 62px;
    height: 68px;
}
.essai-choix {
    display: grid;
    gap: 12px;
}
@media (min-width: 560px) {
    .essai-choix {
        grid-template-columns: 1fr 1fr;
    }
}
.essai-notes {
    display: grid;
    gap: 8px;
    justify-items: start;
}
.bloc {
    display: grid;
    gap: 22px;
    padding-block: 56px 0;
}
.bloc-tete {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
}
.etapes {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 22px;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
}
.etape {
    display: grid;
    gap: 8px;
    align-content: start;
    padding: 20px;
}
.etape-num {
    font-family: var(--font-feutre);
    font-size: 40px;
    line-height: 1;
    color: var(--rouge);
}
.intercalaires {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 8px;
    border-bottom: 2px solid var(--encre);
}
.intercalaire {
    display: grid;
    gap: 2px;
    min-width: 150px;
    padding: 14px 18px 18px;
    text-decoration: none;
    transition: padding 0.2s;
}
.intercalaire:hover {
    padding-bottom: 28px;
}
.intercalaire strong {
    font-size: 18px;
}
.intercalaire span {
    font-size: 14px;
}
.cahier-garde {
    align-items: start;
}
@media (min-width: 940px) {
    .cahier-garde {
        grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
    }
}
.garde-texte {
    display: grid;
    gap: 16px;
    justify-items: start;
}
.garde-objets {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 18px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}
.objet {
    display: grid;
    gap: 12px;
    align-content: start;
    padding: 18px;
}
.objet-note {
    font-family: var(--font-feutre);
    color: var(--rouge);
    font-size: 46px;
    line-height: 1;
}
.objet-note small {
    font-size: 24px;
}
.objet-revoir {
    display: grid;
    min-height: 46px;
}
.objet-revoir s {
    text-decoration-color: var(--rouge);
    text-decoration-thickness: 2px;
    color: var(--graphite);
}
.objet-regle {
    margin-block: 12px;
}
.objet-gommettes {
    display: flex;
    gap: 6px;
}
</style>
