/**
 * v-revele : le bloc entre quand il devient visible (classe .est-visible, styles
 * dans app.css). La valeur est un délai en millisecondes, pour une cascade.
 */
let observateur;
function obs() {
    if (observateur || typeof IntersectionObserver === 'undefined') return observateur;
    observateur = new IntersectionObserver(
        (entrees) => {
            entrees.forEach((e) => {
                if (e.isIntersecting) {
                    e.target.classList.add('est-visible');
                    observateur.unobserve(e.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
    );
    return observateur;
}

export const revele = {
    mounted(el, binding) {
        el.classList.add('revele');
        if (binding.value) el.style.transitionDelay = `${binding.value}ms`;
        const o = obs();
        if (o) o.observe(el);
        else el.classList.add('est-visible');
    },
    unmounted(el) {
        observateur?.unobserve(el);
    },
};
