<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    matieres: { type: Array, required: true },
});
</script>

<template>
    <Head title="Matières" />
    <section class="page matieres">
        <header class="tete">
            <p class="surtitre">Les intercalaires</p>
            <h1 class="t-page">Choisis une matière</h1>
            <p class="chapeau">Chaque matière a ses quiz. Commence où tu veux : rien ne se perd, tout se range.</p>
        </header>

        <ul class="pile">
            <li v-for="(m, i) in matieres" :key="m.slug" v-revele="i * 90" class="pile-item">
                <Link :href="`/matieres/${m.slug}`" class="onglet pile-onglet" :style="{ background: m.onglet }">
                    <span class="pile-texte">
                        <strong class="t-carte">{{ m.nom }}</strong>
                        <span>{{ m.description }}</span>
                    </span>
                    <span class="pile-compte">{{ m.quiz }} quiz</span>
                    <svg viewBox="0 0 24 24" class="pile-fleche" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" /></svg>
                </Link>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.matieres {
    display: grid;
    gap: 30px;
    padding-block: 34px 0;
}
.tete {
    display: grid;
    gap: 10px;
}
.pile {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    border-bottom: 2px solid var(--encre);
}
.pile-item + .pile-item {
    margin-top: -12px;
}
.pile-onglet {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 14px;
    padding: 18px 18px 30px;
    text-decoration: none;
    box-shadow: 0 -4px 0 rgb(0 0 0 / 0.06);
    transition: transform 0.25s cubic-bezier(0.3, 1.4, 0.5, 1);
}
.pile-onglet:hover,
.pile-onglet:focus-visible {
    transform: translateY(-10px);
}
.pile-texte {
    display: grid;
    gap: 2px;
    min-width: 0;
}
.pile-texte span {
    font-size: 15px;
}
.pile-compte {
    font-weight: 700;
    font-size: 15px;
    white-space: nowrap;
}
.pile-fleche {
    width: 24px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2.4;
    stroke-linecap: round;
    stroke-linejoin: round;
}
@media (max-width: 520px) {
    .pile-onglet {
        grid-template-columns: minmax(0, 1fr) auto;
    }
    .pile-compte {
        display: none;
    }
}
</style>
