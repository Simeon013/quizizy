<script setup>
import { Link } from '@inertiajs/vue3';
import Gribouille from './Gribouille.vue';

defineProps({ petit: { type: Boolean, default: false } });
</script>

<template>
    <Link href="/" class="logo" :class="{ 'logo-petit': petit }" aria-label="Eurêka, accueil">
        <Gribouille pose="curieux" :vivant="false" class="logo-icone" />
        <span class="logo-mot">Eurêka</span>
    </Link>
</template>

<style scoped>
.logo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--encre);
    text-decoration: none;
}
.logo-icone {
    width: 40px;
    height: 44px;
    transition: transform 0.3s;
}
.logo:hover .logo-icone {
    transform: rotate(-8deg);
}
.logo-mot {
    font-family: var(--font-feutre);
    font-size: 30px;
    line-height: 1;
    background: linear-gradient(transparent 58%, var(--surligne) 58%, var(--surligne) 92%, transparent 92%);
    padding-inline: 2px;
}
.logo-petit .logo-icone {
    width: 32px;
    height: 36px;
}
.logo-petit .logo-mot {
    font-size: 24px;
}
</style>
