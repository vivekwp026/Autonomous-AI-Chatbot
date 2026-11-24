import React, { Fragment } from 'react';
import { createRoot } from 'react-dom';
import OpenAiImage from "./image";
import { Toaster } from 'react-hot-toast';

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById('wc_cptbot_create_image');
    if (container) {
        const root = createRoot(container);
        root.render(
            <Fragment>
                <OpenAiImage />
                <Toaster />
            </Fragment>
        );
    }
});
