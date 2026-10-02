<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Gommette from '../composants/Gommette.vue';

defineProps({
    lignes: { type: Array, required: true },
    appreciation: { type: String, required: true },
    gommettes: { type: Array, required: true },
});

const note = (n) => (n === null ? '—' : n.toLocaleString('fr-FR'));
</script>

<template>
    <Head title="Mon bulletin" />
    <section class="page bulletin">
        <header class="tete">
            <p class="surtitre">Mon cahier</p>
            <h1 class="t-page">Le bulletin</h1>
            <p class="chapeau">Ta moyenne sur 20 dans chaque matière, calculée sur toutes tes copies.</p>
        </header>

        <div class="feuille feuille-bulletin">
            <div class="tableau">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Matière</th>
                            <th scope="col" class="num">Copies</th>
                            <th scope="col" class="num">Moyenne</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in lignes" :key="l.slug">
                            <th scope="row">
                                <Link :href="`/matieres/${l.slug}`" class="matiere">
                                    <span class="puce" :style="{ background: l.onglet }"></span>{{ l.matiere }}
                                </Link>
                            </th>
                            <td class="num chiffres">{{ l.copies }}</td>
                            <td class="num moyenne chiffres" :class="{ 'sans-note': l.moyenne === null }">
                                {{ note(l.moyenne) }}<small v-if="l.moyenne !== null">/20</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="appreciation">
                <span class="discret">Appréciation</span>
                <p class="note-verte">{{ appreciation }}</p>
            </div>
        </div>

        <section aria-labelledby="t-gommettes" class="gommettes-bloc">
            <h2 id="t-gommettes" class="t-section">La collection</h2>
            <ul class="gommettes">
                <li v-for="g in gommettes" :key="g.cle" :title="`${g.nom} : ${g.description}`">
                    <Gommette :symbole="g.symbole" :couleur="g.couleur" :obtenue="g.obtenue" :taille="64" />
                    <span class="nom" :class="{ discret: !g.obtenue }">{{ g.nom }}</span>
                </li>
            </ul>
        </section>
    </section>
</template>

<style scoped>
.bulletin {
    display: grid;
    gap: 28px;
    padding-block: 34px 0;
}
.tete {
    display: grid;
    gap: 10px;
}
.feuille-bulletin {
    display: grid;
    gap: 20px;
    padding: clamp(16px, 3vw, 28px);
}
.tableau {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th,
td {
    text-align: left;
    padding: 10px 6px;
    border-bottom: 1px solid var(--quadrille);
}
thead th {
    border-bottom: 2px solid var(--encre);
    font-size: 14px;
}
.num {
    text-align: right;
}
.matiere {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: inherit;
    text-decoration: none;
}
.puce {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid var(--encre);
}
.moyenne {
    font-family: var(--font-main);
    font-weight: 700;
    font-size: 28px;
    color: var(--rouge);
    line-height: 1;
}
.moyenne small {
    font-size: 18px;
}
.sans-note {
    color: var(--graphite);
}
.appreciation {
    display: grid;
    gap: 4px;
    border-top: 2px dashed var(--quadrille);
    padding-top: 14px;
}
.appreciation .note-verte {
    font-size: 26px;
}
.gommettes-bloc {
    display: grid;
    gap: 16px;
}
.gommettes {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.gommettes li {
    display: grid;
    justify-items: center;
    gap: 6px;
    width: 96px;
    text-align: center;
}
.nom {
    font-size: 14px;
    font-weight: 700;
}
</style>
