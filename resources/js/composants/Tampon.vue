<script setup>
defineProps({ anime: { type: Boolean, default: true } });
</script>

<template>
    <svg viewBox="0 0 100 100" class="tampon" :class="{ 'tampon-anime': anime }" aria-hidden="true">
        <circle cx="50" cy="50" r="42" fill="none" stroke="var(--rouge)" stroke-width="3" stroke-dasharray="5 4" />
        <path d="M50 20 L58 41 L80 41 L62 54 L69 76 L50 63 L31 76 L38 54 L20 41 L42 41 Z" fill="var(--rouge)" opacity=".85" />
    </svg>
</template>

<style scoped>
.tampon {
    transform: rotate(14deg);
}
.tampon-anime {
    animation: tamponner 0.55s 0.4s cubic-bezier(0.3, 1.6, 0.5, 1) both;
}
@keyframes tamponner {
    from {
        transform: rotate(-10deg) scale(2.4);
        opacity: 0;
    }
    to {
        transform: rotate(14deg) scale(1);
        opacity: 1;
    }
}
</style>
