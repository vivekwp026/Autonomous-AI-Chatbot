import { Fragment } from "react";
import { createRoot } from "react-dom";
import Model from "./model";

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("wc_cptbot_model_container");

    if (!!container) {
        const root = createRoot(container);

        root.render(
            <Fragment>
                <Model />
            </Fragment>
        );
    }
});
