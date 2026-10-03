<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import BoutonSon from './BoutonSon.vue';
import BoutonTheme from './BoutonTheme.vue';
import Logo from './Logo.vue';

const page = usePage();
const utilisateur = computed(() => page.props.utilisateur);
const flash = computed(() => page.props.flash ?? {});
const menuOuvert = ref(false);

// Le menu se referme à chaque changement de page.
watch(() => page.url, () => (menuOuvert.value = false));

const liens = computed(() => [
    { href: '/matieres', texte: 'Matières' },
    { href: '/rejoindre', texte: 'Rejoindre' },
    ...(utilisateur.value
        ? [
              { href: '/cahier', texte: 'Mon cahier' },
              { href: '/a-revoir', texte: 'À revoir' },
              { href: '/bulletin', texte: 'Bulletin' },
              { href: '/atelier', texte: 'Atelier' },
          ]
        : []),
]);
const actif = (href) => page.url === href || page.url.startsWith(href + '/');

const deconnecter = () => router.post('/deconnexion');
</script>

<template>
    <a href="#contenu" class="aller-contenu">Aller au contenu</a>
    <header class="entete">
        <div class="page entete-ligne">
            <Logo />
            <nav class="nav" :class="{ 'nav-ouverte': menuOuvert }" aria-label="Navigation principale">
                <Link v-for="l in liens" :key="l.href" :href="l.href" class="nav-lien" :class="{ 'nav-actif': actif(l.href) }">
                    {{ l.texte }}
                </Link>
                <template v-if="utilisateur">
                    <button type="button" class="nav-lien nav-sortir" @click="deconnecter">Se déconnecter</button>
                </template>
                <template v-else>
                    <Link href="/connexion" class="nav-lien" :class="{ 'nav-actif': actif('/connexion') }">Se connecter</Link>
                    <Link href="/inscription" class="btn btn-plein nav-cta">Créer mon cahier</Link>
                </template>
            </nav>
            <div class="entete-outils">
                <BoutonSon />
                <BoutonTheme />
                <button
                    type="button"
                    class="burger"
                    :aria-expanded="menuOuvert"
                    aria-controls="menu"
                    :aria-label="menuOuvert ? 'Fermer le menu' : 'Ouvrir le menu'"
                    @click="menuOuvert = !menuOuvert"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path v-if="!menuOuvert" d="M4 7h16M4 12.5h16M4 18h11" />
                        <path v-else d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div v-if="flash.succes || flash.alerte" class="page flash-zone" role="status">
        <p class="postit flash" :class="{ 'flash-alerte': flash.alerte }">{{ flash.succes || flash.alerte }}</p>
    </div>

    <main id="contenu" class="principal">
        <slot />
    </main>

    <footer class="pied">
        <div class="page pied-ligne">
            <Logo petit />
            <p class="note-verte">Ici, se tromper fait partie du jeu.</p>
        </div>
    </footer>
</template>

<style scoped>
.aller-contenu {
    position: absolute;
    left: -999px;
    top: 8px;
    z-index: 50;
    background: var(--carte);
    padding: 8px 14px;
    border: 2px solid var(--encre);
}
.aller-contenu:focus {
    left: 12px;
}
.entete {
    position: sticky;
    top: 0;
    z-index: 30;
    background: color-mix(in srgb, var(--papier) 88%, transparent);
    backdrop-filter: blur(6px);
    border-bottom: 2px solid var(--encre);
}
.entete-ligne {
    display: flex;
    align-items: center;
    gap: 16px;
    min-height: 68px;
}
.nav {
    display: none;
    align-items: center;
    gap: 4px;
    margin-left: auto;
}
.nav-lien {
    font-weight: 700;
    color: var(--encre);
    text-decoration: none;
    padding: 10px 12px;
    background: none;
    border: 0;
    font-size: 16px;
    cursor: pointer;
    min-height: 44px;
}
.nav-lien:hover,
.nav-actif {
    background: linear-gradient(transparent 55%, var(--surligne) 55%, var(--surligne) 90%, transparent 90%);
}
.nav-cta {
    margin-left: 8px;
    min-height: 44px;
    padding-block: 6px;
}
.entete-outils {
    display: flex;
    gap: 8px;
    margin-left: auto;
}
.burger {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border: 2px solid var(--encre);
    border-radius: 10px;
    background: var(--carte);
    color: var(--encre);
}
.burger svg {
    width: 22px;
    stroke: currentColor;
    stroke-width: 2.4;
    stroke-linecap: round;
    fill: none;
}
@media (max-width: 1099px) {
    .nav.nav-ouverte {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        padding: 12px 16px 18px;
        background: var(--papier);
        border-bottom: 2px solid var(--encre);
        box-shadow: 0 12px 20px -14px rgb(0 0 0 / 0.5);
    }
    .nav-cta {
        margin: 8px 0 0;
    }
    .nav-sortir {
        text-align: left;
    }
}
@media (min-width: 1100px) {
    .nav {
        display: flex;
    }
    .entete-outils {
        margin-left: 8px;
    }
    .burger {
        display: none;
    }
}
.flash-zone {
    padding-top: 18px;
}
.flash {
    max-width: 420px;
    transform: rotate(-1.5deg);
}
.principal {
    min-height: 60vh;
}
.pied {
    margin-top: 80px;
    border-top: 2px solid var(--encre);
}
.pied-ligne {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-block: 22px 30px;
}
</style>
