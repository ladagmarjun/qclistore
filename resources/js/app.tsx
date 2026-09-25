import '../css/admin.css';

import { createInertiaApp, type ResolvedComponent, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { toast } from '@/lib/feedback';

const pages = import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx');

// Show messages the server flashes with Inertia::flash('success' | 'error', ...).
router.on('flash', (event) => {
    const { success, error } = event.detail.flash;
    if (error) toast(error, true);
    else if (success) toast(success);
});

createInertiaApp({
    title: (title) => (title ? `${title} · Peacock Admin` : 'Peacock Admin'),
    resolve: (name) => {
        const page = pages[`./pages/${name}.tsx`];
        if (!page) throw new Error(`Page not found: ${name}`);
        return page().then((module) => module.default);
    },
    setup({ el, App, props }) {
        if (el) createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#e41b17',
    },
});
