import { Fragment } from 'react';
import { createRoot } from 'react-dom';
import Dashboard from "./dashboard";

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById('wc_cptbot_dashboard');

    if (!!container) {
        const root = createRoot(container);

        root.render(
            <Fragment>
                <Dashboard />
            </Fragment>
        );
    }
});
