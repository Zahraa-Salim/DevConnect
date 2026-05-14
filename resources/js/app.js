import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    title: (title) => title ? `${title} · DevConnect` : 'DevConnect',
    // Inertia difference: Laravel returns a page name like "Dashboard";
    // this resolver loads resources/js/Pages/Dashboard.vue without creating
    // a separate Blade view or a JSON-only API endpoint for that screen.
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#534AB7',
    },
});
