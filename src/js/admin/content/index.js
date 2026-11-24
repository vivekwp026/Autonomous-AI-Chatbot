import React, { Fragment } from 'react';
import { createRoot } from 'react-dom';
import Content from "./content";
import { Toaster } from 'react-hot-toast';

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById('wc_cptbot_content_writing');

    if (container) {
        const root = createRoot(container);
        root.render(
            <Fragment>
                <Content />
                <Toaster />
            </Fragment>
        );
    }
});
