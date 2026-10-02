<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Gommette from '../composants/Gommette.vue';
import Gribouille from '../composants/Gribouille.vue';

defineProps({
    etiquette: { type: Object, required: true },
    cornees: { type: Array, required: true },
    copies: { type: Array, required: true },
    aRevoir: { type: Number, required: true },
    gommettes: { type: Array, required: true },
});

const date = (iso) => new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
const reviser = () => router.post('/a-revoir/parties');
</script>

<template>
    <Head title="Mon cahier" />
    <section class="page cahier">
        <div class="couverture">
            <span class="spirale" aria-hidden="true"></span>
            <div class="etiquette">
                <span class="etiquette-titre">Eurêka</span>
                <p class="ligne">Nom : <b>{{ etiquette.nom }}</b></p>
                <p class="ligne">Niveau : <b>{{ etiquette.niveau.nom }}</b></p>
                <p class="ligne chiffres">Points : <b>{{ etiquette.points.toLocaleString('fr-FR') }}</b></p>
                <p class="ligne chiffres">Copies rendues : <b>{{ etiquette.copies }}</b></p>
            </div>
            <div class="couverture-cote">
                <p class="serie" :class="{ 'serie-vide': !etiquette.serie }">
                    {{ etiquette.serie ? `★ ${etiquette.serie} jour${etiquette.serie > 1 ? 's' : ''} d'affilée` : 'Joue aujourd\'hui pour lancer ta série' }}
                </p>
                <p v-if="etiquette.niveau.prochain" class="prochain chiffres">
                    Prochain niveau à {{ etiquette.niveau.prochain.toLocaleString('fr-FR') }} points
                </p>
                <Link href="/matieres" class="btn">Nouvelle copie</Link>
            </div>
        </div>

        <div class="grille">
            <section class="feuille bloc" :class="{ 'bloc-large': !cornees.length }" aria-labelledby="t-revoir">
                <h2 id="t-revoir" class="t-section">À revoir</h2>
                <template v-if="aRevoir">
                    <p><strong class="chiffres">{{ aRevoir }}</strong> question{{ aRevoir > 1 ? 's' : '' }} ratée{{ aRevoir > 1 ? 's' : '' }} t'attend{{ aRevoir > 1 ? 'ent' : '' }}.</p>
                    <p class="note-verte">Les réussir les retire de la liste.</p>
                    <div class="actions">
                        <button type="button" class="btn btn-plein" @click="reviser">Réviser maintenant</button>
                        <Link href="/a-revoir" class="lien">Voir la liste</Link>
                    </div>
                </template>
                <p v-else class="note-verte">Rien à revoir. Toutes tes erreurs sont réparées !</p>
            </section>

            <section v-if="cornees.length" class="feuille bloc" aria-labelledby="t-cornees">
                <h2 id="t-cornees" class="t-section">Pages cornées</h2>
                <ul class="liste">
                    <li v-for="p in cornees" :key="p.id">
                        <Link :href="`/parties/${p.id}`" class="ligne-lien">
                            <span class="puce" :style="{ background: p.onglet || 'var(--carte)' }"></span>
                            <span class="liste-titre">{{ p.titre }}</span>
                            <span class="discret chiffres">question {{ p.position + 1 }}/{{ p.total }}</span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section class="feuille bloc bloc-large" aria-labelledby="t-copies">
                <div class="bloc-tete">
                    <h2 id="t-copies" class="t-section">Dernières copies</h2>
                    <Link href="/bulletin" class="lien">Mon bulletin</Link>
                </div>
                <ul v-if="copies.length" class="copies">
                    <li v-for="c in copies" :key="c.id">
                        <Link :href="`/parties/${c.id}/copie`" class="mini-copie">
                            <span class="mini-note chiffres">{{ c.bonnes }}<small>/{{ c.total }}</small></span>
                            <span class="mini-titre">{{ c.titre }}</span>
                            <span class="discret">{{ date(c.le) }}</span>
                        </Link>
                    </li>
                </ul>
                <div v-else class="vide">
                    <Gribouille pose="dodo" class="vide-gribouille" />
                    <p>Pas encore de copie. <Link href="/matieres" class="lien">Choisis une matière</Link> pour commencer.</p>
                </div>
            </section>

            <section class="feuille bloc bloc-large" aria-labelledby="t-gommettes">
                <h2 id="t-gommettes" class="t-section">Mes gommettes</h2>
                <ul class="gommettes">
                    <li v-for="g in gommettes" :key="g.cle" :class="{ 'non-obtenue': !g.obtenue }">
                        <Gommette :symbole="g.symbole" :couleur="g.couleur" :obtenue="g.obtenue" :taille="58" />
                        <span><strong>{{ g.nom }}</strong><br /><span class="discret">{{ g.description }}</span></span>
                    </li>
                </ul>
            </section>
        </div>
    </section>
</template>

<style scoped>
.cahier {
    display: grid;
    gap: 30px;
    padding-block: 34px 0;
}
.couverture {
    position: relative;
    display: grid;
    gap: 20px;
    padding: 26px 20px 26px 44px;
    background-color: var(--couverture);
    background-image: repeating-linear-gradient(45deg, rgb(255 255 255 / 0.05) 0 2px, transparent 2px 8px);
    color: #fff;
    border: 2px solid var(--encre);
    border-radius: 6px 16px 16px 6px;
    box-shadow: 6px 6px 0 var(--ombre);
}
@media (min-width: 760px) {
    .couverture {
        grid-template-columns: minmax(0, 420px) 1fr;
        align-items: center;
        padding-left: 56px;
    }
}
.spirale {
    position: absolute;
    left: 12px;
    top: 18px;
    bottom: 18px;
    width: 16px;
    background: radial-gradient(circle at 8px 8px, #0d0f1c 4.5px, transparent 5px) 0 0 / 16px 26px;
}
.etiquette {
    background: #fff;
    color: #1e2b5c;
    border: 2px solid #1e2b5c;
    border-radius: 12px;
    padding: 16px 18px;
    display: grid;
    gap: 6px;
    transform: rotate(-1.2deg);
}
.etiquette-titre {
    font-family: var(--font-feutre);
    font-size: 36px;
    text-align: center;
    line-height: 1;
}
.ligne {
    font-family: var(--font-main);
    font-size: 22px;
    border-bottom: 1.5px dashed #9aa8d6;
}
.ligne b {
    color: #c92a25;
}
.couverture-cote {
    display: grid;
    gap: 10px;
    justify-items: start;
}
.serie {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 28px;
    color: #fff27a;
    line-height: 1.1;
}
.serie-vide {
    color: #fff;
}
.prochain {
    font-size: 15px;
    opacity: 0.9;
}
.grille {
    display: grid;
    gap: 24px;
}
@media (min-width: 860px) {
    .grille {
        grid-template-columns: 1fr 1fr;
    }
    .bloc-large {
        grid-column: 1 / -1;
    }
}
.bloc {
    display: grid;
    gap: 12px;
    align-content: start;
    padding: clamp(18px, 3vw, 26px);
    min-width: 0;
}
.bloc-tete {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
}
.actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}
.liste {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 6px;
}
.ligne-lien {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 44px;
    color: inherit;
    text-decoration: none;
    border-bottom: 1px dashed var(--quadrille);
}
.ligne-lien:hover .liste-titre {
    background: linear-gradient(transparent 60%, var(--surligne) 60%);
}
.puce {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid var(--encre);
    flex: none;
}
.liste-titre {
    flex: 1;
    font-weight: 700;
}
.copies {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
}
.mini-copie {
    display: grid;
    gap: 2px;
    padding: 12px 14px;
    border: 2px solid var(--encre);
    border-radius: 6px;
    background: var(--papier);
    color: inherit;
    text-decoration: none;
    transform: rotate(-1deg);
    transition: transform 0.2s;
}
.copies li:nth-child(even) .mini-copie {
    transform: rotate(1deg);
}
.mini-copie:hover {
    transform: rotate(0) translateY(-3px);
}
.mini-note {
    font-family: var(--font-feutre);
    color: var(--rouge);
    font-size: 34px;
    line-height: 1;
}
.mini-note small {
    font-size: 18px;
}
.mini-titre {
    font-weight: 700;
}
.vide {
    display: flex;
    align-items: center;
    gap: 16px;
}
.vide-gribouille {
    width: 70px;
    height: 76px;
    flex: none;
}
.gommettes {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
}
.gommettes li {
    display: flex;
    align-items: center;
    gap: 12px;
}
.non-obtenue strong {
    color: var(--graphite);
}
</style>
