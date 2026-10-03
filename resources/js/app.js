import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import Disposition from './composants/Disposition.vue';
import { papierVivant } from './lib/papier';
import { revele } from './lib/revele';

// Les apparitions au défilement ne s'appliquent que si JavaScript tourne (voir .js .revele).
document.documentElement.classList.add('js');
papierVivant();

createInertiaApp({
    title: (titre) => (titre ? `${titre} · Eurêka` : 'Eurêka · Le quiz qui corrige au stylo vert'),
    resolve: (nom) => {
        const pages = import.meta.glob('./pages/**/*.vue');
        return pages[`./pages/${nom}.vue`]().then((module) => {
            const page = module.default;
            if (page.layout === undefined) page.layout = Disposition;
            return page;
        });
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).directive('revele', revele).mount(el);
    },
    progress: { color: '#C92A25', delay: 200 },
});
