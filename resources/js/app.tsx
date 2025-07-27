import "../css/app.css";

import AppLayout from "@/layouts/app-layout";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { initializeTheme } from "./hooks/use-appearance";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),

    resolve: (name) => {
        const pages = import.meta.glob("./pages/**/*.tsx", { eager: true });
        let page = pages[`./pages/${name}.tsx`] as any;

        if (!name.startsWith("auth")) {
            page.default.layout = page.default.layout || ((page: any) => <AppLayout children={page} />);
        }

        return page;
    },

    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: "#4B5563",
    },
});

// This will set light / dark mode on load...
initializeTheme();
